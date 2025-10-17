<?php

namespace App\Offline\Multimodal;


use App\Database\Criteria;
use App\Repositories\Document;
use App\Repositories\Video;
use App\Services\AppService;
use GoogleSpeechToText;
use GoogleStorage;
use GuzzleHttp\Client;

require_once 'GoogleSpeechToText.php';
require_once 'GoogleStorage.php';

class audioPreprocess_Livia
{
    public $videoFile;
    public $audioFile;
    public $dataPath;
    public $videoSize;
    public $videoPath;
    public $videoFileOriginal;
    public $sha1Name;
    public $video;
    public $ffmpegConfig;
    private $ffmpeg;
    private $idDocument;
    private $idVideo;
    private $idLanguage;

    public function __construct(int $idDocument)
    {
        Criteria::$database = 'webtool';
        $this->idDocument = $idDocument;
        $this->dataPath = "/home/ematos/temp/livia/videos";
        AppService::setCurrentLanguage(1);
    }

    public function process()
    {
        debug("Document " . $this->idDocument);
        $dv = Criteria::table("view_document_video")
            ->where("idDocument", $this->idDocument)
            ->first();
        $document = Document::byId($dv->idDocument);
        $video = Video::byId($dv->idVideo);
        $this->idVideo = $dv->idVideo;
        $this->sha1Name = $video->sha1Name;
        $this->videoFileOriginal = "{$this->dataPath}/{$video->originalFile}";
        $this->videoSize = 'small';
        $this->idLanguage = $video->idLanguage;
        $this->audioProcess();
    }

    public function audioProcess()
    {
        $this->audioFile = "{$this->dataPath}/{$this->sha1Name}.flac";
        if (!file_exists($this->audioFile)) {
            $this->ffmpegConfig = $config = [
                'dataPath' => $this->dataPath,
                'ffmpeg.binaries' => '/usr/bin/ffmpeg', // '/var/www/html/core/support/charon/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',//'/var/www/html/core/support/charon/bin/ffprobe',
            ];
            debug('=1');
            $logger = null;
            // video attributes
            // video compression
            $this->ffmpeg = \FFMpeg\FFMpeg::create([
                'ffmpeg.binaries' => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'timeout' => 3600, // The timeout for the underlying process
                'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
            ], @$logger);
            $this->video = $this->ffmpeg->open($this->videoFileOriginal);
            // Set the formats
            $outputFormat = new \FFMpeg\Format\Audio\Flac(); // Here you choose your output format
            $outputFormat->on('progress', function ($audio, $format, $percentage) {
                debug("$percentage % transcoded");
            });
            $outputFormat
                ->setAudioCodec("flac")
                ->setAudioChannels(1);
            //->setAudioKiloBitrate(256);
            debug("saving audio " . $this->audioFile);
            $this->video->save($outputFormat, $this->audioFile);
        }
        $this->speechToText($this->audioFile);

    }

    public function speechToText($audioFile)
    {
        if (file_exists($audioFile)) {
            $outputFile = "{$this->dataPath}/{$this->sha1Name}-{$this->idLanguage}.json";
            if (!file_exists($outputFile)) {
                $storage = new GoogleStorage();
                $storage->upload_object('charon_bucket', $this->sha1Name . ".flac", $audioFile);
                debug('uploaded to bucket');
                $bucketObject = "gs://charon_bucket/" . $this->sha1Name . ".flac";
                $speechToText = new GoogleSpeechToText($bucketObject, $outputFile, $this->idLanguage);
                $speechToText->process();
            }
            // update table WordMM
            Criteria::table("wordmm")
                ->where("idVideo", $this->idVideo)
                ->where("origin", 0)
                ->delete();
            $transcript = json_decode(file_get_contents($outputFile));
            foreach ($transcript as $sentence) {
                foreach ($sentence->words as $word) {
                    $start = str_replace('s', '', $word->startTime);
                    $end = str_replace('s', '', $word->endTime);
                    // este idDocumentMM é fake, só para manter a compatibilidade com a versão anterior da webtool
                    Criteria::create("wordmm", [
                        "idDocumentMM" => 1339,
                        "word" => $word->word,
                        "startTime" => $start,
                        "endTime" => $end,
                        "origin" => 0,
                        "idVideo" => $this->idVideo
                    ]);
                }
            }
        } else {
            debug('Error! File ' . $audioFile . ' doesnt exist!');
        }
    }

}


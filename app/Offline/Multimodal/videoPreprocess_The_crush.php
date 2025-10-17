<?php

namespace App\Offline\Multimodal;

/**
 * Script executado a partir de services/MultimodalService.php
 * Parâmetros: {$idDocumentMM} {$idUser}
 *
 * see also: https://ricecooker.readthedocs.io/en/latest/video_compression.html
 */

/*
Automated conversion

ffmpeg -i inputfile.mp4 \
  -b:a 32k -ac 1 \
  -vf scale="'w=-2:h=trunc(min(ih,480)/2)*2'" \
  -crf 23 \
  -profile:v baseline -level 3.0 -preset slow -v error -strict -2 -stats \
  -y outputfile.mp4

This command takes the inputfile.mp4 and outputs the file outputfile.mp4 that has the following transformations applied to it:
Limits the audio codec to 32k/sec
Scale the video to max-height of 480 pixels
Compress the video with CRF of 23 (constant rate factor)
*/

use App\Database\Criteria;
use App\Repositories\Document;
use App\Repositories\Video;
use App\Services\AppService;
use getID3;
use thiagoalessio\TesseractOCR\TesseractOCR;
use GuzzleHttp\Client;

require_once 'GoogleSpeechToText.php';
require_once 'GoogleStorage.php';

class videoPreprocess_The_crush
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
        $this->dataPath = "/home/ematos/devel/fnbr/webtool4/storage/app/videos";
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
        $this->videoFileOriginal = "{$this->dataPath}/{$video->sha1Name}_original.mp4";
        $this->videoSize = 'small';
        $this->idLanguage = $video->idLanguage;
        $this->videoProcess();
    }

    public function videoProcess()
    {
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
        //$this->videoFile = str_replace("_original", "", $this->videoFileOriginal);
        $this->videoFile = "{$this->dataPath}/{$this->sha1Name}.mp4";
        debug('=2');
        // preprocess the video
        debug('probing ' . $this->videoFileOriginal);
        $ffprobe = \FFMpeg\FFProbe::create([
            'ffmpeg.binaries' => '/usr/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',
            'timeout' => 3600, // The timeout for the underlying process
            'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
        ], @$logger);

        $first = $ffprobe
            ->streams($this->videoFileOriginal)
            ->videos()
            ->first();
        $duration = (int)$first->get('duration');

        debug('duration 1 :' . $duration);

        $frameRate = (int)$first->get('r_frame_rate');
        debug('framerate 1 :' . $frameRate);

        $duration = floor($duration) * 60;
        debug('duration 2 :' . $duration);
        $frameRate = round($frameRate) / 1000;
        debug('framerate 2 :' . $frameRate);
        $n = round($duration / $frameRate);
        debug('n :' . $n);
        $frameRate = round($duration / $n);
        debug('framerate 3 :' . $frameRate);
        $frameRate = '1/' . $frameRate;
        debug('framerate 4 :' . $frameRate);

        //debug($first->getDimensions());
        // using getID3
        $getID3 = new getID3();
        $file = $getID3->analyze($this->videoFileOriginal);
        $width = $file['video']['resolution_x'];
        $height = $file['video']['resolution_y'];
        $this->videoSize = 'small';
        if ($width > 240 and $height > 180) {
            $this->videoSize = "large";
        }
        debug('width = ' . $width);
        debug('height = ' . $height);

        $newWidth = floor(((480 / $height) * $width) / 2) * 2;
        $originalVideo = $this->ffmpeg->open($this->videoFileOriginal);
        debug('resizing');
        $originalVideo
            ->filters()
            ->resize(new \FFMpeg\Coordinate\Dimension($newWidth, 480), \FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_HEIGHT, true)
            ->synchronize();
        debug('saving');
        $originalVideo
            ->save(new \FFMpeg\Format\Video\X264('copy'), $this->videoFile);
        debug('compressed video file saved');
        $this->videoHeight = 480;
        $this->videoWidth = $newWidth;
        Criteria::table("video")
            ->where("idVideo", $this->idVideo)
            ->update([
                'width' => $this->videoWidth,
                'height' => $this->videoHeight,
            ]);
    }

    /*
    public function getFrames()
    {
        // getting frame
        $imagePath = $this->dataPath . "Images_Store/thumbs/{$this->videoSize}/";
        $fileName = $imagePath . $this->sha1Name . ".jpeg";
        debug('frame file = ' . $fileName);
        $this->video = $this->ffmpeg->open($this->videoFileOriginal);

        try {
            //debug($this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5)));
            //debug(frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5));
            $this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5))->save($fileName);
        } catch (\Exception $e) {
            debug('---' . $e->getMessage());
        }
    }

    public function getAudio()
    {
        $audioPath = $this->dataPath . "Audio_Store/audio/";
        $this->audioFile = $audioPath . $this->sha1Name . ".flac";
        if ((!file_exists($this->audioFile) || $this->preprocess->audio)) {
            // Set the formats
            $outputFormat = new FFMpeg\Format\Audio\Flac(); // Here you choose your output format
            $outputFormat->on('progress', function ($audio, $format, $percentage) {
                debug("$percentage % transcoded");
            });
            $outputFormat
                ->setAudioCodec("flac")
                ->setAudioChannels(1);
            //->setAudioKiloBitrate(256);
            debug("saving audio " . $this->audioFile);
            $this->video->save($outputFormat, $this->audioFile);

            // upload to bucket
            //$export = "export GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/apps/webtool/offline/google-cloud/storage/charon-286713-0b09338da74c.json";
            //shell_exec($export);
            //$upload = "php /var/www/html/apps/webtool/offline/google-cloud/storage/storage.php objects --upload-from=" . $this->audioFile . " charon_bucket " . $this->sha1Name . ".flac";
            //debug($upload);
            //shell_exec($upload);
        }

    }

    public function speechToText()
    {
        $audioPath = $this->dataPath . "Audio_Store/audio/";
        $this->audioFile = $audioPath . $this->sha1Name . ".flac";
        if (file_exists($this->audioFile)) {
            $storage = new GoogleStorage();
            $storage->upload_object('charon_bucket', $this->sha1Name . ".flac", $this->audioFile);
            debug('uploaded to bucket');

            $bucketObject = "gs://charon_bucket/" . $this->sha1Name . ".flac";
            $transcriptPath = $this->dataPath . "Text_Store/transcripts/";
            $outputFile = $transcriptPath . $this->sha1Name . '_' . $this->idLanguage . '.json';
            if ((!file_exists($outputFile) || $this->preprocess->s2t)) {
                $speechToText = new GoogleSpeechToText($bucketObject, $outputFile, $this->idLanguage);
                //$speechToText = new GoogleSpeechToText($bucketObject, $outputFile, 1);
                $speechToText->process();
                // update table WordMM
                $wordMM = new fnbr\models\WordMM();
                $deleteCriteria = $wordMM->getDeleteCriteria();
                $deleteCriteria->where("idDocumentMM = {$this->idDocumentMM}");
                $deleteCriteria->where("origin = 0");
                $deleteCriteria->delete();
                $transcript = json_decode(file_get_contents($outputFile));
                foreach ($transcript as $sentence) {
                    foreach ($sentence->words as $word) {
                        $wordMM->setPersistent(false);
                        $start = str_replace('s', '', $word->startTime);
                        $end = str_replace('s', '', $word->endTime);
                        $wordMM->setWord($word->word);
                        $wordMM->setStartTime($start);
                        $wordMM->setEndTime($end);
                        $wordMM->setStartTimestamp($start);
                        $wordMM->setEndTimestamp($end);
                        $wordMM->setOrigin(0);
                        $wordMM->setIdDocumentMM($this->idDocumentMM);
                        $wordMM->save();
                    }
                }
            }
        } else {
            debug('Error! File ' . $this->audioFile . ' doesnt exist!');
        }
    }

    public function extractFrames()
    {
        debug("extracting frames");
        $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
        if ((!file_exists($framesPath) || $this->preprocess->frames)) {
            debug($framesPath);
            if (is_dir($framesPath)) {
                $this->rrmdir($framesPath);
            }
            mkdir($framesPath, 0777);

            $cmd = $this->ffmpegConfig['ffmpeg.binaries'] . " -i {$this->videoFile} -r 25 -qscale:v 2 {$framesPath}/img%06d.jpg";
            debug($cmd);
            exec($cmd);
            debug("frames extracted.");
        } else {
            debug("frames already extracted.");
        }
    }

    public function tesseract()
    {
        debug("going to Tesseract");
        $subtitlesPath = $this->dataPath . "Text_Store/subtitles/";
        $subtitlesFile = $subtitlesPath . $this->sha1Name . ".srt";

        $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
        $files = array_diff(scandir($framesPath), ['..', '.']);

        $subtitlesFile = fopen($subtitlesFile, "w");
        asort($files);
        foreach ($files as $file) {
            $full_path = $framesPath . '/' . $file;
            $tesseract = new TesseractOCR($full_path);
            $text = $tesseract->run();
            debug($text);
            fwrite($subtitlesFile, $text);
        }
        fclose($subtitlesFile);
        debug("Subtitles extracted.\r\n");
    }

    public function alignment()
    {
        //Decode JSON
        $shaName = basename($this->videoFile, '.mp4');
        $json = file_get_contents($this->transcriptFile);
        $json_data = json_decode($json, true);
        $results = $json_data["results"];
        $parsed_transcript = [];
        $i = -1;
        foreach ($results as $key => $value) {
            $i = $i + 1;
            $det1 = $results[$key];
            $alternatives = $det1["alternatives"];
            $det2 = $alternatives[0];
            $transcript = $det2["transcript"];
            $timestamps = $det2["timestamps"];
            $num = count($timestamps);
            $start_time = $timestamps[0][1];
            $end_time = $timestamps[$num - 1][2];
            $parsed_transcript[$i][0] = $start_time;
            $parsed_transcript[$i][1] = $transcript;
            $parsed_transcript[$i][2] = $end_time;
        }
        $subtitles = file_get_contents($this->dataPath . "./Text_Store/subtitles/{$shaName}.srt");
        $subtitles = str_replace("\n", " ", $subtitles);
        $subtitles = str_replace("‘", "'", $subtitles);
        $sub_ar = explode(" ", $subtitles);
        $this->combinedFile = $this->dataPath . "Text_Store/combined/{$shaName}.txt";
        $combined_file = fopen($this->combinedFile, "w");
        foreach ($parsed_transcript as $key => $value) {
            $tr = $parsed_transcript[$key][1];
            $tr_ar = explode(' ', $tr);
            $cnt = count($tr_ar);
            debug($tr_ar);
            debug($key);

            for ($x = 0; $x <= $cnt - 2; $x++) {
                $flag = 0;
                $cnt1 = count($sub_ar);

                for ($y = 0; $y <= $cnt1 - 2; $y++) {
                    if ($tr_ar[$x] === strtolower($sub_ar[$y]) && $tr_ar[$x + 1] === strtolower($sub_ar[$y + 1]) && $tr_ar[$x + 2] === strtolower($sub_ar[$y + 2])) {
                        $first = $tr_ar[$x];
                        $val = 0;
                        for ($k = $x; $k <= $cnt - 2; $k++) {
                            if ($tr_ar[$k] === $sub_ar[$y + $k - $x] || $tr_ar[$k + 1] === $sub_ar[$y + $k - $x + 1] || $tr_ar[$k] === $sub_ar[$y + $k - $x + 1]) {
                                if ($tr_ar[$k] === $sub_ar[$y + $k - $x + 1]) {
                                    $inserted = array($sub_ar[$y + $k - $x]);

                                    array_splice($tr_ar, $k, 0, $inserted);

                                } else
                                    $tr_ar[$k] = $sub_ar[$y + $k - $x];
                            } else {
                                $val = 1;
                                break;
                            }

                            if ($tr_ar[$k] === $tr_ar[$k + 1])
                                unset($arr1[$k]);
                        }
                        if ($val === 1)
                            $tr_ar[$k] = $sub_ar[$y + $k - $x];
                        else {
                            $tr_ar[$k] = $sub_ar[$y + $k - $x + 1];
                        }

                        $flag = 1;
                        break;
                    }
                }
                if ($flag === 1)
                    break;
            }

            list($sec, $ms) = explode('.', $parsed_transcript[$key][0]);
            $parsed_transcript[$key][3] = gmdate("H:i:s", $sec) . '.' . substr($ms . '000', 0, 3);
            list($sec, $ms) = explode('.', $parsed_transcript[$key][2]);
            $parsed_transcript[$key][4] = gmdate("H:i:s", $sec) . '.' . substr($ms . '000', 0, 3);
            //fwrite($combined_file, $parsed_transcript[$key][0] . "\n" . $parsed_transcript[$key][1] . "\n" . $parsed_transcript[$key][2] . "\n\n");
            fwrite($combined_file, $parsed_transcript[$key][3] . "|" . $parsed_transcript[$key][4] . "|" . $parsed_transcript[$key][1] . "\n");
        }

        debug("Alignments Done.\r\n");

    }


    public function ccextractor()
    {
        debug("going to ccExtractor");
        $subtitlesPath = $this->dataPath . "Text_Store/subtitles/";
        $subtitlesFile = $subtitlesPath . $this->sha1Name . ".srt";
        if ($this->preprocess->cc) {
            if (!file_exists($subtitlesFile)) {
                $ccextractor = 'cd /home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/devel/ccextractor & /home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/devel/ccextractor/ccextractor';
                //$ccextractor = 'cd /var/www/html/apps/webtool/devel/ccextractor & /var/www/html/apps/webtool/devel/ccextractor/ccextractor';
//./ccextractor /var/www/html/apps/webtool/files/multimodal/Video_Store/full/09e29a12a9bbd129d7ec2f5ce090a715e9e99401_original.mp4 -hardsubx -subcolor yellow -detect_italics -whiteness_thresh 90 -conf_thresh 60 -o output.str -ocrlang por -oem 1 -out=ttxt -min_sub_duration 1.0
                $cmd = $ccextractor . ' ' . $this->videoFileOriginal . " -hardsubx -subcolor white -detect_italics -whiteness_thresh 90 -conf_thresh 60 -ocrlang por -oem 1 -out=ttxt -min_sub_duration 0.8 -o " . $subtitlesFile;
                debug($cmd);
                //exec($cmd . " > /dev/null");
                $output = null;
                $retval = null;
                exec($cmd, $output, $retval);
                debug($retval);
                debug($output);
                debug("Subtitles extraction in execution.\r\n");
            }
            if (file_exists($subtitlesFile)) {
                // update table WordMM com as ccSentences
                $wordMM = new fnbr\models\WordMM();
                $deleteCriteria = $wordMM->getDeleteCriteria();
                $deleteCriteria->where("idDocumentMM = {$this->idDocumentMM}");
                $deleteCriteria->where("origin = 1");
                $deleteCriteria->delete();
                $file = file($subtitlesFile);
                foreach ($file as $line) {
                    $line = trim($line);
                    list($startTs, $endTs, $burn, $originalText) = explode('|', $line);
                    $startTime = $this->convertTsToSeconds($startTs);
                    $endTime = $this->convertTsToSeconds($endTs);
                    $text = $this->cleanCCText($originalText);
                    if ($text != '') {
                        debug($startTime . ' ' . $endTime . ' ' . $text);
                        $wordMM->setPersistent(false);
                        $wordMM->setWord($text);
                        $wordMM->setStartTime($startTime);
                        $wordMM->setEndTime($endTime);
                        $wordMM->setStartTimestamp($startTime);
                        $wordMM->setEndTimestamp($endTime);
                        $wordMM->setOrigin(1);
                        $wordMM->setIdDocumentMM($this->idDocumentMM);
                        $wordMM->save();
                    }
                }
            } else {
                debug('Error! File ' . $subtitlesFile . ' doesnt exist!');
            }
        }
    }


    public function charon()
    {
        if ($this->preprocess->yolo) {
            $currentURL = Manager::getConf('charon.currentURL');
            $apiURL = Manager::getConf('charon.apiURL');
            $documentMM = new fnbr\models\DocumentMM();
            $documentMM->getById($this->idDocumentMM);
            $documentMM->clearAllCharonObjects();

            $client = new Client([
                'base_uri' => $apiURL,
                'timeout' => 300.0,
            ]);

            $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
            $images = array_diff(scandir($framesPath), ['..', '.']);
            $countImages = count($images);
            $j = 0;
            for ($frameIndex = 1; $frameIndex < $countImages; $frameIndex = $frameIndex + 75) {
                if ($j++ > 30) {
                    break;
                }
                $imageIndex = $frameIndex + 1;
                $imageURL = str_replace('/home/framenetbr/devel/fnbr/charon_docker_maestro', $currentURL, $framesPath . '/' . $images[$imageIndex]);
                try {
                    $response = $client->request('get', 'predict', [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ],
                        'query' => [
                            'urlImage' => $imageURL,
                        ]
                    ]);
                    $result = json_decode($response->getBody());
                    //debug($result);
                    foreach ($result->labels as $b => $label) {
                        $box = $result->bbox[$b];
                        $documentMM->addCharonObject($frameIndex, $label, $box);
                    }


                } catch (Exception $e) {
                    debug($e->getMessage());
                }
            }
        }
    }

    private function convertTsToSeconds($ts)
    {
        //ts format: 00:00:00,000
        list($h, $m, $s) = explode(':', $ts);
        $s = (float)str_replace(',', '.', $s);
        $seconds = (((int)$h) * 3600) + (((int)$m) * 60) + $s + 0.8;
        return $seconds;
    }

    private function cleanCCText($originalText)
    {
        $text = str_replace(['‘', '’', '_', '|', '\'', '-', '~', '=', '*', ':'], '', $originalText);
        $text = trim($text);
        if (strlen($text) < 4) {
            $text = '';
        }
        return $text;
    }

    public function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . '/' . $object) == 'dir') {
                        $this->rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }
    */
}



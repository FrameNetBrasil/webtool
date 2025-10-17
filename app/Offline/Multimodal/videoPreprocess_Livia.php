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

class videoPreprocess_Livia
{
    public $videoFile;
    public $audioFile;
    public $dataPath;
    public $videoSize;
    public $videoPath;
    public $videoHeight;
    public $videoWidth;
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

    /*
     * 1. colocar os videos em uma pasta temporária
     * 2. Criar o registro do video na webtool, fazendo o upload do arquivo original a partir da pasta temporária
     * 3. Associar o video com o document na webtool
     * 4. Processar o video com este offline, apontando para a pasta temporária (os dados do video são obtidos a partir do idDocument)
     * 5. Fazer o upload do video processado para o servidor de midia (usando a Webtool)
     */


    public function process()
    {
        debug("Document " . $this->idDocument);
        $dv = Criteria::table("view_document_video")
            ->where("idDocument", $this->idDocument)
            ->first();
        $document = Document::byId($dv->idDocument);
        $video = Video::byId($dv->idVideo);
        debug($video);
        $this->idVideo = $dv->idVideo;
        $this->sha1Name = $video->sha1Name;
        $this->videoFileOriginal = "{$this->dataPath}/{$video->originalFile}";
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
}



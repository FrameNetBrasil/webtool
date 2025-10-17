<?php
/**
 * Script executado a partir de services/MultimodalService.php
 * Parâmetros: {$idDocumentMM} {$idUser}
 *
 * see also: https://ricecooker.readthedocs.io/en/latest/video_compression.html
 */

/*
Render video from frames

ffmpeg -framerate 10 -i filename-%03d.jpg output.mp4

*/

require_once 'SimpleImage.php';

class Multimodal
{
    public $videoFile;
    public $audioFile;
    public $transcriptFile;
    public $combinedFile;
    public $dataPath;
    public $videoSize;
    public $videoFileOriginal;
    public $sha1Name;
    public $video;
    public $ffmpegConfig;
    private $ffmpeg;
    private $videoHeight;
    private $videoWidth;

    //public $testingPhase;

    private $idDocumentMM;
    private $idDocument;
    private $idUser;
    private $idLanguage;
    private $email;
    private $preprocess;

    public function __construct($parameters)
    {
        $this->idDocumentMM = $parameters[1];
        $this->idUser = $parameters[2];
        $this->documentMM = new fnbr\models\DocumentMM();
        $this->documentMM->getById($this->idDocumentMM);
        $this->idDocument = $this->documentMM->getIdDocument();
        $document = new fnbr\models\Document($this->idDocument);
        $this->sha1Name = $this->documentMM->getSHA1Name();
        $this->videoFileOriginal = str_replace($this->sha1Name, $this->sha1Name . '_original', $this->documentMM->getVideoPath());
        $this->idLanguage = $this->documentMM->getIdLanguage();
        $this->dataPath = '/var/www/html/apps/webtool/files/multimodal/';
        $this->videoSize = 'small';
    }


    public function process()
    {

        $this->initFfmpeg();
        $this->extractFrames();
        $this->drawFrames();
        //$this->renderVideo();
        $emailService = new EmailService();
        //$emailService->sendSystemEmail($email, 'Webtool: upload Video MM', "The video {$videoFile} was processed.<br>FNBr Webtool Team");
        mdump('finished!!');
    }

    public function initFfmpeg()
    {
        $this->ffmpegConfig = $config = [
            'dataPath' => $this->dataPath,
            'ffmpeg.binaries' => 'ffmpeg', // '/var/www/html/core/support/charon/bin/ffmpeg',
            'ffprobe.binaries' => 'ffprobe',//'/var/www/html/core/support/charon/bin/ffprobe',
        ];
        $logger = null;
        // video attributes
        // video compression
        $this->ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => $config['ffmpeg.binaries'],
            'ffprobe.binaries' => $config['ffprobe.binaries'],
            'timeout' => 3600, // The timeout for the underlying process
            'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
        ], @$logger);
    }

    public function extractFrames()
    {
        mdump("extracting frames");
        $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
        if (!file_exists($framesPath)) {
            mdump($framesPath);
            if (is_dir($framesPath)) {
                $this->rrmdir($framesPath);
            }
            mkdir($framesPath, 0777);

            $cmd = $this->ffmpegConfig['ffmpeg.binaries'] . " -i {$this->videoFile} -r 25 -qscale:v 2 {$framesPath}/img%06d.jpg";
            mdump($cmd);
            exec($cmd);
            mdump("frames extracted.");
        } else {
            mdump("frames already extracted.");
        }
    }

    public function drawFrames()
    {
        $frames = $this->documentMM->listObjectFrames();
        mdump($frames);
        try {
            // Create a new SimpleImage object
            $image = new SimpleImage();
            $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
            $toFramesPath = $framesPath . '/toRender';
            $this->rrmdir($toFramesPath);
            mkdir($toFramesPath);
            $images = array_diff(scandir($framesPath), ['..', '.']);
            foreach ($images as $imageName) {
                $imagePath = $framesPath . '/' . $imageName;
                $toImagePath = $toFramesPath . '/' . $imageName;
                $frame = (int)(str_replace(['img', '.jpg'], '', $imageName));
                if (isset($frames[$frame])) {
                    foreach ($frames[$frame] as $box) {
                        $x1 = $box['x'];
                        $y1 = $box['y'];
                        $x2 = $box['x'] + $box['width'];
                        $y2 = $box['x'] + $box['height'];
                        $image
                            ->fromFile($imagePath)
                            ->rectangle($x1, $y1, $x2, $y2, 'white')
                            ->toFile($toImagePath);
                    }
                } else {
                    $image
                        ->fromFile($imagePath)
                        ->toFile($toImagePath);
                }
            }
        } catch (Exception $err) {
            // Handle errors
            echo $err->getMessage();
        }

    }


    public function renderVideo()
    {
        $this->videoFile = str_replace("_original", "_rendered", $this->videoFileOriginal);
        $this->videoFile = str_replace("full", "rendered", $this->videoFile);
        unlink($this->videoFile);
        //ffmpeg -framerate 10 -i filename-%03d.jpg output.mp4
        $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
        $audioFile = $this->dataPath . "Audio_Store/audio/" . $this->sha1Name . ".flac";
        $cmd = $this->ffmpegConfig['ffmpeg.binaries'] . " -i {$framesPath}/img%06d.jpg  -i {$audioFile} {$this->videoFile}";
        mdump($cmd);
        exec($cmd);
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
}

$app = 'webtool';
$db = 'webtool';

$dirScript = dirname(dirname(__FILE__));
include $dirScript . "/offline.php";
require_once($dirScript . '/../vendor/autoload.php');
include $dirScript . "/../services/EmailService.php";

$configFile = Manager::getHome() . "/apps/{$app}/conf/conf.php";
Manager::loadConf($configFile);
Manager::setConf('logs.level', 2);
Manager::setConf('logs.port', 9998);
Manager::setConf('fnbr.db', $db);

try {
    $mm = new Multimodal($argv);
    $mm->process();
} catch (Exception $e) {
    mdump($e->getMessage());
}


<?php

require_once 'local/GoogleSpeechToText.php';
require_once 'local/GoogleStorage.php';

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

    public function getAudio()
    {
        $dataPath = '/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/files/multimodal/';
        $ffmpegConfig = $config = [
            'dataPath' => $dataPath,
            'ffmpeg.binaries' => 'ffmpeg',
            'ffprobe.binaries' => 'ffprobe',
        ];
        $logger = null;
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries' => $config['ffmpeg.binaries'],
            'ffprobe.binaries' => $config['ffprobe.binaries'],
            'timeout' => 3600, // The timeout for the underlying process
            'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
        ], @$logger);

        $audioPath = $dataPath . "Audio_Store/audio/";
        $audioFileOriginal = $audioPath . "Audio_teste.mp3";
        $audioFile = $audioPath . md5("Audio_teste.mp3") . '.flac';
        $video = $ffmpeg->open($audioFileOriginal);
        // Set the formats
        $outputFormat = new FFMpeg\Format\Audio\Flac(); // Here you choose your output format
        $outputFormat->on('progress', function ($audio, $format, $percentage) {
            print_r("$percentage % transcoded" . PHP_EOL);
        });
        $outputFormat
            ->setAudioCodec("flac")
            ->setAudioChannels(1);
        //->setAudioKiloBitrate(256);
        mdump("saving audio " . $audioFile  . PHP_EOL);
        $video->save($outputFormat, $audioFile);

    }

    public function speechToText()
    {
        $dataPath = '/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/files/multimodal/';
        $audioPath = $dataPath . 'Audio_Store/audio/';
        $audioFile = $audioPath . md5("Audio_teste.mp3")  . '.flac';
        print_r($audioFile . PHP_EOL);
        if (file_exists($audioFile)) {
            $storage = new GoogleStorage();
            print_r('uploading to bucket' . PHP_EOL);
            $storage->upload_object('charon_bucket', md5("Audio_teste.mp3")  . ".flac", $audioFile);
            print_r('uploaded to bucket' . PHP_EOL);

            $bucketObject = "gs://charon_bucket/" . md5("Audio_teste.mp3")  . ".flac";
            $transcriptPath = $dataPath . "Text_Store/transcripts/";
            $outputFile = $transcriptPath . md5("Audio_teste.mp3") . '.json';
            print_r('processing' . PHP_EOL);
            $speechToText = new GoogleSpeechToText($bucketObject, $outputFile, $this->idLanguage);
            $speechToText->process();
            print_r('processed' . PHP_EOL);
        } else {
            mdump('Error! File ' . $this->audioFile . ' doesnt exist!');
        }
    }

    public function sentences() {
        $dataPath = '/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/files/multimodal/';
        $transcriptPath = $dataPath . "Text_Store/transcripts/";
        $outputFile = $transcriptPath . md5("Audio_teste.mp3") . '.json';
        $transcript = json_decode(file_get_contents($outputFile));
        $lines = [];
        foreach ($transcript as $sentence) {
            $lines[] = utf8_decode($sentence->text);
        }
        file_put_contents($transcriptPath . md5("Audio_teste.mp3") . '.txt', implode("\n",$lines));
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
Manager::setConf('logs.port', 9996);
Manager::setConf('fnbr.db', $db);

try {
    $mm = new Multimodal();
//    $mm->getAudio();
//    $mm->speechToText();
    $mm->sentences();
} catch (Exception $e) {
    mdump($e->getMessage());
}


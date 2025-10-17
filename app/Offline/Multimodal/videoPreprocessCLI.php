<?php
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

use thiagoalessio\TesseractOCR\TesseractOCR;
use GuzzleHttp\Client;

require_once 'GoogleSpeechToText.php';
require_once 'GoogleStorage.php';

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
        var_dump($parameters);
        $this->idDocumentMM = $parameters[1];
        $this->idUser = $parameters[2];
        $this->preprocess = json_decode(base64_decode($parameters[3]));
        $documentMM = new fnbr\models\DocumentMM();
        var_dump($this->idDocumentMM);
        $documentMM->getById($this->idDocumentMM);
        $this->idDocument = $documentMM->getIdDocument();
        var_dump($this->idDocument);
        $document = new fnbr\models\Document($this->idDocument);
        $this->sha1Name = $documentMM->getSHA1Name();
        $this->videoFileOriginal = str_replace($this->sha1Name, $this->sha1Name . '_original', $documentMM->getVideoPath());
        $this->idLanguage = $documentMM->getIdLanguage();
        //$this->dataPath = Manager::getAppPath() . '/files/multimodal/';
        $this->dataPath = '/var/www/html/apps/webtool/files/multimodal/';
        //$this->dataPath = '/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/files/multimodal/';
        $this->videoSize = 'small';
        $this->testingPhase = 2;
        var_dump('idLanguage = ' . $this->idLanguage);
        $this->videoFileOriginal = str_replace("/home/framenetbr/devel/fnbr/charon_docker_maestro/","",$this->videoFileOriginal);
        var_dump($this->videoFileOriginal);
        $this->ffmpegConfig = $config = [
            'dataPath' => $this->dataPath,
            'ffmpeg.binaries' => '/usr/bin/ffmpeg', // '/var/www/html/core/support/charon/bin/ffmpeg',
            'ffprobe.binaries' => '/usr/bin/ffprobe',//'/var/www/html/core/support/charon/bin/ffprobe',
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
        $this->videoFile = str_replace("_original", "", $this->videoFileOriginal);
    }


    public function process()
    {
        var_dump($this->preprocess);
//        $this->videoProcess();
//        $this->getFrames();
//        $this->getAudio();
        //$this->speechToText();
        //$this->tesseract();
        //$this->ccextractor();
        //$this->alignment();
        //$this->extractFrames();
        $this->charon();
        $emailService = new EmailService();
        //$emailService->sendSystemEmail($email, 'Webtool: upload Video MM', "The video {$videoFile} was processed.<br>FNBr Webtool Team");
        var_dump('finished!!');
    }

    public function videoProcess()
    {
//        $this->ffmpegConfig = $config = [
//            'dataPath' => $this->dataPath,
//            'ffmpeg.binaries' => '/usr/bin/ffmpeg', // '/var/www/html/core/support/charon/bin/ffmpeg',
//            'ffprobe.binaries' => '/usr/bin/ffprobe',//'/var/www/html/core/support/charon/bin/ffprobe',
//        ];
//        $logger = null;
//        // video attributes
//        // video compression
//        $this->ffmpeg = FFMpeg\FFMpeg::create([
//            'ffmpeg.binaries' => $config['ffmpeg.binaries'],
//            'ffprobe.binaries' => $config['ffprobe.binaries'],
//            'timeout' => 3600, // The timeout for the underlying process
//            'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
//        ], @$logger);
//        $this->videoFile = str_replace("_original", "", $this->videoFileOriginal);
        $logger = null;
        $config = $this->ffmpegConfig;
        var_dump('=1');
        if ((!file_exists($this->videoFile) || $this->preprocess->video)) {
            var_dump('=2');
            // preprocess the video
            var_dump('probing ' . $this->videoFileOriginal);
            $ffprobe = FFMpeg\FFProbe::create([
                'ffmpeg.binaries' => $config['ffmpeg.binaries'],
                'ffprobe.binaries' => $config['ffprobe.binaries'],
                'timeout' => 3600, // The timeout for the underlying process
                'ffmpeg.threads' => 12, // The number of threads that FFMpeg should use
            ], @$logger);

            $first = $ffprobe
                ->streams($this->videoFileOriginal)
                ->videos()
                ->first();
            $duration = $first->get('duration');

            var_dump('duration 1 :' . $duration);

            $frameRate = $first->get('r_frame_rate');
            var_dump('framerate 1 :' . $frameRate);

            $duration = floor($duration) * 60;
            var_dump('duration 2 :' . $duration);
            $frameRate = round($frameRate) / 1000;
            var_dump('framerate 2 :' . $frameRate);
            $n = round($duration / $frameRate);
            var_dump('n :' . $n);
            $frameRate = round($duration / $n);
            var_dump('framerate 3 :' . $frameRate);
            $frameRate = '1/' . $frameRate;
            var_dump('framerate 4 :' . $frameRate);

            //var_dump($first->getDimensions());
            // using getID3
            $getID3 = new getID3;
            $file = $getID3->analyze($this->videoFileOriginal);
            $width = $file['video']['resolution_x'];
            $height = $file['video']['resolution_y'];
            $this->videoSize = 'small';
            if ($width > 240 and $height > 180) {
                $this->videoSize = "large";
            }
            var_dump('width = ' . $width);
            var_dump('height = ' . $height);

            $newWidth = floor(((480 / $height) * $width) / 2) * 2;
            $originalVideo = $this->ffmpeg->open($this->videoFileOriginal);
            var_dump('resizing');
            $originalVideo
                ->filters()
                ->resize(new FFMpeg\Coordinate\Dimension($newWidth, 480), FFMpeg\Filters\Video\ResizeFilter::RESIZEMODE_SCALE_HEIGHT, true)
                ->synchronize();
            var_dump('saving');
            $originalVideo
                ->save(new FFMpeg\Format\Video\X264('copy'), $this->videoFile);
            var_dump('compressed video file saved');
            $this->videoHeight = 480;
            $this->videoWidth = $newWidth;
            $documentMM = new fnbr\models\DocumentMM();
            $documentMM->getById($this->idDocumentMM);
            $documentMM->saveMMData((object)[
                'videoHeight' => $this->videoHeight,
                'videoWidth' => $this->videoWidth,
            ]);
        }

    }

    public function getFrames()
    {
        // getting frame
        $imagePath = $this->dataPath . "Images_Store/thumbs/{$this->videoSize}/";
        $fileName = $imagePath . $this->sha1Name . ".jpeg";
        var_dump('frame file = ' . $fileName);
        $this->video = $this->ffmpeg->open($this->videoFileOriginal);

        try {
            //var_dump($this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5)));
            //var_dump(frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5));
            $this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(5))->save($fileName);
        } catch(\Exception $e) {
            var_dump('---'.$e->getMessage());
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
                var_dump("$percentage % transcoded");
            });
            $outputFormat
                ->setAudioCodec("flac")
                ->setAudioChannels(1);
            //->setAudioKiloBitrate(256);
            var_dump("saving audio " . $this->audioFile);
            $this->video->save($outputFormat, $this->audioFile);

            // upload to bucket
            //$export = "export GOOGLE_APPLICATION_CREDENTIALS=/var/www/html/apps/webtool/offline/google-cloud/storage/charon-286713-0b09338da74c.json";
            //shell_exec($export);
            //$upload = "php /var/www/html/apps/webtool/offline/google-cloud/storage/storage.php objects --upload-from=" . $this->audioFile . " charon_bucket " . $this->sha1Name . ".flac";
            //var_dump($upload);
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
            var_dump('uploaded to bucket');

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
            var_dump('Error! File ' . $this->audioFile . ' doesnt exist!');
        }
    }

    public function extractFrames()
    {
        var_dump("extracting frames");
        var_dump($this->videoFileOriginal);
        $this->videoFile = str_replace("_original", "", $this->videoFileOriginal);
        $framesPath = $this->dataPath . "Video_Frames/" . $this->sha1Name;
        if ((!file_exists($framesPath) || $this->preprocess->frames)) {
            var_dump($framesPath);
            if (is_dir($framesPath)) {
                $this->rrmdir($framesPath);
            }
            mkdir($framesPath, 0777);

            $cmd = $this->ffmpegConfig['ffmpeg.binaries'] . " -i {$this->videoFile} -r 25 -qscale:v 2 {$framesPath}/img%06d.jpg";
            var_dump($cmd);
            exec($cmd);
            var_dump("frames extracted.");
        } else {
            var_dump("frames already extracted.");
        }
    }

    public function tesseract()
    {
        var_dump("going to Tesseract");
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
            var_dump($text);
            fwrite($subtitlesFile, $text);
        }
        fclose($subtitlesFile);
        var_dump("Subtitles extracted.\r\n");
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
            var_dump($tr_ar);
            var_dump($key);

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

        var_dump("Alignments Done.\r\n");

    }


    public function ccextractor()
    {
        var_dump("going to ccExtractor");
        $subtitlesPath = $this->dataPath . "Text_Store/subtitles/";
        $subtitlesFile = $subtitlesPath . $this->sha1Name . ".srt";
        if ($this->preprocess->cc) {
            if (!file_exists($subtitlesFile)) {
                $ccextractor = 'cd /home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/devel/ccextractor & /home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/devel/ccextractor/ccextractor';
                //$ccextractor = 'cd /var/www/html/apps/webtool/devel/ccextractor & /var/www/html/apps/webtool/devel/ccextractor/ccextractor';
//./ccextractor /var/www/html/apps/webtool/files/multimodal/Video_Store/full/09e29a12a9bbd129d7ec2f5ce090a715e9e99401_original.mp4 -hardsubx -subcolor yellow -detect_italics -whiteness_thresh 90 -conf_thresh 60 -o output.str -ocrlang por -oem 1 -out=ttxt -min_sub_duration 1.0
                $cmd = $ccextractor . ' ' . $this->videoFileOriginal . " -hardsubx -subcolor white -detect_italics -whiteness_thresh 90 -conf_thresh 60 -ocrlang por -oem 1 -out=ttxt -min_sub_duration 0.8 -o " . $subtitlesFile;
                var_dump($cmd);
                //exec($cmd . " > /dev/null");
                $output = null;
                $retval = null;
                exec($cmd, $output, $retval);
                var_dump($retval);
                var_dump($output);
                var_dump("Subtitles extraction in execution.\r\n");
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
                        var_dump($startTime . ' ' . $endTime . ' ' . $text);
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
                var_dump('Error! File ' . $subtitlesFile . ' doesnt exist!');
            }
        }
    }


    public function charon()
    {
        //if ($this->preprocess->yolo) {
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
                //$imageURL = str_replace('/home/framenetbr/devel/fnbr/charon_docker_maestro', $currentURL, $framesPath . '/' . $images[$imageIndex]);
                $imageURL = str_replace('/var/www/html', $currentURL, $framesPath . '/' . $images[$imageIndex]);
                var_dump($imageURL);
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
                    //var_dump($result);
                    foreach ($result->labels as $b => $label) {
                        $box = $result->bbox[$b];
                        $documentMM->addCharonObject($frameIndex, $label, $box);
                    }


                } catch (Exception $e) {
                    var_dump($e->getMessage());
                }
            }
        //}
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
        $text = str_replace(['‘', '’', '_', '|', '\'', '-', '~', '=', '*',':'], '', $originalText);
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
}

$app = 'webtool';
$db = 'webtool';

$dirScript = dirname(dirname(__FILE__));
include $dirScript . "/offline.php";
require_once($dirScript . '/../vendor/autoload.php');
include $dirScript . "/../services/EmailService.php";

$configFile = Manager::getHome() . "/apps/{$app}/conf/conf.php";
$manager = Manager::getInstance();
$manager->loadConf($configFile);
$manager->setConf('logs.level', 2);
$manager->setConf('logs.port', 9999);
$manager->setConf('fnbr.db', $db);

try {
    var_dump($argv);
    $mm = new Multimodal($argv);
    $mm->process();
} catch (Exception $e) {
    var_dump($e->getMessage());
}


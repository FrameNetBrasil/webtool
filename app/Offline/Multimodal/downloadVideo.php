<?php

use YouTube\Models\StreamFormat;
use YouTube\YouTubeDownloader;
use YouTube\Exception\YouTubeException;
use YouTube\Utils\Utils;

class Multimodal
{
    public $videoFile;
    public $video;
    private $idDocumentMM;

    public function __construct($parameters)
    {
        $params = json_decode(base64_decode($parameters));
//        mdump($params);
        print_r('URL = ' . $params->url . PHP_EOL);
        print_r('path = ' . $params->path . PHP_EOL);
        $youtube = new YouTubeDownloader();

        $downloadOptions = $youtube->getDownloadLinks($params->url);
        $formats = $downloadOptions->getAllFormats();
        $x = Utils::arrayFilterReset($formats, function ($format) {
            /** @var $format StreamFormat */
            print_r($format->mimeType . ' | ' . $format->quality . ' | ' . $format->audioQuality. PHP_EOL);
            return strpos($format->mimeType, 'video/mp4') === 0 && strpos($format->quality, 'hd720') === 0  && !empty($format->audioQuality);
        });
<<<<<<< HEAD
        if (count($x) == 0) {
            $x = Utils::arrayFilterReset($formats, function ($format) {
                /** @var $format StreamFormat */
                print_r($format->mimeType . ' | ' . $format->quality . ' | ' . $format->audioQuality. PHP_EOL);
                return strpos($format->mimeType, 'video/mp4') === 0 && strpos($format->quality, 'medium') === 0  && !empty($format->audioQuality);
            });
        }
=======
>>>>>>> 760a66d51225e8c9e54f58aa32fa4f728107541e
        if ($x[0]->url == '') {
            $x = Utils::arrayFilterReset($formats, function ($format) {
                /** @var $format StreamFormat */
                print_r($format->mimeType . ' | ' . $format->quality . ' | ' . $format->audioQuality. PHP_EOL);
                return strpos($format->mimeType, 'video/mp4') === 0 && strpos($format->quality, 'large') === 0 && !empty($format->audioQuality);
            });
        }
        print_r($x[0]->mimeType . PHP_EOL);
        print_r($x[0]->quality . PHP_EOL);
        print_r($x[0]->audioQuality . PHP_EOL);
        if ($x[0]->url) {
            //$url = $downloadOptions->getFirstCombinedFormat()->url;
            print_r('====' . $x[0]->url . PHP_EOL);
            file_put_contents($params->path, file_get_contents($x[0]->url));
        } else {
            //echo 'No links found';
            throw new \Exception('Download video file has failed!');
        }

    }

}

$app = 'webtool';
$db = 'webtool';

$dirScript = dirname(dirname(__FILE__));
include $dirScript . "/offline.php";
require_once($dirScript . '/../vendor/autoload.php');

$configFile = Manager::getHome() . "/apps/{$app}/conf/conf.php";
Manager::loadConf($configFile);
Manager::setConf('logs.level', 2);
<<<<<<< HEAD
Manager::setConf('logs.port', 9995);
=======
Manager::setConf('logs.port', 9996);
>>>>>>> 760a66d51225e8c9e54f58aa32fa4f728107541e
Manager::setConf('fnbr.db', $db);

try {
    $mm = new Multimodal($argv[1]);
} catch (Exception $e) {
    mdump($e->getMessage());
}


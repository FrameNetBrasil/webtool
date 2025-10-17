<?php

class Multimodal
{
    public $videoFile;
    public $video;
    private $idDocumentMM;

    public function __construct($parameters)
    {
        $dataPath = '/var/www/html/apps/webtool/files/multimodal/';
        $this->idDocumentMM = $parameters[1];
        print_r('idDocument = ' . $this->idDocumentMM . PHP_EOL);
        $this->videoFile = $parameters[2];
        print_r('video file = ' . $this->videoFile . PHP_EOL);
        $idLanguage = $parameters[3];
        print_r('idLanguage = ' . $idLanguage . PHP_EOL);
        $name = basename($this->videoFile);
        list($name, $extension) = explode('.', $name);
        $originalFile = $name;
        $videoTitle = $name;
        $sha1Name = sha1($name);
        $shaNameOriginal = $sha1Name . '_original';
        $fileName = $shaNameOriginal . '.mp4';
        $targetDir = $dataPath . "Video_Store/full/";
        $targetFile = $targetDir . $fileName;
        print_r('target file = ' . $targetFile . PHP_EOL);
        copy($this->videoFile, $targetFile);
        $videoFile = str_replace('_original', '', $targetFile);
        $documentMM = new fnbr\models\DocumentMM();
        $documentMM->getById($this->idDocumentMM);
        $data = $documentMM->getData();
        $data->title = $videoTitle;
        $data->originalFile = $originalFile;
        $data->sha1Name = $sha1Name;
        $data->videoPath = $videoFile;
        $data->idLanguage = $idLanguage;
        print_r($data);
        $documentMM->saveMMData($data);
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
Manager::setConf('logs.port', 9996);
Manager::setConf('fnbr.db', $db);

try {
    $mm = new Multimodal($argv);
} catch (Exception $e) {
    mdump($e->getMessage());
}


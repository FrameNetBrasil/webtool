<?php

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

class GoogleSpeechToText
{

    private $bucketObject;
    private $outputFile;
    private $idLanguage;

    public function __construct($bucketObject, $outputFile, $idLanguage)
    {
        $this->bucketObject = $bucketObject;
        $this->outputFile = $outputFile;
        $this->idLanguage = $idLanguage;
        //$file = trim("/var/www/html/apps/webtool/offline/google-cloud/storage/charon-286713-0b09338da74c.json");
        $file = trim(__DIR__ . "/charon-286713-0b09338da74c.json");
        //$file = trim("/home/framenetbr/devel/fnbr/charon_docker_maestro/apps/webtool/offline/google-cloud/storage/charon-286713-0b09338da74c.json");
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$file");
    }

    public function process() {

        // change these variables if necessary
        $encoding = AudioEncoding::FLAC;
        $sampleRateHertz = 44100;
        debug('GoogleSpeechToText idLanguage = ' . $this->idLanguage);
        if ($this->idLanguage == 1) {
            $languageCode = 'pt-BR';
        } else if ($this->idLanguage == 2) {
            $languageCode = 'en-US';
        }
        debug('GoogleSpeechToText languageCode = ' . $languageCode);

        if (!extension_loaded('grpc')) {
            throw new \Exception('Install the grpc extension (pecl install grpc)');
        }

// When true, time offsets for every word will be included in the response.
        $enableWordTimeOffsets = true;

// set string as audio content
        $audio = (new RecognitionAudio())
            ->setUri($this->bucketObject);

// set config
        $config = (new RecognitionConfig())
            ->setEncoding($encoding)
            //->setSampleRateHertz($sampleRateHertz)
            ->setLanguageCode($languageCode)
            ->setAudioChannelCount(1)
            ->setEnableWordTimeOffsets($enableWordTimeOffsets);

// create the speech client
        $client = new SpeechClient();

// create the asyncronous recognize operation
        $operation = $client->longRunningRecognize($config, $audio);
        $operation->pollUntilComplete();

        $output = [];

        if ($operation->operationSucceeded()) {
            $response = $operation->getResult();

            // each result is for a consecutive portion of the audio. iterate
            // through them to get the transcripts for the entire audio file.
            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                $mostLikely = $alternatives[0];
                $transcript = $mostLikely->getTranscript();
                $confidence = $mostLikely->getConfidence();
                //printf('Transcript: %s' . PHP_EOL, $transcript);
                //printf('Confidence: %s' . PHP_EOL, $confidence);
                $words = [];
                foreach ($mostLikely->getWords() as $wordInfo) {
                    $startTime = $wordInfo->getStartTime();
                    $endTime = $wordInfo->getEndTime();
                    debug(sprintf('  Word: %s (start: %s, end: %s)' . PHP_EOL,
                        $wordInfo->getWord(),
                        $startTime->serializeToJsonString(),
                        $endTime->serializeToJsonString()));
                    $words[] = [
                        'word' => $wordInfo->getWord(),
                        'startTime' => str_replace('"', '', $startTime->serializeToJsonString()),
                        'endTime' => str_replace('"', '', $endTime->serializeToJsonString())
                    ];
                }
                $output[] = [
                    'text' => $transcript,
                    'words' => $words
                ];
            }
        } else {
            debug('===============');
            debug($operation->getError());
        }

        file_put_contents($this->outputFile, json_encode($output));

        $client->close();

    }
}

<?php

namespace App\Console\Commands\Daisy;

use App\Data\Daisy\DaisyInputData;
use App\Data\LoginData;
use App\Database\Criteria;
use App\Services\AppService;
use App\Services\AuthUserService;
use App\Services\Daisy\DaisyService;
use App\Services\Daisy\GridService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DaisyProcessSentenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daisy:process-sentences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process individual sentence using Daisy';

    public function init()
    {
        ini_set('memory_limit', '10240M');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->init();
            $user = Criteria::one('user', ['login', '=', 'lome']);
            $loginData = LoginData::from([
                'login' => 'lome',
                'password' => $user->passMD5,
            ]);
            AuthUserService::offlineLogin($loginData);
            $frameNames = Criteria::table('view_frame as f')
                ->select('f.idFrame', 'f.name')
                ->where('f.idLanguage', 1)
                ->chunkResult('idFrame', 'name');
            $feNames = Criteria::table('view_frameelement as fe')
                ->select('fe.idFrameElement', 'fe.name')
                ->where('fe.idLanguage', 1)
                ->chunkResult('idFrameElement', 'name');
            $punctuation = " .,;:?/'][\{\}\"!@#$%&*\(\)-_+=";
            $trankit = new TrankitService;
            $trankit->init('http://localhost:8405');
            $gridService = new GridService;
            $daisy = new DaisyService($trankit, $gridService);
            // corpus dtake
            //            $command = "select s.idSentence, s.text,s.idOriginMM,ds.idDocumentSentence,s.idLanguage
            //             from sentence s
            //             join document_sentence ds on (s.idSentence = ds.idSentence)
            //             join document d on (ds.idDocument = d.idDocument)
            //             where ds.idDocumentSentence = 5232265
            //             where d.idCorpus between 204 and 217
            //             and  s.idOriginMM in (10,11,12,13,14,15,16)";
            $command = 'select s.idSentence, s.text,s.idOriginMM,ds.idDocumentSentence,s.idLanguage
             from sentence s
             join document_sentence ds on (s.idSentence = ds.idSentence)
             join document d on (ds.idDocument = d.idDocument)
             where ds.idDocumentSentence = 5232265';
            $sentences = DB::connection('webtool')
                ->select($command);
            debug('count sentence = '.count($sentences));
            mb_internal_encoding('UTF-8'); // this IS A MUST!! PHP has trouble with multibyte when no internal encoding is set!
            $s = 0;
            foreach ($sentences as $sentence) {
                AppService::setCurrentLanguage($sentence->idLanguage);
                $s++;
                try {
                    $text = trim($sentence->text);
                    $tempText = $text;
                    $offset = 0;
                    print_r($sentence->idSentence.' - '.$text."\n");
                    $parts = explode('.', $text);
                    foreach ($parts as $part) {
                        $s = trim($part);
                        if ($s != '') {
                            //                    print_r("====================\n");
                            //                    print_r($sentence->idSentence . ": " . $text . "\n");
                            //                    print_r("====================\n");
                            print_r($s."\n");
                            $data = DaisyInputData::from([
                                'sentence' => $s,
                                'idLanguage' => $sentence->idLanguage,
                                'searchType' => 4,
                                'level' => 5,
                                'gregnetMode' => false,
                            ]);
                            $result = $daisy->disambiguate($data);
                            //                            print_r($result);
                            $i = 0;
                            foreach ($result->result as $idWindow => $window) {
                                foreach ($window as $word => $values) {
                                    $pos = mb_strpos($tempText, $word);
                                    $p = $i + $pos;
                                    $x = $offset + $p;
                                    print_r($word.' - '.($x ?? '')."\n");
                                    $l = strlen($word);
                                    $tempText = mb_substr($tempText, $pos + $l);
                                    $i = $p + $l;
                                }
                            }
                        }
                        $offset = $offset + strlen($part) - 1;
                    }
                    // if ($s > 5) die;
                } catch (\Exception $e) {
                    print_r("\n Error: ".$sentence->idSentence.':'.$e->getMessage());

                    continue;
                }
                break;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }
}

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
use Exception;
use Illuminate\Console\Command;

class DaisyProcessOneSentenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daisy:process-one-sentence {sentence} {idLanguage}';

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
            $trankit = new TrankitService;
            $trankit->init('http://localhost:8405');
            $gridService = new GridService;
            $daisy = new DaisyService($trankit, $gridService);
            mb_internal_encoding('UTF-8'); // this IS A MUST!! PHP has trouble with multibyte when no internal encoding is set!
            $sentence = $this->argument('sentence');
            $idLanguage = (int) $this->argument('idLanguage');
            AppService::setCurrentLanguage($idLanguage);
            $text = trim($sentence);
            $parts = explode('.', $text);
            foreach ($parts as $part) {
                $s = trim($part);
                if ($s != '') {
                    print_r($s."\n");
                    $data = DaisyInputData::from([
                        'sentence' => $s,
                        'idLanguage' => $idLanguage,
                        'searchType' => 4,
                        'level' => 5,
                        'gregnetMode' => false,
                    ]);
                    $result = $daisy->disambiguate($data);
                    $daisy->printClusters($result->clusters);
                    //print_r($result->result);
                }
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
        }
    }
}

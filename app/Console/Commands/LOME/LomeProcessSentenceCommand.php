<?php

namespace App\Console\Commands\LOME;

use App\Data\LoginData;
use App\Database\Criteria;
use App\Services\AppService;
use App\Services\AuthUserService;
use App\Services\LOME\LOMEService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LomeProcessSentenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lome-process-sentence-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process individual sentence to LOME tables';

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
            $lome = new LOMEService;
            $lome->init('https://lome.frame.net.br');
            $trankit = new TrankitService;
            $trankit->init('http://localhost:8405');
            // corpus dtake
            //            $sentences = DB::connection('webtool')
            //                ->select("
            //                select s.idSentence, s.text,s.idOriginMM,ds.idDocumentSentence
            // from sentence s
            // join document_sentence ds on (s.idSentence = ds.idSentence)
            // join document d on (ds.idDocument = d.idDocument)
            // where d.idCorpus between 204 and 217
            //                and  s.idOriginMM in (15,16)
            //                ");
            // corpus reporter_brasil lome
            $sentences = DB::connection('webtool')
                ->select('
                select s.idSentence, s.text,s.idOriginMM,ds.idDocumentSentence
from sentence s
join document_sentence ds on (s.idSentence = ds.idSentence)
join document d on (ds.idDocument = d.idDocument)
where d.idCorpus in (227,228)
                ');
            AppService::setCurrentLanguage(1);
            debug('count sentence = '.count($sentences));
            mb_internal_encoding('UTF-8'); // this IS A MUST!! PHP has trouble with multibyte when no internal encoding is set!
            $s = 0;
            foreach ($sentences as $sentence) {
                $s++;
                try {
                    $text = trim($sentence->text);
                    //                    print_r("====================\n");
                    //                    print_r($sentence->idSentence . ": " . $text . "\n");
                    //                    print_r("====================\n");
                    print_r($s."\n");
                    //                    if ($s < 702) continue;
                    // print_r($tokens);
                    Criteria::deleteById('lome_resultfe', 'idSentence', $sentence->idSentence);
                    // $result = $lome->process($text);
                    $ud = $trankit->parseSentenceRawTokens($text, 1);
                    // print_r($ud);
                    $result = $lome->parse($text);
                    if (is_array($result)) {
                        $result = $result[0];
                        $tokens = $result->tokens;
                        //                        print_r($tokens);
                        $ud = $trankit->processTrankitTokens($tokens, 1);
                        //                        debug($ud);
                        $annotations = $result->annotations;
                        //                        print_r($annotations);
                        //                        print_r($tokens);
                        foreach ($annotations as $annotation) {
                            //                        print_r($annotation);
                            $x = explode('_', strtolower($annotation->label));
                            $idFrame = $x[1];
                            $startCharLOME = $annotation->char_span[0];
                            $endCharLOME = $annotation->char_span[1];

                            $currentChar = $startChar = $endChar = $startCharLOME;
                            while ($currentChar <= $endCharLOME) {
                                $char = mb_substr($text, $currentChar, 1);
                                if (mb_strpos($punctuation, $char) !== false) {
                                    break;
                                }
                                $endChar = $currentChar;
                                $currentChar++;
                            }
                            $word = trim(strtolower(mb_substr($text, $startChar, $endChar - $startChar + 1)));
                            //                            for ($t = $annotation->span[0]; $t <= $annotation->span[1]; $t++) {
                            //                                $word .= $tokens[$t] . ' ';
                            //                            }
                            //                            debug("%%%% word = " . $word, $startChar, $endChar);
                            Criteria::create('lome_resultfe', [
                                'start' => $startChar,
                                'end' => $endChar,
                                'word' => $word,
                                'type' => 'lu',
                                'idSpan' => 0,
                                'idLU' => null,
                                'idFrame' => $idFrame,
                                'idFrameElement' => null,
                                'idSentence' => $sentence->idSentence,
                            ]);

                            foreach ($annotation->children as $fe) {
                                $x = explode('_', strtolower($fe->label));
                                $idFrameElement = $x[1];
                                $object = Criteria::byId('frameelement', 'idFrameElement', $idFrameElement);
                                //                                debug("=== FE ====");
                                $startChar = $fe->char_span[0];
                                $endChar = $fe->char_span[1];
                                //                                debug(" original start end", $startChar, $endChar);
                                // tem remover os espaços em banco (e pontuação do final do span criado pelo LOME)
                                while (mb_strpos($punctuation, mb_substr($text, $endChar, 1)) !== false) {
                                    $endChar--;
                                }
                                $word = trim(strtolower(mb_substr($text, $startChar, $endChar - $startChar + 1)));
                                Criteria::create('lome_resultfe', [
                                    'start' => $startChar,
                                    'end' => $endChar,
                                    'word' => trim(strtolower($word)),
                                    'type' => 'fe',
                                    'idSpan' => 0,
                                    'idLU' => null,
                                    'idFrame' => $idFrame,
                                    'idFrameElement' => $idFrameElement,
                                    'idSentence' => $sentence->idSentence,
                                ]);
                            }
                        }
                    }
                    // if ($s > 5) die;
                } catch (\Exception $e) {
                    print_r("\n".$sentence->idSentence.':'.$e->getMessage());
                    exit;
                }
                //                break;
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
        }
    }
}

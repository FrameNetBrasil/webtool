<?php

namespace App\Console\Commands\LOME;

use App\Data\Annotation\Corpus\AnnotationData;
use App\Data\Annotation\Corpus\DeleteObjectData;
use App\Data\Annotation\Corpus\SelectionData;
use App\Data\Annotation\Session\SessionData;
use App\Data\LoginData;
use App\Database\Criteria;
use App\Enum\Status;
use App\Repositories\AnnotationSet;
use App\Repositories\Lemma;
use App\Services\Annotation\CorpusService;
use App\Services\Annotation\SessionService;
use App\Services\AppService;
use App\Services\AuthUserService;
use App\Services\LOME\LOMEService;
use App\Services\Trankit\TrankitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LomeProcessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lome-process-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process sentences from database to LOME tables';

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
            $udPOS = Criteria::table('udpos')
                ->select('idUDPOS', 'POS')
                ->keyBy('POS')
                ->all();

            $punctuation = " .,;:?/'][\{\}\"!@#$%&*\(\)-_+=“”";
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
                select s.idSentence, s.text,s.idOriginMM,ds.idDocumentSentence,d.entry as dentry
from sentence s
join document_sentence ds on (s.idSentence = ds.idSentence)
join document d on (ds.idDocument = d.idDocument)
where d.idCorpus in (227,228)
order by d.entry
                ');
            AppService::setCurrentLanguage(1);
            print_r('count sentence = '.count($sentences).PHP_EOL);
            mb_internal_encoding('UTF-8'); // this IS A MUST!! PHP has trouble with multibyte when no internal encoding is set!
            $s = 0;
            foreach ($sentences as $sentence) {
                print_r('document = '.$sentence->dentry.PHP_EOL);
                SessionService::startSession(SessionData::from(['idDocumentSentence' => $sentence->idDocumentSentence, '_token' => '']));
                $s++;
                try {
                    $text = trim($sentence->text);
                    //                    print_r("====================\n");
                    //                    print_r($sentence->idSentence . ": " . $text . "\n");
                    //                    print_r("====================\n");
                    print_r($s.'   '.$text."\n");
                    // if ($s > 4) break;
                    // print_r($tokens);
                    Criteria::deleteById('lome_resultfe', 'idSentence', $sentence->idSentence);
                    // $result = $lome->process($text);
                    // $ud = $trankit->parseSentenceRawTokens($text, 1);
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
                            print_r("=========================\n");
                            print_r("   new annotation\n");
                            print_r("=========================\n");
                            $x = explode('_', strtolower($annotation->label));
                            $idFrame = $x[1];
                            $startCharLOME = $annotation->char_span[0];
                            $endCharLOME = $annotation->char_span[1];

                            $currentChar = $startChar = $endChar = $startCharLOME;
                            while ($currentChar < $endCharLOME) {
                                $char = mb_substr($text, $currentChar, 1);
                                //                                if (mb_strpos($punctuation, $char) !== false) {
                                //                                    break;
                                //                                }
                                $endChar = $currentChar;
                                $currentChar++;
                            }
                            $word = trim(strtolower(mb_substr($text, $startChar, $endChar - $startChar + 1)));
                            //                            for ($t = $annotation->span[0]; $t <= $annotation->span[1]; $t++) {
                            //                                $word .= $tokens[$t] . ' ';
                            //                            }
                            //                            debug("%%%% word = " . $word, $startChar, $endChar);
                            print_r([$startCharLOME, $endCharLOME, $word]);
                            $resultfe = [
                                'start' => $startChar,
                                'end' => $endChar,
                                'word' => $word,
                                'type' => 'lu',
                                'idSpan' => 0,
                                'idLU' => null,
                                'idFrame' => $idFrame,
                                'idFrameElement' => null,
                                'idSentence' => $sentence->idSentence,
                            ];
                            // print_r($resultfe);
                            Criteria::create('lome_resultfe', $resultfe);
                            $idAnnotationSet = null;
                            $luToken = $annotation->span[0];
                            print_r([$startCharLOME, $endCharLOME, $luToken, $ud->tokens[$luToken]->upos]);
                            if (($ud->tokens[$luToken]->upos == 'PUNCT')
                                || ($ud->tokens[$luToken]->upos == 'PROPN')
                                || ($ud->tokens[$luToken]->upos == 'PART')
                                || ($ud->tokens[$luToken]->upos == 'SYM')
                                || ($ud->tokens[$luToken]->upos == 'X')) {
                                continue;
                            }
                            $parts = explode(' ', $luToken);
                            if (count($parts) == 1) {
                                if ($word == "'") {
                                    $word = "\'";
                                }
                                if ($word == '\\') {
                                    $word = '/';
                                }
                                $lexicon = DB::connection('webtool')->select("
                                select idLU
from view_lexicon
where form collate 'utf8mb4_bin' = '{$word}'
and idFrame = {$idFrame}
and head = 1
and position = 1
limit 1
                                ");
                                if (count($lexicon) == 0) {
                                    $lemma = DB::connection('webtool')->select("
                                select idLemma
from view_lexicon
where form collate 'utf8mb4_bin' = '{$word}'
and udPOS='{$ud->tokens[$luToken]->upos}'
and idlanguage = 1
and head = 1
and position = 1
limit 1
                                ");
                                    if (empty($lemma)) {
                                        print_r("   --- creating lemma {$word}\n");
                                        $data = (object) [
                                            'name' => strtolower($word),
                                            'idUDPOS' => $udPOS[$ud->tokens[$luToken]->upos]->idUDPOS,
                                            'idLanguage' => 1,
                                            'idUser' => $user->idUser,
                                        ];
                                        //                                    debug($data);
                                        $idLemma = Criteria::function('lemma_create(?)', [json_encode($data)]);
                                    } else {
                                        $idLemma = $lemma[0]->idLemma;
                                    }
                                    print_r("idLemma = {$idLemma}\n");
                                    $lu = DB::connection('webtool')->select("
                                select lu.idLU
from LU
where (lu.idLemma = {$idLemma}) and (lu.idFrame = {$idFrame})
limit 1
                                ");
                                    if (empty($lu)) {
                                        // cria LU Candidate
                                        $lm = Lemma::byId($idLemma);
                                        print_r("   --- creating lu candidate {$lm->shortName}\n");
                                        $data = (object) [
                                            'name' => strtolower($lm->shortName),
                                            'senseDescription' => '',
                                            'discussion' => 'Created by LOME',
                                            'idLemma' => (int) $idLemma,
                                            'idFrame' => (int) $idFrame,
                                            'idDocumentSentence' => $sentence->idDocumentSentence,
                                            'createdAt' => Carbon::now(),
                                            'status' => 'PENDING',
                                            'origin' => 'LOME',
                                            'idUser' => $user->idUser,
                                        ];
                                        //                                    debug($data);
                                        $idLU = Criteria::function('lu_create(?)', [json_encode($data)]);
                                    } else {
                                        $idLU = $lu[0]->idLU;
                                    }
                                } else {
                                    $idLU = $lexicon[0]->idLU;
                                }
                                print_r("idLU = {$idLU}\n");
                                //                                debug('idLU=', $idLU, $tokens[$luToken]);
                                // verifica annotationset para LU neste startChar (pode ter a mesma LU mais de uma vez na sentence)
                                $as = Criteria::table('view_annotationset as a')
                                    ->join('view_annotation_text_target as t', 'a.idAnnotationSet', '=', 't.idAnnotationSet')
                                    ->where('a.idDocumentSentence', $sentence->idDocumentSentence)
                                    ->where('a.idLU', $idLU)
                                    ->where('t.startChar', $startChar)
                                    ->where('a.idUser', 611)
                                    ->first();
                                if (! is_null($as)) {
                                    print_r("as exists - hard delete\n");
                                    Criteria::function('annotationset_hard_delete(?,?)', [$as->idAnnotationSet, $user->idUser]);
                                }

                                $idAnnotationSet = AnnotationSet::createForLU($sentence->idDocumentSentence, $idLU, $startChar, $endChar);
                                print_r("AS created {$idAnnotationSet}\n");

                            }

                            foreach ($annotation->children as $fe) {
                                $x = explode('_', strtolower($fe->label));
                                $idFrameElement = $x[1];
                                $object = Criteria::byId('frameelement', 'idFrameElement', $idFrameElement);
                                debug('=== FE ====');
                                $startChar = $fe->char_span[0];
                                $endChar = $fe->char_span[1];
                                debug(' original start end', $startChar, $endChar);
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

                                debug($idAnnotationSet, '['.$word.']', $startChar, $endChar);
                                if (! is_null($idAnnotationSet)) {
                                    $range = SelectionData::from(json_encode([
                                        'type' => 'word',
                                        'id' => '',
                                        'start' => (string) $startChar,
                                        'end' => (string) $endChar,
                                    ]));
                                    $annotationData = AnnotationData::from([
                                        'idAnnotationSet' => $idAnnotationSet,
                                        'range' => $range,
                                        'idEntity' => $object->idEntity,
                                        'corpusAnnotationType' => 'fe',
                                    ]);
                                    $deleteData = DeleteObjectData::from([
                                        'idAnnotationSet' => $idAnnotationSet,
                                        'idEntity' => $object->idEntity,
                                        'corpusAnnotationType' => 'fe',
                                    ]);
                                    CorpusService::deleteObject($deleteData);
                                    CorpusService::annotateObject($annotationData);
                                    AnnotationSet::updateStatusField($idAnnotationSet, Status::CREATED->value);
                                }
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

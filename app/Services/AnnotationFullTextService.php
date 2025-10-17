<?php

namespace App\Services;

use App\Data\Annotation\_FullText\AnnotationData;
use App\Data\Annotation\_FullText\CreateASData;
use App\Data\Annotation\_FullText\DeleteLabelData;
use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\LU;
use App\Repositories\Timeline;
use App\Repositories\WordForm;
use Illuminate\Support\Facades\DB;


class AnnotationFullTextService
{
    private static function hasTimespan(int $idDocument): bool
    {
        $timespan = Criteria::table("view_document_sentence as ds")
            ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
            ->where("ds.idDocument", $idDocument)
            ->first();
        return !is_null($timespan);
    }

    public static function listSentences(int $idDocument): array
    {
        $u = DB::select("select SESSION_USER()");
        debug($u);
        $hasTimespan = self::hasTimespan($idDocument);
        if ($hasTimespan) {
            $sentences = Criteria::table("view_sentence as s")
                ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
                ->where("d.idDocument", $idDocument)
                ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ts.startTime", "ts.endTime")
                ->orderBy("ts.startTime")
                ->orderBy("ds.idDocumentSentence")
                ->limit(1000)
                ->get()->keyBy("idDocumentSentence")->all();
        } else {
            $sentences = Criteria::table("view_sentence as s")
                ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
                ->where("d.idDocument", $idDocument)
                ->select("s.idSentence", "s.text", "ds.idDocumentSentence")
                ->orderBy("ds.idDocumentSentence")
                ->limit(1000)
                ->get()->keyBy("idDocumentSentence")->all();
        }
        if (!empty($sentences)) {
            $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
            foreach ($targets as $idDocumentSentence => $spans) {
                $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
            }
        }
        return $sentences;
    }

    public static function getPrevious(int $idDocument, int $idDocumentSentence)
    {
        $i = Criteria::table("view_document_sentence")
            ->where("idDocument", "=", $idDocument)
            ->where("idDocumentSentence", "<", $idDocumentSentence)
            ->max('idDocumentSentence');
        return $i ?? null;
    }

    public static function getNext(int $idDocument, int $idDocumentSentence)
    {
        $i = Criteria::table("view_document_sentence")
            ->where("idDocument", "=", $idDocument)
            ->where("idDocumentSentence", ">", $idDocumentSentence)
            ->min('idDocumentSentence');
        return $i ?? null;
    }

    public static function getAnnotationData(int $idDocumentSentence): array
    {
        $sentence = Criteria::table("view_sentence as s")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->where("ds.idDocumentSentence", $idDocumentSentence)
            ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ds.idDocument")
            ->first();
        $words = self::getWords($sentence);
        foreach ($words as $i => $word) {
            if (!$word['hasLU']) {
                $words[$i]['hasLU'] = WordForm::wordHasLU($word['word']);
            }
        }
//        debug($words);
        $document = Document::byId($sentence->idDocument);
        $corpus = Corpus::byId($document->idCorpus);
        return [
            'idDocumentSentence' => $idDocumentSentence,
            'idPrevious' => self::getPrevious($sentence->idDocument, $idDocumentSentence),
            'idNext' => self::getNext($sentence->idDocument, $idDocumentSentence),
            'corpus' => $corpus,
            'document' => $document,
            'sentence' => $sentence,
            'text' => $sentence->text,
            'tokens' => $words,
            'idAnnotationSet' => null
        ];

    }

    public static function getWords(object $sentence): array
    {
        $targets = AnnotationSet::getTargets($sentence->idDocumentSentence);
        // get words/chars
        $wordsChars = AnnotationSet::getWordsChars(htmlspecialchars_decode($sentence->text));
//        debug($wordsChars);
        $words = $wordsChars->words;
        $wordsByChar = [];
        foreach ($words as $word) {
            $wordsByChar[$word['startChar']] = $word;
        }
//        debug($wordsChars->chars);
        $wordTarget = [];
        foreach ($targets as $target) {
            $wordTarget[$target->startChar] = [
                'word' => mb_substr($sentence->text, $target->startChar, ($target->endChar - $target->startChar + 1)),
                'startChar' => $target->startChar,
                'endChar' => $target->endChar,
                'hasLU' => true,
                'idAS' => $target->idAnnotationSet
            ];
        }
        $wordList = [];
        $nextChar = 0;
        while ($nextChar < count($wordsChars->chars)) {
            if (isset($wordTarget[$nextChar])) {
                $wordList[] = $wordTarget[$nextChar];
                $nextChar = $wordTarget[$nextChar]['endChar'] + 1;
            } else {
                $wordList[] = [
                    'word' => $wordsByChar[$nextChar]['word'],
                    'startChar' => $wordsByChar[$nextChar]['startChar'],
                    'endChar' => $wordsByChar[$nextChar]['endChar'],
                    'hasLU' => false
                ];
                $nextChar = $wordsByChar[$nextChar]['endChar'] + 1;
            }
        }
        return $wordList;
    }

    public static function getLUs(int $idDocumentSentence, int $idWord): array
    {
        $sentence = Criteria::table("view_sentence as s")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->where("ds.idDocumentSentence", $idDocumentSentence)
            ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ds.idDocument")
            ->first();
        $words = self::getWords($sentence);
        $wordsToShow = [];
        for ($i = $idWord - 10; $i <= $idWord + 10; $i++) {
            if (isset($words[$i])) {
                if ($words[$i]['word'] != ' ') {
                    $wordsToShow[$i] = $words[$i];
                }
            }
        }
        return [
            'lus' => WordForm::getLUs($words[$idWord]['word']),
            'words' => $wordsToShow,
        ];

    }

    public static function getASData(int $idAS, string $token = ''): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $it = Criteria::table("view_instantiationtype")
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->all();
        $as = Criteria::table("view_annotationset")
            ->where('idAnnotationSet', $idAS)
            ->first();
        $sentence = Criteria::table("view_sentence as s")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->where("ds.idDocumentSentence", $as->idDocumentSentence)
            ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ds.idDocument")
            ->first();
        $wordsChars = AnnotationSet::getWordsChars($sentence->text);
        foreach ($wordsChars->words as $i => $word) {
            $wordsChars->words[$i]['hasFE'] = false;
        }
        $lu = LU::byId($as->idLU);
        $pos = Criteria::byId("pos", "idPOS", $lu->idPOS);
        $layerPOS = ['pos_n' => 'lty_noun', 'pos_v' => 'lty_verb', 'pos_a' => 'lty_adj', 'pos_prep' => 'lty_prep',
            'pos_adv' => 'lty_adv', 'pos_num' => 'lty_num', 'pos_pron' => 'lty_pron', 'pos_ccon' => 'lty_ccon', 'pos_scon' => 'lty_scon' /*, 'pos_intj' => 'lty_intj'*/];
        $labels = [];
        $labels['lty_fe'] = Criteria::table("view_frameelement")
            ->where('idLanguage', $idLanguage)
            ->where("idFrame", $lu->idFrame)
            ->keyBy("idEntity")
            ->all();
        $labels['lty_gf'] = Criteria::table("view_layertype_gl")
            ->where('idLanguage', $idLanguage)
            ->where("entry", "lty_gf")
            ->keyBy("idEntityGenericLabel")
            ->all();
        $labels['lty_pt'] = Criteria::table("view_layertype_gl")
            ->where('idLanguage', $idLanguage)
            ->where("entry", "lty_pt")
            ->keyBy("idEntityGenericLabel")
            ->all();
        $labels['lty_other'] = Criteria::table("view_layertype_gl")
            ->where('idLanguage', $idLanguage)
            ->where("entry", "lty_other")
            ->keyBy("idEntityGenericLabel")
            ->all();
        if (isset($layerPOS[$pos->entry])) {
            $labels[$layerPOS[$pos->entry]] = Criteria::table("view_layertype_gl")
                ->where('idLanguage', $idLanguage)
                ->where("entry", $layerPOS[$pos->entry])
                ->keyBy("idEntityGenericLabel")
                ->all();
        }
        $labels['lty_sent'] = Criteria::table("view_layertype_gl")
            ->where('idLanguage', $idLanguage)
            ->where("entry", "lty_sent")
            ->keyBy("idEntityGenericLabel")
            ->all();
        $entities = [];
        foreach ($labels as $type => $labelNames) {
            foreach ($labelNames as $idEntity => $label) {
                $label->type = $type;
                $entities[$idEntity] = $label;
//                $idLayerTypes[$type] = $label->idLayerType;
            }
        }
        $layers = AnnotationSet::getLayers($idAS);
        $target = array_filter($layers, fn($x) => ($x->layerTypeEntry == 'lty_target'));
        debug('target', $target);
        foreach ($target as $tg) {
            $tg->startWord = $wordsChars->chars[$tg->startChar]['order'];
            $tg->endWord = $wordsChars->chars[$tg->endChar]['order'];
        }
        $allSpans = array_filter($layers, fn($x) => ($x->layerTypeEntry != 'lty_target'));;
        $spans = [];
        $nis = [];
        $idLayers = [];
        $firstWord = array_key_first($wordsChars->words);
        $lastWord = array_key_last($wordsChars->words);
        $spansByLayer = collect($allSpans)->groupBy('idLayer')->all();
        foreach ($spansByLayer as $idLayer => $existingSpans) {
            $idLayers[] = $idLayer;
            for ($i = $firstWord; $i <= $lastWord; $i++) {
                $spans[$i][$idLayer] = null;
            }
            foreach ($existingSpans as $span) {
                if ($span->idTextSpan != '') {
                    $span->startWord = ($span->startChar != -1) ? $wordsChars->chars[$span->startChar]['order'] : -1;
                    $span->endWord = ($span->endChar != -1) ? $wordsChars->chars[$span->endChar]['order'] : -1;

                    if ($span->startWord != -1) {
                        $hasLabel = false;
                        for ($i = $span->startWord; $i <= $span->endWord; $i++) {
                            $name = (!$hasLabel) ? $entities[$span->idEntity]->name : null;
                            $spans[$i][$idLayer] = [
                                'idEntity' => $span->idEntity,
                                'label' => $name,
                            ];
                            $wordsChars->words[$i]['hasAnnotation'] = true;
                            $hasLabel = true;
                        }
                    } else {
                        if ($span->layerTypeEntry == 'lty_fe') {
                            $name = $entities[$span->idEntity]->name;
                            $nis[$span->idInstantiationType][] = [
                                'idEntityFE' => $span->idEntity,
                                'label' => $name
                            ];
                        }
                    }
                }
            }
        }
        $layerTypes = Criteria::table("view_layertype as lt")
            ->join("view_layer as l", "lt.idLayerType", "=", "l.idLayerType")
            ->where('lt.idLanguage', $idLanguage)
            ->where('l.idLanguage', $idLanguage)
            ->whereIn("l.idLayer", $idLayers)
            ->orderBy("lt.layerOrder")
            ->all();

        $alternativeLU = [];
        if ($token != '') {
            $idLU = $lu->idLU;
            foreach (WordForm::getLUs($token, $lu->idLanguage) as $altLU) {
                if ($altLU->idLU != $idLU) {
                    $alternativeLU[] = $altLU;
                }
            }
        }


        //debug($baseLabels, $labels);
//        ksort($spans);
//        debug($labels);
//        debug($it);
//        debug($nis);
//        debug( $wordsChars->words);
//        debug($spans);
        return [
            'pos' => $pos,
            'it' => $it,
            'layerTypes' => $layerTypes,
            'idLayers' => $idLayers,
            'words' => $wordsChars->words,
            'idAnnotationSet' => $idAS,
            'lu' => $lu,
            'target' => $target[0],
            'spans' => $spans,
            'labels' => $labels,
            'entities' => $entities,
            'nis' => $nis,
            'alternativeLU' => $alternativeLU,
            'word' => $token
        ];

    }

    public static function getSpans(int $idAS): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $it = Criteria::table("view_instantiationtype")
            ->where('idLanguage', $idLanguage)
            ->all();
        $as = Criteria::table("view_annotationset")
            ->where('idAnnotationSet', $idAS)
            ->first();
        $sentence = Criteria::table("view_sentence as s")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->where("ds.idDocumentSentence", $as->idDocumentSentence)
            ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ds.idDocument")
            ->first();
        $wordsChars = AnnotationSet::getWordsChars($sentence->text);
        foreach ($wordsChars->words as $i => $word) {
            $wordsChars->words[$i]['hasFE'] = false;
        }
        $layers = AnnotationSet::getLayers($idAS);
        $allSpans = $layers;// array_filter($layers, fn($x) => ($x->layerTypeEntry != 'lty_target'));;
        $spans = [];
        $nis = [];
        $idLayers = [];
        $firstWord = array_key_first($wordsChars->words);
        $lastWord = array_key_last($wordsChars->words);
        $spansByLayerType = [];
        foreach ($allSpans as $i => $span) {
            if ($span->layerTypeEntry != 'lty_target') {
                $idLayers[$span->idLayer] = $span->idLayer;
                $spansByLayerType[$span->idLayerType][$span->idLayer][] = $span;
            }
        }
        $layerTypes = Criteria::table("view_layertype as lt")
            ->join("view_layer as l", "lt.idLayerType", "=", "l.idLayerType")
            ->where('lt.idLanguage', $idLanguage)
            ->where('l.idLanguage', $idLanguage)
            ->whereIn("l.idLayer", $idLayers)
            ->orderBy("lt.layerOrder")
            ->all();
        foreach ($layerTypes as $layerType) {
            $idLayerType = $layerType->idLayerType;
            if (isset($spansByLayerType[$idLayerType])) {
                $idLayer = $layerType->idLayer;
                for ($i = $firstWord; $i <= $lastWord; $i++) {
                    $spans[$i][$idLayer] = null;
                }
                foreach ($spansByLayerType[$idLayerType] as $idLayer => $existingSpans) {
                    foreach ($existingSpans as $span) {
                        if ($span->idTextSpan != '') {
                            $span->startWord = ($span->startChar != -1) ? $wordsChars->chars[$span->startChar]['order'] : -1;
                            $span->endWord = ($span->endChar != -1) ? $wordsChars->chars[$span->endChar]['order'] : -1;

                            if ($span->startWord != -1) {
                                $hasLabel = false;
                                for ($i = $span->startWord; $i <= $span->endWord; $i++) {
                                    $label = $span->name;
                                    $name = (!$hasLabel) ? $label : null;
                                    $spans[$i][$idLayer] = [
                                        'idEntity' => $span->idEntity,
                                        'label' => $name,
                                        'idColor' => $span->idColor,
                                    ];
                                    $wordsChars->words[$i]['hasAnnotation'] = true;
                                    $hasLabel = true;
                                }
                            } else {
                                if ($span->layerTypeEntry == 'lty_fe') {
                                    $nis[$span->idInstantiationType][$span->idEntity] = [
                                        'idEntityFE' => $span->idEntity,
                                        'label' => $span->name,
                                        'idColor' => $span->idColor,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return [
            'it' => $it,
            'layerTypes' => $layerTypes,
            'words' => $wordsChars->words,
            'idAnnotationSet' => $idAS,
            'spans' => $spans,
            'nis' => $nis,
        ];

    }

    /**
     * @throws \Exception
     */
    public static function annotateEntity(AnnotationData $data): void
    {
        DB::transaction(function () use ($data) {
            $annotationSet = Criteria::byId("view_annotationset","idAnnotationSet", $data->idAnnotationSet);
            $idUser = AppService::getCurrentIdUser();
            $userTask = Criteria::table("usertask as ut")
                ->join("task as t", "ut.idTask", "=", "t.idTask")
                ->where("ut.idUser", -2)
                ->where("t.name", 'Default Task')
                ->first();
            if ($data->layerType == 'lty_fe') {
                $fe = Criteria::byId("frameelement", "idEntity", $data->idEntity);
                $spans = Criteria::table("view_annotation_text_fe")
                    ->where('idAnnotationSet', $data->idAnnotationSet)
                    ->where("layerTypeEntry", "lty_fe")
                    ->where("idLanguage", AppService::getCurrentIdLanguage())
                    ->select('idAnnotationSet', 'idLayerType', 'idLayer', 'startChar', 'endChar', 'idEntity', 'idTextSpan', 'layerTypeEntry', 'idInstantiationType')
                    ->all();
                $layers = Criteria::table("view_layer")
                    ->where('idAnnotationSet', $data->idAnnotationSet)
                    ->where("entry", "lty_fe")
                    ->where("idLanguage", AppService::getCurrentIdLanguage())
                    ->all();
                // verify if exists a layer with no overlap, else create one
                $idLayer = 0;
                foreach ($layers as $layer) {
                    $overlap = false;
                    foreach ($spans as $span) {
                        if ($span->idLayer == $layer->idLayer) {
                            if (!(($data->range->end < $span->startChar) || ($data->range->start > $span->endChar))) {
                                $overlap |= true;
                            }
                        }
                    }
                    if (!$overlap) {
                        $idLayer = $layer->idLayer;
                        break;
                    }
                }
                if ($idLayer == 0) {
                    $layerType = Criteria::byId("layertype", "entry", "lty_fe");
                    $idLayer = Criteria::create("layer", [
                        'rank' => 0,
                        'idLayerType' => $layerType->idLayerType,
                        'idAnnotationSet' => $data->idAnnotationSet

                    ]);
                }
            } else {
                $gl = Criteria::byId("genericlabel", "idEntity", $data->idEntity);
                $layer = Criteria::table("view_layer")
                    ->where('idAnnotationSet', $data->idAnnotationSet)
                    ->where("entry", $data->layerType)
                    ->where("idLanguage", AppService::getCurrentIdLanguage())
                    ->first();
                $idLayer = $layer->idLayer;
            }
            //
            $idAnnotation = 0;
            if ($data->range->type == 'word') {
                $it = Criteria::table("view_instantiationtype")
                    ->where('entry', 'int_normal')
                    ->first();
                $json = json_encode([
                    'startChar' => (int)$data->range->start,
                    'endChar' => (int)$data->range->end,
                    'multi' => 0,
                    'idLayer' => $idLayer,
                    'idInstantiationType' => $it->idInstantiationType,
                    'idSentence' => $annotationSet->idSentence,
                ]);
                $idTextSpan = Criteria::function("textspan_char_create(?)", [$json]);
                $ts = Criteria::table("textspan")
                    ->where("idTextSpan", $idTextSpan)
                    ->first();
                $json = json_encode([
                    'idAnnotationObject' => $ts->idAnnotationObject,
                    'idEntity' => $data->idEntity,
                    'relationType' => 'rel_annotation',
                    'idUserTask' => $userTask->idUserTask,
                    'idUser' => $idUser
                ]);
                $idAnnotation = Criteria::function("annotation_create(?)", [$json]);
            } else if ($data->range->type == 'ni') {
                $json = json_encode([
                    'startChar' => -1,
                    'endChar' => -1,
                    'multi' => 0,
                    'idLayer' => $idLayer,
                    'idInstantiationType' => (int)$data->range->id,
                    'idSentence' => $annotationSet->idSentence,
                ]);
                $idTextSpan = Criteria::function("textspan_char_create(?)", [$json]);
                $ts = Criteria::table("textspan")
                    ->where("idTextSpan", $idTextSpan)
                    ->first();
                $json = json_encode([
                    'idAnnotationObject' => $ts->idAnnotationObject,
                    'idEntity' => $data->idEntity,
                    'relationType' => 'rel_annotation',
                    'idUserTask' => $userTask->idUserTask,
                    'idUser' => $idUser
                ]);
                $idAnnotation = Criteria::function("annotation_create(?)", [$json]);
            }
            Timeline::addTimeline("annotation", $idAnnotation, "C");
        });
    }

    public static function deleteLabel(DeleteLabelData $data): void
    {
        DB::transaction(function () use ($data) {
            //debug($data);
            // get Label spans for this idAnnotationSet based on idEntity
            $annotations = Criteria::table("textspan as ts")
                ->join("annotation as a", "ts.idAnnotationObject", "=", "a.idAnnotationObject")
                ->join("layer as l", "ts.idLayer", "=", "l.idLayer")
                ->where("l.idAnnotationSet", $data->idAnnotationSet)
                ->where("a.idEntity", $data->idEntity)
                ->select("a.idAnnotation", "ts.idTextSpan", "l.idLayer")
                ->all();
            //debug($annotations);
            foreach ($annotations as $annotation) {
                Criteria::deleteById("annotation", "idAnnotation", $annotation->idAnnotation);
                Timeline::addTimeline("annotation", $annotation->idAnnotation, "D");
            }
            foreach ($annotations as $annotation) {
                Criteria::deleteById("textspan", "idTextSpan", $annotation->idTextSpan);
            }
            // if FE layer was empty, remove it
            foreach ($annotations as $annotation) {
                $annotationsByLayer = Criteria::table("view_annotation_text_fe")
                    ->where("idLayer", $annotation->idLayer)
                    ->count();
                //debug("count = " . $annotationsByLayer);
                if ($annotationsByLayer == 0) {
                    Criteria::deleteById("layer", "idLayer", $annotation->idLayer);
                }
            }
        });
    }

    public static function createAnnotationSet(CreateASData $data): ?int
    {
        //debug($data);
        $startChar = 4000;
        $endChar = -1;
        foreach ($data->wordList as $word) {
            if ($word->startChar < $startChar) {
                $startChar = $word->startChar;
            }
            if ($word->endChar > $endChar) {
                $endChar = $word->endChar;
            }
        }
        $idAnnotationSet = null;
        if (($startChar != -1) && ($endChar != 4000)) {
            $idAnnotationSet = AnnotationSet::createForLU($data->idDocumentSentence, $data->idLU, $startChar, $endChar);
        }
        return $idAnnotationSet;
    }

    public static function decorateSentenceTarget($text, $spans)
    {
        $decorated = "";
        $ni = "";
        $i = 0;
        foreach ($spans as $span) {
            //$style = 'background-color:#' . $label['rgbBg'] . ';color:#' . $label['rgbFg'] . ';';
            if ($span->startChar >= 0) {
                $decorated .= mb_substr($text, $i, $span->startChar - $i);
                $decorated .= "<span class='color_target'>" . mb_substr($text, $span->startChar, $span->endChar - $span->startChar + 1) . "</span>";
                $i = $span->endChar + 1;
            }
        }
        $decorated = $decorated . mb_substr($text, $i);
        return $decorated;
    }

}

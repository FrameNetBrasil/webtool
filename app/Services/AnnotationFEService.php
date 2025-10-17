<?php

namespace App\Services;

use App\Data\Annotation\_FE\AnnotationData;
use App\Data\Annotation\_FE\CreateASData;
use App\Data\Annotation\_FE\DeleteFEData;
use App\Data\Annotation\_FE\SearchData;
use App\Data\Label\CreateData;
use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\LayerType;
use App\Repositories\LU;
use App\Repositories\Timeline;
use App\Repositories\WordForm;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use Illuminate\Support\Facades\DB;


class AnnotationFEService
{
//    private static function hasTimespan(int $idDocument): bool
//    {
//        $timespan = Criteria::table("view_document_sentence as ds")
//            ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//            ->where("ds.idDocument", $idDocument)
//            ->first();
//        return !is_null($timespan);
//    }
//
//    private static function getRowNumber(int $idDocument, int $idDocumentSentence): int
//    {
//        $sentences = Criteria::table("sentence")
//            ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
//            ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//            ->where("ds.idDocument", $idDocument)
//            ->selectRaw("ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence")
//            ->keyBy("idDocumentSentence")
//            ->all();
//        return $sentences[$idDocumentSentence]->rowNumber;
//    }

//    public static function listSentences(int $idDocument): array
//    {
//        $hasTimespan = self::hasTimespan($idDocument);
//        if ($hasTimespan) {
//            $sentences = Criteria::table("view_sentence as s")
//                ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
//                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
//                ->where("d.idDocument", $idDocument)
//                ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ts.startTime", "ts.endTime")
//                ->orderBy("ts.startTime")
//                ->orderBy("ds.idDocumentSentence")
//                ->limit(1000)
//                ->get()->keyBy("idDocumentSentence")->all();
//        } else {
//            $sentences = Criteria::table("view_sentence as s")
//                ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
//                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
//                ->where("d.idDocument", $idDocument)
//                ->select("s.idSentence", "s.text", "ds.idDocumentSentence")
//                ->orderBy("ds.idDocumentSentence")
//                ->limit(1000)
//                ->get()->keyBy("idDocumentSentence")->all();
//        }
//        if (!empty($sentences)) {
//            $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
//            foreach ($targets as $idDocumentSentence => $spans) {
//                $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
//            }
//        }
//        return $sentences;
//    }

//    public static function getSentence(int $idDocumentSentence): array
//    {
//        $document = Criteria::table("view_document_sentence as ds")
//            ->where("ds.idDocumentSentence", $idDocumentSentence)
//            ->first();
//        $idDocument = $document->idDocument;
//        $hasTimespan = self::hasTimespan($idDocument);
//        if ($hasTimespan) {
//            $sentences = Criteria::table("sentence")
//                ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
//                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
//                ->where("d.idDocument", $idDocument)
//                ->where("ds.idDocumentSentence", $idDocumentSentence)
//                ->select("sentence.idSentence", "sentence.text", "ds.idDocumentSentence", "ts.startTime", "ts.endTime")
//                ->orderBy("ts.startTime")
//                ->orderBy("ds.idDocumentSentence")
//                ->limit(1000)
//                ->get()->keyBy("idDocumentSentence")->all();
//        } else {
//            $sentences = Criteria::table("sentence")
//                ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
//                ->join("document as d", "ds.idDocument", "=", "d.idDocument")
//                ->where("d.idDocument", $idDocument)
//                ->where("ds.idDocumentSentence", $idDocumentSentence)
//                ->select("sentence.idSentence", "sentence.text", "ds.idDocumentSentence")
//                ->orderBy("ds.idDocumentSentence")
//                ->limit(1000)
//                ->get()->keyBy("idDocumentSentence")->all();
//        }
//        if (!empty($sentences)) {
//            $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
//            foreach ($targets as $idDocumentSentence => $spans) {
//                $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
//            }
//        }
//        return $sentences;
//    }


//    public static function getPrevious(int $idDocument, int $idDocumentSentence)
//    {
//        $hasTimespan = self::hasTimespan($idDocument);
//        if ($hasTimespan) {
//            $rowNumber = self::getRowNumber($idDocument, $idDocumentSentence);
//            debug($rowNumber);
//            $sentences = Criteria::table("sentence")
//                ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
//                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//                ->where("ds.idDocument", $idDocument)
//                ->selectRaw("ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence")
//                ->keyBy("rowNumber")
//                ->all();
//            debug($sentences);
//            return isset($sentences[$rowNumber - 1]) ? $sentences[$rowNumber - 1]->idDocumentSentence : null;
//        } else {
//            $i = Criteria::table("view_document_sentence")
//                ->where("idDocument", "=", $idDocument)
//                ->where("idDocumentSentence", "<", $idDocumentSentence)
//                ->max('idDocumentSentence');
//            return $i ?? null;
//        }
//    }
//
//    public static function getNext(int $idDocument, int $idDocumentSentence)
//    {
//        $hasTimespan = self::hasTimespan($idDocument);
//        if ($hasTimespan) {
//            $rowNumber = self::getRowNumber($idDocument, $idDocumentSentence);
//            debug($rowNumber);
//            $sentences = Criteria::table("sentence")
//                ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
//                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
//                ->where("ds.idDocument", $idDocument)
//                ->selectRaw("ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence")
//                ->keyBy("rowNumber")
//                ->all();
//            return isset($sentences[$rowNumber + 1]) ? $sentences[$rowNumber + 1]->idDocumentSentence : null;
//        } else {
//            $i = Criteria::table("view_document_sentence")
//                ->where("idDocument", "=", $idDocument)
//                ->where("idDocumentSentence", ">", $idDocumentSentence)
//                ->min('idDocumentSentence');
//            return $i ?? null;
//        }
//    }

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

        // previous/next

        $hasTimespan = BrowseService::hasTimespan($sentence->idDocument);
        if ($hasTimespan) {
            $rowNumber = BrowseService::getRowNumber($sentence->idDocument, $idDocumentSentence);
            $sentences = Criteria::table("sentence")
                ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
                ->join("view_sentence_timespan as ts", "ds.idSentence", "=", "ts.idSentence")
                ->where("ds.idDocument", $sentence->idDocument)
                ->selectRaw("ROW_NUMBER() OVER (order by `ts`.`startTime` asc, `ds`.`idDocumentSentence` asc) AS `rowNumber`, ds.idDocumentSentence")
                ->keyBy("rowNumber")
                ->all();
            $idPrevious = isset($sentences[$rowNumber - 1]) ? $sentences[$rowNumber - 1]->idDocumentSentence : null;
            $idNext = isset($sentences[$rowNumber + 1]) ? $sentences[$rowNumber + 1]->idDocumentSentence : null;
        } else {
            $i = Criteria::table("view_document_sentence")
                ->where("idDocument", "=", $sentence->idDocument)
                ->where("idDocumentSentence", "<", $idDocumentSentence)
                ->max('idDocumentSentence');
            $idPrevious = $i ?? null;
            $i = Criteria::table("view_document_sentence")
                ->where("idDocument", "=", $sentence->idDocument)
                ->where("idDocumentSentence", ">", $idDocumentSentence)
                ->min('idDocumentSentence');
            $idNext = $i ?? null;
        }

        //

        return [
            'idDocumentSentence' => $idDocumentSentence,
            'idPrevious' => $idPrevious,//self::getPrevious($sentence->idDocument, $idDocumentSentence),
            'idNext' => $idNext,//self::getNext($sentence->idDocument, $idDocumentSentence),
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
        $words = $wordsChars->words;
        $wordsByChar = [];
        foreach ($words as $word) {
            $wordsByChar[$word['startChar']] = $word;
        }
//        debug($wordsChars->chars);
//        debug($wordsByChar);
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
        $lu = Criteria::byFilter("view_lu_full", ['idLU', '=', $as->idLU])->first();
        $lu->frame = Frame::byId($lu->idFrame);
        $alternativeLU = Criteria::table("view_lu as lu1")
            ->join("view_lu as lu2", "lu1.idLemma", "=", "lu2.idLemma")
            ->where("lu2.idLU", $lu->idLU)
            ->where("lu1.idLU", "<>", $lu->idLU)
            ->select("lu1.frameName", "lu1.name as lu")
            ->all();
        $fes = Criteria::table("view_frameelement")
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->where("idFrame", $lu->idFrame)
            ->keyBy("idEntity")
            ->all();

        $fesByType = [
            'Core' => [],
            'Peripheral' => [],
            'Extra-thematic' => [],
        ];
        foreach ($fes as $fe) {
            if (($fe->coreType == 'cty_core') || ($fe->coreType == 'cty_core-unexpressed')) {
                $fesByType['Core'][] = $fe;
            } elseif ($fe->coreType == 'cty_peripheral') {
                $fesByType['Peripheral'][] = $fe;
            } else {
                $fesByType['Extra-thematic'][] = $fe;
            }
        }

        $firstWord = array_key_first($wordsChars->words);
        $lastWord = array_key_last($wordsChars->words);

        $spans = [];
        $idLayers = [];
        $layersForLU = LayerType::listToLU($lu);
        foreach ($layersForLU as $layer) {
            if ($layer->entry == 'lty_fe') {
                $idLayers[] = $layer->idLayerType;
                for ($i = $firstWord; $i <= $lastWord; $i++) {
                    $spans[$i][$layer->idLayerType] = [];
                }
            }
        }

        $layers = AnnotationSet::getLayers($idAS);
        $spansByLayer = collect($layers)->groupBy('layerTypeEntry')->all();
//        debug($spansByLayer);
        $nis = [];
        $target = $spansByLayer['lty_target'];
        foreach ($target as $tg) {
            $tg->startWord = $wordsChars->chars[$tg->startChar]['order'];
            $tg->endWord = $wordsChars->chars[$tg->endChar]['order'];
        }
        /*
        $feSpans = $spansByLayer['lty_fe'] ?? [];
        foreach ($feSpans as $span) {
            $span->startWord = ($span->startChar != -1) ? $wordsChars->chars[$span->startChar]['order'] : -1;
            $span->endWord = ($span->endChar != -1) ? $wordsChars->chars[$span->endChar]['order'] : -1;
            if ($span->startWord != -1) {
                $hasLabel = false;
                for ($i = $span->startWord; $i <= $span->endWord; $i++) {
                    $name = (!$hasLabel) ? $span->name : null;
                    $spans[$i][$span->idLayerType] = [
                        'idEntityFE' => $span->idEntity,
                        'label' => $name
                    ];
                    $wordsChars->words[$i]['hasFE'] = true;
                    $hasLabel = true;
                }
            } else {
                $name = $span->name;
                $nis[$span->idInstantiationType][$span->idEntity] = [
                    'idEntityFE' => $span->idEntity,
                    'label' => $name
                ];
            }
        }
        */
        $objects = $spansByLayer['lty_fe'] ?? [];
        foreach ($objects as $layerEntry => $object) {
            $object->startWord = ($object->startChar != -1) ? $wordsChars->chars[$object->startChar]['order'] : -1;
            $object->endWord = ($object->endChar != -1) ? $wordsChars->chars[$object->endChar]['order'] : -1;
        }
        $objectsRows = [];
        $objectsRowsEnd = [];
        $idLayerTypeCurrent = -1;
        $idLayerType = 0;
        foreach ($objects as $object) {
            if ($object->startWord != -1) {
                if ($object->idLayerType != $idLayerTypeCurrent) {
                    $idLayerTypeCurrent = $object->idLayerType;
                    $objectsRows[$idLayerType][0][] = $object;
                    $objectsRowsEnd[$idLayerType][0] = $object->endWord;
                } else {
                    $allocated = false;
                    foreach ($objectsRows[$idLayerType] as $idLayer => $objectRow) {
                        if ($object->startWord > $objectsRowsEnd[$idLayerType][$idLayer]) {
                            $objectsRows[$idLayerType][$idLayer][] = $object;
                            $objectsRowsEnd[$idLayerType][$idLayer] = $object->endWord;
                            $allocated = true;
                            break;
                        }
                    }
                    if (!$allocated) {
                        $idLayer = count($objectsRows[$idLayerType]);
                        $objectsRows[$idLayerType][$idLayer][] = $object;
                        $objectsRowsEnd[$idLayerType][$idLayer] = $object->endWord;
                    }
                }
            }
        }
        debug($objectsRows);


//        $target = array_filter($layers, fn($x) => ($x->layerTypeEntry == 'lty_target'));
//        foreach ($target as $tg) {
//            $tg->startWord = $wordsChars->chars[$tg->startChar]['order'];
//            $tg->endWord = $wordsChars->chars[$tg->endChar]['order'];
//        }
//
//
//        $feSpans = array_filter($layers, fn($x) => $x->layerTypeEntry == 'lty_fe');

//        $nis = [];
//        $idLayers = [];
//        $spansByLayer = collect($feSpans)->groupBy('idLayer')->all();
//        foreach ($spansByLayer as $idLayer => $existingSpans) {
//            $idLayers[] = $idLayer;
//            for ($i = $firstWord; $i <= $lastWord; $i++) {
//                $spans[$i][$idLayer] = null;
//            }
//            foreach ($existingSpans as $span) {
//                if ($span->idTextSpan != '') {
//                    $span->startWord = ($span->startChar != -1) ? $wordsChars->chars[$span->startChar]['order'] : -1;
//                    $span->endWord = ($span->endChar != -1) ? $wordsChars->chars[$span->endChar]['order'] : -1;
//                    if ($span->layerTypeEntry == 'lty_fe') {
//                        if ($span->startWord != -1) {
//                            $hasLabel = false;
//                            for ($i = $span->startWord; $i <= $span->endWord; $i++) {
//                                $name = (!$hasLabel) ? $fes[$span->idEntity]->name : null;
//                                $spans[$i][$idLayer] = [
//                                    'idEntityFE' => $span->idEntity,
//                                    'label' => $name
//                                ];
//                                $wordsChars->words[$i]['hasFE'] = true;
//                                $hasLabel = true;
//                            }
//                        } else {
//                            $name = $fes[$span->idEntity]->name;
//                            $nis[$span->idInstantiationType][$span->idEntity] = [
//                                'idEntityFE' => $span->idEntity,
//                                'label' => $name
//                            ];
//                        }
//                    }
//                }
//            }
//        }
        //debug($baseLabels, $labels);
//        ksort($spans);
//        debug($labels);
//        debug($it);
//        debug($nis);
//        debug( $wordsChars->words);
//        debug($spans);
        return [
            'it' => $it,
            'idLayers' => $idLayers,
            'words' => $wordsChars->words,
            'idAnnotationSet' => $idAS,
            'lu' => $lu,
            'alternativeLU' => $alternativeLU,
            'target' => $target[0],
            'spans' => $spans,
            'fes' => $fes,
            'fesByType' => $fesByType,
            'nis' => $nis,
            'word' => $token,
            'comment' => CommentService::getAnnotationSetComment($idAS),
        ];

    }

    /**
     * @throws \Exception
     */
    public static function annotateFE(AnnotationData $data): array
    {
        DB::transaction(function () use ($data) {
            $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $data->idAnnotationSet);
            $fe = FrameElement::byId($data->idFrameElement);
            $idLayerTypeFE = Criteria::byId("layertype", "entry", "lty_fe")->idLayerType;
            if ($data->range->type == 'word') {
                $it = Criteria::table("view_instantiationtype")
                    ->where('entry', 'int_normal')
                    ->first();
                $idInstantiationType = $it->idInstantiationType;
                $startChar = (int)$data->range->start;
                $endChar = (int)$data->range->end;
            } else if ($data->range->type == 'ni') {
                $idInstantiationType = (int)$data->range->id;
                $startChar = -1;
                $endChar = -1;
            }
            $data = json_encode([
                'startChar' => $startChar,
                'endChar' => $endChar,
                'multi' => 0,
                'idLayerType' => $idLayerTypeFE,
                'idAnnotationSet' => $data->idAnnotationSet,
                'idInstantiationType' => $idInstantiationType,
                'idSentence' => $annotationSet->idSentence,
            ]);
            $idTextSpan = Criteria::function("textspan_char_create(?)", [$data]);
            $data = json_encode([
                'idTextSpan' => $idTextSpan,
                'idEntity' => $fe->idEntity,
                'relationType' => 'rel_annotation',
                'idUser' => AppService::getCurrentIdUser()
            ]);
            $idAnnotation = Criteria::function("annotation_create(?)", [$data]);
            Timeline::addTimeline("annotation", $idAnnotation, "C");
        });
        return CorpusService::getAnnotationSetData($data->idAnnotationSet, $data->token);
    }

    public static function deleteFE(DeleteFEData $data): void
    {
        DB::transaction(function () use ($data) {
            // get FE spans for this idAnnotationSet
            $annotations = Criteria::table("view_annotation_text_fe")
                ->where("idAnnotationSet", $data->idAnnotationSet)
                ->where("idFrameElement", $data->idFrameElement)
                ->where("layerTypeEntry", "lty_fe")
                ->where("idLanguage", AppService::getCurrentIdLanguage())
                ->select("idAnnotation", "idTextSpan", "idLayer")
                ->all();
            // Ao invés de remover fisicamente a anotaçao, apenas marca como "DELETED' e mantem o textSpan
            foreach ($annotations as $annotation) {
                Criteria::table("annotation")
                    ->where("idAnnotation", $annotation->idAnnotation)
                    ->update(["status" => 'DELETED']);
                Timeline::addTimeline('annotation', $annotation->idAnnotation, 'D');
            }
//            foreach ($annotations as $annotation) {
//                Criteria::deleteById("annotation", "idAnnotation", $annotation->idAnnotation);
//            }
//            foreach ($annotations as $annotation) {
//                Criteria::deleteById("textspan", "idTextSpan", $annotation->idTextSpan);
//            }
            // if FE layer was empty, remove it
//            foreach ($annotations as $annotation) {
//                $annotationsByLayer = Criteria::table("view_annotation_text_fe")
//                    ->where("idLayer", $annotation->idLayer)
//                    ->count();
//                if ($annotationsByLayer == 0) {
//                    Criteria::deleteById("layer", "idLayer", $annotation->idLayer);
//                }
//            }
        });
    }

    public static function createAnnotationSet(CreateASData $data): ?int
    {
        debug($data);
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

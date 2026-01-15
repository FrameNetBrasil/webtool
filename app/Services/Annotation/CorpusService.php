<?php

namespace App\Services\Annotation;

use App\Data\Annotation\Corpus\AnnotationData;
use App\Data\Annotation\Corpus\CreateASData;
use App\Data\Annotation\Corpus\DeleteObjectData;
use App\Database\Criteria;
use App\Enum\AnnotationType;
use App\Enum\Status;
use App\Repositories\AnnotationSet;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\Frame;
use App\Repositories\LayerType;
use App\Repositories\LU;
use App\Repositories\Timeline;
use App\Repositories\WordForm;
use App\Services\AppService;
use App\Services\CommentService;
use Illuminate\Support\Facades\DB;

class CorpusService
{
    public static function getResourceDataByIdSentence(int $idSentence, ?int $idAnnotationSet = null, string $corpusAnnotationType = 'fe'): array
    {
        $sentence = Criteria::table('view_sentence as s')
            ->where('s.idSentence', $idSentence)
            ->select('s.idSentence', 's.text')
            ->first();
        $words = self::getWordsByIdSentence($sentence);
        foreach ($words as $i => $word) {
            if (! $word['hasLU']) {
                $words[$i]['hasLU'] = WordForm::wordHasLU($word['word']);
            }
        }

        // tokens
        $tokens = [];
        $word = '';
        foreach ($words as $i => $token) {
            $tokens[$i] = $token;
            if ($token['idAS'] == $idAnnotationSet) {
                $word = $token['word'];
            }
        }

        return [
            'idSentence' => $idSentence,
            'sentence' => $sentence,
            'text' => $sentence->text,
            'tokens' => $tokens,
            'idAnnotationSet' => $idAnnotationSet,
            'word' => $word,
            'corpusAnnotationType' => $corpusAnnotationType,
        ];

    }

    public static function getResourceData(int $idDocumentSentence, ?int $idAnnotationSet = null, string $corpusAnnotationType = 'fe'): array
    {
        $sentence = Criteria::table('view_sentence as s')
            ->join('document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
            ->where('ds.idDocumentSentence', $idDocumentSentence)
            ->select('s.idSentence', 's.text', 'ds.idDocumentSentence', 'ds.idDocument')
            ->first();
        $words = self::getWordsByIdDocumentSentence($sentence);
        foreach ($words as $i => $word) {
            if (! $word['hasLU']) {
                $words[$i]['hasLU'] = WordForm::wordHasLU($word['word']);
            }
        }
        //        debug($words);
        $document = Document::byId($sentence->idDocument);
        $corpus = Corpus::byId($document->idCorpus);

        // previous/next
        $previousNext = BrowseService::getPreviousNext($idDocumentSentence);

        // tokens
        $tokens = [];
        $word = '';
        foreach ($words as $i => $token) {
            $tokens[$i] = $token;
            if ($token['idAS'] == $idAnnotationSet) {
                $word = $token['word'];
            }
        }

        return [
            'idDocumentSentence' => $idDocumentSentence,
            'idPrevious' => $previousNext->previous,
            'idNext' => $previousNext->next,
            'corpus' => $corpus,
            'document' => $document,
            'sentence' => $sentence,
            'text' => $sentence->text,
            'tokens' => $tokens,
            'idAnnotationSet' => $idAnnotationSet,
            'word' => $word,
            'corpusAnnotationType' => $corpusAnnotationType,
        ];

    }

    public static function getAnnotationSetData(int $idAnnotationSet, string $token = '', string $corpusAnnotationType = 'fe'): array
    {
        $it = Criteria::table('view_instantiationtype')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->all();
        $as = Criteria::table('view_annotationset')
            ->where('idAnnotationSet', $idAnnotationSet)
            ->first();
        $sentence = Criteria::table('view_sentence as s')
            ->join('view_document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
            ->where('ds.idDocumentSentence', $as->idDocumentSentence)
            ->select('s.idSentence', 's.text', 'ds.idDocumentSentence', 'ds.idDocument')
            ->first();
        if (is_null($sentence)) {
            return [
                'idAnnotationSet' => null,
            ];
        }
        $wordsChars = AnnotationSet::getWordsChars($sentence->text);
        foreach ($wordsChars->words as $i => $word) {
            $wordsChars->words[$i]['hasFE'] = false;
        }
        $lu = Criteria::byFilter('view_lu_full', ['idLU', '=', $as->idLU])->first();
        $lu->frame = Frame::byId($lu->idFrame);
        $lu->idUDPOS = LU::getidUDPOS($lu->idLemma);
        $alternativeLU = Criteria::table('view_lu as lu1')
            ->join('view_lu as lu2', 'lu1.idLemma', '=', 'lu2.idLemma')
            ->where('lu2.idLU', $lu->idLU)
            ->where('lu1.idLU', '<>', $lu->idLU)
            ->select('lu1.idLU', 'lu1.frameName', 'lu1.name as lu')
            ->all();
        $fes = Criteria::table('view_frameelement')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->where('idFrame', $lu->idFrame)
            ->where('coreType', '<>', 'cty_target')
            ->keyBy('idEntity')
            ->all();

        $fesByType = [
            'Core' => [],
            'Peripheral' => [],
            'Extra-thematic' => [],
        ];
        foreach ($fes as $fe) {
            if (($fe->coreType == 'cty_core') || ($fe->coreType == 'cty_core-unexpressed')) {
                $fesByType['Core'][] = $fe->idEntity;
            } elseif ($fe->coreType == 'cty_peripheral') {
                $fesByType['Peripheral'][] = $fe->idEntity;
            } else {
                $fesByType['Extra-thematic'][] = $fe->idEntity;
            }
        }

        $matrixData = self::getLayersByAnnotationSet($idAnnotationSet, $wordsChars);
        $matrixConfig = self::getMatrixConfig($matrixData);
        // $groupedLayers = self::groupLayersByName($matrixData);

        $firstWord = array_key_first($wordsChars->words);
        $lastWord = array_key_last($wordsChars->words);

        //        $spans = [];
        //        $idLayers = [];
        $layersForLU = collect(LayerType::listToLU($lu))->keyBy('entry')->toArray();

        $glsByLayerType = Criteria::table('view_layertype_gl')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->whereIN('entry', array_keys($layersForLU))
            ->select('entry', 'idEntityGenericLabel as idEntity', 'name', 'idColor')
            ->orderby('layerOrder')
            ->get()->groupBy('entry')->toArray();
        $asStatus = AnnotationSet::updateStatus($as, $matrixData, $fesByType['Core']);

        return [
            'it' => $it,
            'layers' => $layersForLU,
            'words' => $wordsChars->words,
            'idAnnotationSet' => $idAnnotationSet,
            'annotationSetStatus' => $asStatus,
            'annotationSet' => $as,
            'lu' => $lu,
            'alternativeLU' => $alternativeLU,
            //            'target' => $target[0],
            //            'spans' => $spans,
            'fes' => $fes,
            'fesByType' => $fesByType,
            'glsByLayerType' => $glsByLayerType,
            //            'nis' => $nis,
            'word' => $token,
            'matrix' => [
                //                'data' => $matrixData,
                'config' => $matrixConfig,
            ],
            'groupedLayers' => $matrixData,
            'corpusAnnotationType' => $corpusAnnotationType,
            'comment' => CommentService::getComment($idAnnotationSet, $sentence->idDocument, AnnotationType::ANNOTATIONSET->value),
        ];

    }

    public static function getLayersByAnnotationSet(int $idAnnotationSet, object $wordsChars): array
    {
        $layers = AnnotationSet::getLayers($idAnnotationSet);
        $spansByLayer = collect($layers)->groupBy('layerTypeEntry')->all();
        //        $objects = $spansByLayer['lty_fe'] ?? [];
        //        foreach ($spansByLayer as $objects) {
        //            foreach($objects as $object) {
        //                $object->startWord = ($object->startChar != -1) ? $wordsChars->chars[$object->startChar]['order'] : -1;
        //                $object->endWord = ($object->endChar != -1) ? $wordsChars->chars[$object->endChar]['order'] : -1;
        //            }
        //        }
        $objectsRows = [];
        $objectsRowsEnd = [];
        foreach ($spansByLayer as $objects) {
            $first = true;
            foreach ($objects as $object) {
                $object->startWord = ($object->startChar != -1) ? $wordsChars->chars[$object->startChar]['order'] : -1;
                $object->endWord = ($object->endChar != -1) ? $wordsChars->chars[$object->endChar]['order'] : -1;
                if ($object->startWord != -1) {
                    if ($first) {
                        $objectsRows[$object->layerTypeEntry][0][] = $object;
                        $objectsRowsEnd[$object->layerTypeEntry][0] = $object->endWord;
                        $first = false;
                    } else {
                        $allocated = false;
                        foreach ($objectsRows[$object->layerTypeEntry] as $idRow => $objectRow) {
                            if ($object->startWord > $objectsRowsEnd[$object->layerTypeEntry][$idRow]) {
                                $objectsRows[$object->layerTypeEntry][$idRow][] = $object;
                                $objectsRowsEnd[$object->layerTypeEntry][$idRow] = $object->endWord;
                                $allocated = true;
                                break;
                            }
                        }
                        if (! $allocated) {
                            $idRow = count($objectsRows[$object->layerTypeEntry]);
                            $objectsRows[$object->layerTypeEntry][$idRow][] = $object;
                            $objectsRowsEnd[$object->layerTypeEntry][$idRow] = $object->endWord;
                        }
                    }
                } else {
                    $objectsRows['nis'][$object->idInstantiationType][] = $object;
                }
            }
        }

        //        $result = [];
        //        debug($objectsRows);
        //        foreach ($objectsRows as $layerTypeEntry => $rows) {
        //            foreach ($rows as $idRow => $objects) {
        //                $result[] = [
        //                    'layer' => $layerTypeEntry,
        //                    'objects' => $objects,
        //                ];
        //            }
        //        }
        //
        //        return $result;
        return $objectsRows;
    }

    public static function getMatrixConfig($matrixData): array
    {
        $minChar = PHP_INT_MAX;
        $maxChar = PHP_INT_MIN;

        foreach ($matrixData as $layerTypeEntry => $rows) {

            foreach ($rows as $row) {
                foreach ($row as $object) {
                    $minChar = min($minChar, $object->startChar);
                    $maxChar = max($maxChar, $object->endChar);
                }
            }
        }

        // Add padding
        //        $minFrame = max(0, $minFrame - 100);
        //        $maxFrame = $maxFrame + 100;

        return ['minChar' => $minChar,
            'maxChar' => $maxChar,
            'charToPixel' => 8,
            'minObjectWidth' => 16,
            'objectHeight' => 24,
            'labelWidth' => 150,
            'matrixWidth' => ($maxChar - $minChar) * 1,
            'matrixHeight' => (24 * count($matrixData)) + 10, ];
    }

    private static function groupLayersByName($matrixData): array
    {
        $layerGroups = [];

        foreach ($matrixData as $idRow => $layer) {
            $layerName = $layer['layer'];

            if (! isset($layerGroups[$layerName])) {
                $layerGroups[$layerName] = [];
            }

            $layerGroups[$layerName][$idRow] = $layer['objects'];
        }

        return $layerGroups;
    }

    public static function getWordsByIdSentence(object $sentence)
    {
        $targets = AnnotationSet::getTargetsByIdSentence($sentence->idSentence);

        return self::getWords($targets, $sentence->text);
    }

    public static function getWordsByIdDocumentSentence(object $sentence)
    {
        $targets = AnnotationSet::getTargets($sentence->idDocumentSentence);

        return self::getWords($targets, $sentence->text);
    }

    public static function getWords(array $targets, string $text): array
    {
        debug($text);
        $wordsChars = AnnotationSet::getWordsChars($text);
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
                'word' => mb_substr($text, $target->startChar, ($target->endChar - $target->startChar + 1)),
                'startChar' => $target->startChar,
                'endChar' => $target->endChar,
                'hasLU' => true,
                'idAS' => $target->idAnnotationSet,
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
                    'hasLU' => false,
                    'idAS' => -1,
                ];
                $nextChar = $wordsByChar[$nextChar]['endChar'] + 1;
            }
        }

        return $wordList;
    }

    public static function getLUs(int $idDocumentSentence, int $idWord): array
    {
        $sentence = Criteria::table('view_sentence as s')
            ->join('document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
            ->where('ds.idDocumentSentence', $idDocumentSentence)
            ->select('s.idSentence', 's.text', 'ds.idDocumentSentence', 'ds.idDocument')
            ->first();
        $words = self::getWordsByIdSentence($sentence);
        $wordsToShow = [];
        for ($i = $idWord - 10; $i <= $idWord + 10; $i++) {
            if (isset($words[$i])) {
                if ($words[$i]['idAS'] != -1) {
                    if ($i >= $idWord) {
                        break;
                    } else {
                        $wordsToShow = [];

                        continue;
                    }
                }
                if ($words[$i]['word'] != ' ') {
                    $wordsToShow[$i] = $words[$i];
                }
            }
        }

        return [
            'lus' => WordForm::getLUs($words[$idWord]['word']),
            'words' => $wordsToShow,
            'idWord' => $idWord,
            'idDocumentSentence' => $idDocumentSentence,
        ];
    }

    public static function createAnnotationSet(CreateASData $data): ?int
    {
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

    public static function annotateObject(AnnotationData $object): array
    {
        $annotationSet = Criteria::byId('view_annotationset', 'idAnnotationSet', $object->idAnnotationSet);
        $idUser = AppService::getCurrentIdUser();
        if ($object->corpusAnnotationType != 'flex') {
            if (! SessionService::isActive($annotationSet->idDocumentSentence, $idUser)) {
                throw new \Exception('The annotation session is not active.');
            }
        }
        DB::transaction(function () use ($object, $annotationSet) {
            // no caso do corpus annotation, o objeto pode ser um FE ou um GL
            $fe = Criteria::byId('frameelement', 'idEntity', $object->idEntity);
            $idLayerType = Criteria::byId('layertype', 'entry', 'lty_fe')->idLayerType;
            if (is_null($fe)) {
                //                $idLayerType = Criteria::table("view_layertype_gl")
                //                    ->where("idEntityGenericLabel", $object->idEntity)
                //                    ->first()->idLayerType;
                $idLayerType = Criteria::table('genericlabel')
                    ->where('idEntity', $object->idEntity)
                    ->first()->idLayerType;
            }
            if ($object->range->type == 'word') {
                $it = Criteria::table('view_instantiationtype')
                    ->where('entry', 'int_normal')
                    ->first();
                $idInstantiationType = $it->idInstantiationType;
                $startChar = (int) $object->range->start;
                $endChar = (int) $object->range->end;
            } elseif ($object->range->type == 'ni') {
                $idInstantiationType = (int) $object->range->id;
                $startChar = -1;
                $endChar = -1;
            }
            $data = json_encode([
                'startChar' => $startChar,
                'endChar' => $endChar,
                'multi' => 0,
                'idLayerType' => $idLayerType,
                'idAnnotationSet' => $object->idAnnotationSet,
                'idInstantiationType' => $idInstantiationType,
                'idSentence' => $annotationSet->idSentence,
            ]);
            $idTextSpan = Criteria::function('textspan_char_create(?)', [$data]);
            $data = json_encode([
                'idTextSpan' => $idTextSpan,
                'idEntity' => $object->idEntity,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$data]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
            AnnotationSet::updateStatusField($object->idAnnotationSet, Status::UPDATED->value);
        });
        if ($object->corpusAnnotationType == 'flex') {
            return FlexService::getAnnotationData($annotationSet->idDocumentSentence);
        } else {
            return CorpusService::getAnnotationSetData($object->idAnnotationSet, $object->token);
        }
    }

    public static function deleteObject(DeleteObjectData $object): void
    {
        $annotationSet = Criteria::byId('view_annotationset', 'idAnnotationSet', $object->idAnnotationSet);
        $idUser = AppService::getCurrentIdUser();
        if ($object->corpusAnnotationType != 'flex') {
            if (! SessionService::isActive($annotationSet->idDocumentSentence, $idUser)) {
                throw new \Exception('The annotation session is not active.');
            }
        }
        DB::transaction(function () use ($object) {
            $fe = Criteria::byId('frameelement', 'idEntity', $object->idEntity);
            $table = 'view_annotation_text_fe';
            $idLayerType = Criteria::byId('layertype', 'entry', 'lty_fe')->idLayerType;
            if (is_null($fe)) {
                $table = 'view_annotation_text_gl';
                //                $idLayerType = Criteria::table("view_layertype_gl")
                //                    ->where("idEntityGenericLabel", $object->idEntity)
                //                    ->first()->idLayerType;
                $idLayerType = Criteria::table('genericlabel')
                    ->where('idEntity', $object->idEntity)
                    ->first()->idLayerType;
            }
            $annotations = Criteria::table($table)
                ->where('idAnnotationSet', $object->idAnnotationSet)
                ->where('idEntity', $object->idEntity)
                ->where('idLayerType', $idLayerType)
                ->where('idLanguage', AppService::getCurrentIdLanguage())
                ->select('idAnnotation')
                ->all();
            debug($annotations);
            // Ao invés de remover fisicamente a anotaçao, apenas marca como "DELETED' e mantem o textSpan
            foreach ($annotations as $annotation) {
                Criteria::table('annotation')
                    ->where('idAnnotation', $annotation->idAnnotation)
                    ->update(['status' => 'DELETED']);
                Timeline::addTimeline('annotation', $annotation->idAnnotation, 'D');
            }
            AnnotationSet::updateStatusField($object->idAnnotationSet, Status::UPDATED->value);
        });
    }
}

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

class FlexService
{
    public static function getAnnotationData(int $idDocumentSentence): array
    {
        // first - check the annotationSet for this idDocumentSentence was created; if notm create it.
        $as = Criteria::table("view_annotationset")
            ->where('idDocumentSentence', $idDocumentSentence)
            ->first();
        if (is_null($as)) {
            $as = AnnotationSet::createForFlex($idDocumentSentence);
        }
        $sentence = Criteria::table("view_sentence as s")
            ->join("view_document_sentence as ds", "s.idSentence", "=", "ds.idSentence")
            ->where("ds.idDocumentSentence", $as->idDocumentSentence)
            ->select("s.idSentence", "s.text", "ds.idDocumentSentence", "ds.idDocument")
            ->first();
        if (is_null($sentence)) {
            return [
                'idAnnotationSet' => null
            ];
        }
        $wordsChars = AnnotationSet::getWordsChars($sentence->text);
        foreach ($wordsChars->words as $i => $word) {
            $wordsChars->words[$i]['hasFE'] = false;
        }

        $matrixData = CorpusService::getLayersByAnnotationSet($as->idAnnotationSet, $wordsChars);
        $matrixConfig = CorpusService::getMatrixConfig($matrixData);
        debug ($matrixData);

        $layersForFlex = collect(LayerType::listToFlex())->keyBy("entry")->toArray();

        $layerTypes = ['lty_phrasal_ce','lty_clausal_ce','lty_sentential_ce'];
        $glsByLayerType = Criteria::table("layertype as lt")
            ->join("genericlabel as gl", "gl.idLayerType", "=", "lt.idLayerType")
            ->where('gl.idLanguage', AppService::getCurrentIdLanguage())
            ->whereIN("lt.entry", $layerTypes)
            ->select("lt.entry", "gl.idEntity", "gl.name", "gl.idColor")
            ->orderby("lt.layerOrder")
            ->get()->groupBy("entry")->toArray();

        return [
            'layers' => $layersForFlex,
            'words' => $wordsChars->words,
            'idAnnotationSet' => $as->idAnnotationSet,
            'annotationSet' => $as,
            'glsByLayerType' => $glsByLayerType,
            'matrix' => [
                'config' => $matrixConfig,
            ],
            'groupedLayers' => $matrixData,
            'corpusAnnotationType' => 'flex',
        ];

    }

    public static function getLayersByAnnotation(int $idDocumentSentence, object $wordsChars): array
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
                        if (!$allocated) {
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

    private static function getMatrixConfig($matrixData): array
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
            'matrixHeight' => (24 * count($matrixData)) + 10,];
    }

    private static function groupLayersByName($matrixData): array
    {
        $layerGroups = [];

        foreach ($matrixData as $idRow => $layer) {
            $layerName = $layer['layer'];

            if (!isset($layerGroups[$layerName])) {
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

    public
    static function createAnnotationSet(CreateASData $data): ?int
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
        $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $object->idAnnotationSet);
        $idUser = AppService::getCurrentIdUser();
        if (!SessionService::isActive($annotationSet->idDocumentSentence, $idUser)) {
            throw new \Exception("The annotation session is not active.");
        }
        DB::transaction(function () use ($object, $annotationSet) {
            // no caso do corpus annotation, o objeto pode ser um FE ou um GL
            $fe = Criteria::byId("frameelement", "idEntity", $object->idEntity);
            $idLayerType = Criteria::byId("layertype", "entry", "lty_fe")->idLayerType;
            if (is_null($fe)) {
                $idLayerType = Criteria::table("view_layertype_gl")
                    ->where("idEntityGenericLabel", $object->idEntity)
                    ->first()->idLayerType;
            }
            if ($object->range->type == 'word') {
                $it = Criteria::table("view_instantiationtype")
                    ->where('entry', 'int_normal')
                    ->first();
                $idInstantiationType = $it->idInstantiationType;
                $startChar = (int)$object->range->start;
                $endChar = (int)$object->range->end;
            } else if ($object->range->type == 'ni') {
                $idInstantiationType = (int)$object->range->id;
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
            $idTextSpan = Criteria::function("textspan_char_create(?)", [$data]);
            $data = json_encode([
                'idTextSpan' => $idTextSpan,
                'idEntity' => $object->idEntity,
                'idUser' => AppService::getCurrentIdUser()
            ]);
            $idAnnotation = Criteria::function("annotation_create(?)", [$data]);
            Timeline::addTimeline("annotation", $idAnnotation, "C");
            AnnotationSet::updateStatusField($object->idAnnotationSet, Status::UPDATED->value);
        });
        return FlexService::getAnnotationSetData($object->idAnnotationSet, $object->token);
    }

    public static function deleteObject(DeleteObjectData $object): void
    {
        $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $object->idAnnotationSet);
        $idUser = AppService::getCurrentIdUser();
        if (!SessionService::isActive($annotationSet->idDocumentSentence, $idUser)) {
            throw new \Exception("The annotation session is not active.");
        }
        DB::transaction(function () use ($object) {
            $fe = Criteria::byId("frameelement", "idEntity", $object->idEntity);
            $table = "view_annotation_text_fe";
            $idLayerType = Criteria::byId("layertype", "entry", "lty_fe")->idLayerType;
            if (is_null($fe)) {
                $table = "view_annotation_text_gl";
                $idLayerType = Criteria::table("view_layertype_gl")
                    ->where("idEntityGenericLabel", $object->idEntity)
                    ->first()->idLayerType;
            }
            $annotations = Criteria::table($table)
                ->where("idAnnotationSet", $object->idAnnotationSet)
                ->where("idEntity", $object->idEntity)
                ->where("idLayerType", $idLayerType)
                ->where("idLanguage", AppService::getCurrentIdLanguage())
                ->select("idAnnotation")
                ->all();
            // Ao invés de remover fisicamente a anotaçao, apenas marca como "DELETED' e mantem o textSpan
            foreach ($annotations as $annotation) {
                Criteria::table("annotation")
                    ->where("idAnnotation", $annotation->idAnnotation)
                    ->update(["status" => 'DELETED']);
                Timeline::addTimeline('annotation', $annotation->idAnnotation, 'D');
            }
            AnnotationSet::updateStatusField($object->idAnnotationSet, Status::UPDATED->value);
        });
    }

}

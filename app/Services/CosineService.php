<?php

namespace App\Services;

use App\Database\Criteria;

class CosineService
{
    private static $weigths;

    private static $frames;

    private static $processed;

    private static function init(): void
    {
        self::$weigths = [
            'rel_perspective_on' => 0.9,
            'rel_inheritance' => 0.9,
            'rel_using' => 0.8,
            'rel_see_also' => 0,
            'rel_subframe' => 0.85,
            'rel_causative_of' => 0.7,
            'rel_inchoative_of' => 0.7,
            'rel_metaphorical_projection' => 0,
            'rel_precedes' => 0.7,
        ];
        self::$frames = [];
    }

    private static function createFrameLinks(int $idFrameSource): void
    {
        if (! isset(self::$frames[$idFrameSource])) {
            $idNodeSource = Criteria::byId('cosine_node', 'idFrame', $idFrameSource)->idCosineNode;
            self::$frames[$idFrameSource] = $idNodeSource;
            $relations = Criteria::table('view_frame_relation')
                ->where('f2IdFrame', $idFrameSource)
                ->where('idLanguage', AppService::getCurrentIdLanguage())
                ->all();
            foreach ($relations as $relation) {
                if (self::$weigths[$relation->relationType] > 0) {
                    if (isset(self::$frames[$relation->f1IdFrame])) {
                        $idNodeTarget = self::$frames[$relation->f1IdFrame];
                    } else {
                        $idNodeTarget = Criteria::byId('cosine_node', 'idFrame', $relation->f1IdFrame)->idCosineNode;
                    }
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idNodeSource,
                        'idCosineNodeTarget' => $idNodeTarget,
                        'type' => 'fr',
                        'value' => self::$weigths[$relation->relationType],
                    ]);
                    self::createFrameLinks($relation->f1IdFrame);
                }
            }
        }
    }

    public static function createFrameNetwork(): void
    {
        self::init();
        // clear current network
        $frameNodes = Criteria::table('cosine_node')
            ->where('type', 'FRM')
            ->get()->pluck('idCosineNode')->toArray();
        //        debug($frameNodes);
        Criteria::table('cosine_link')
            ->whereIN('idCosineNodeSource', $frameNodes)
            ->delete();
        Criteria::table('cosine_link')
            ->whereIN('idCosineNodeTarget', $frameNodes)
            ->delete();
        Criteria::table('cosine_node')
            ->where('type', 'FRM')
            ->delete();
        $frames = Criteria::table('frame')
            ->select('idFrame')
            ->all();
        // create all frame nodes
        foreach ($frames as $frame) {
            Criteria::create('cosine_node', [
                'name' => 'frame_'.$frame->idFrame,
                'type' => 'FRM',
                'idFrame' => $frame->idFrame,
            ]);
        }
        // now create links
        foreach ($frames as $frame) {
            self::createFrameLinks($frame->idFrame);
        }
    }

    public static function createLinkSentenceAnnotationToFrame(int $idDocument): void
    {
        // clear current network for the idDocument
        $sentences = Criteria::table('document_sentence')
            ->where('idDocument', $idDocument)
            ->all();
        foreach ($sentences as $sentence) {
            $sentenceNode = Criteria::byId('cosine_node', 'idDocument', $sentence->idDocument);
            Criteria::table('cosine_link')
                ->where('idCosineNodeSource', $sentenceNode->idCosineNode)
                ->delete();
            Criteria::table('cosine_node')
                ->where('idCosineNode', $sentenceNode->idCosineNode)
                ->delete();
        }
        //
        $sentences = Criteria::table('document_sentence as ds')
            ->join('view_annotationset as a', 'ds.idSentence', 'a.idSentence')
            ->join('view_annotation_text_gl as t', 'a.idAnnotationSet', 't.idAnnotationSet')
            ->join('lu', 'lu.idLU', 'a.idLU')
            ->where('ds.idDocument', $idDocument)
            ->where('t.idLanguage', AppService::getCurrentIdLanguage())
            ->where('t.name', 'Target')
            ->select('ds.idSentence', 'lu.idFrame', 'ds.idDocumentSentence', 'ds.idDocument')
            ->distinct()
            ->all();
        $idDocumentSentence = $idCosineNodeSentence = 0;
        foreach ($sentences as $sentence) {
            if ($sentence->idDocumentSentence != $idDocumentSentence) {
                $idCosineNodeSentence = Criteria::create('cosine_node', [
                    'name' => 'sen_'.$sentence->idSentence,
                    'type' => 'SEN',
                    'idDocument' => $sentence->idDocument,
                    'idSentence' => $sentence->idSentence,
                ]);
                $idDocumentSentence = $sentence->idDocumentSentence;
            }
            $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $sentence->idFrame)->idCosineNode;
            Criteria::create('cosine_link', [
                'idCosineNodeSource' => $idCosineNodeSentence,
                'idCosineNodeTarget' => $idCosineNodeFrame,
                'value' => 1.0,
                'type' => 'lu',
            ]);
        }
    }

    public static function createLinkDocumentSentenceToFrame(int $idDocument): void
    {
        // clear current network for the idDocument
        $docSentences = Criteria::table('document_sentence')
            ->where('idDocument', $idDocument)
            ->all();
        foreach ($docSentences as $docSentence) {
            $sentenceNode = Criteria::byId('cosine_node', 'idDocumentSentence', $docSentence->idDocumentSentence);
            if ($sentenceNode?->idCosineNode) {
                Criteria::table('cosine_link')
                    ->where('idCosineNodeSource', $sentenceNode->idCosineNode)
                    ->delete();
                Criteria::table('cosine_node')
                    ->where('idCosineNode', $sentenceNode->idCosineNode)
                    ->delete();
            }
        }
        $docSentences = Criteria::table('document_sentence as ds')
            ->where('ds.idDocument', $idDocument)
            ->all();
        foreach ($docSentences as $docSentence) {
            $idCosineNodeSentence = Criteria::create('cosine_node', [
                'name' => 'dse_'.$docSentence->idDocumentSentence,
                'type' => 'DSE',
                'idDocumentSentence' => $docSentence->idDocumentSentence,
            ]);
            $frames = Criteria::table('view_annotationset as a')
                ->join('view_annotation_text_gl as t', 'a.idAnnotationSet', 't.idAnnotationSet')
                ->join('lu', 'lu.idLU', 'a.idLU')
                ->where('a.idDocumentSentence', $docSentence->idDocumentSentence)
                ->where('t.idLanguage', AppService::getCurrentIdLanguage())
                ->where('t.name', 'Target')
                ->select('lu.idFrame')
                ->distinct()
                ->all();
            foreach ($frames as $frame) {
                $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $frame->idFrame)->idCosineNode;
                Criteria::create('cosine_link', [
                    'idCosineNodeSource' => $idCosineNodeSentence,
                    'idCosineNodeTarget' => $idCosineNodeFrame,
                    'value' => 1.0,
                    'type' => 'lu',
                ]);
            }
        }

        //
        //        $docSentences = Criteria::table("document_sentence as ds")
        //            ->join("view_annotationset as a", "ds.idDocumentSentence", "a.idDocumentSentence")
        //            ->join("view_annotation_text_gl as t", "a.idAnnotationSet", "t.idAnnotationSet")
        //            ->join("lu", "lu.idLU", "a.idLU")
        //            ->where("ds.idDocument", $idDocument)
        //            ->where("t.idLanguage", AppService::getCurrentIdLanguage())
        //            ->where("t.name", "Target")
        //            ->select("ds.idSentence", "lu.idFrame", "ds.idDocumentSentence", "ds.idDocument")
        //            ->distinct()
        //            ->all();
        //        $idDocumentSentence = $idCosineNodeSentence = 0;
        //        foreach ($docSentences as $docSentence) {
        //            if ($docSentence->idDocumentSentence != $idDocumentSentence) {
        //                $idCosineNodeSentence = Criteria::create("cosine_node", [
        //                    "name" => "dse_" . $docSentence->idDocumentSentence,
        //                    "type" => "DSE",
        //                    "idDocumentSentence" => $docSentence->idDocumentSentence,
        //                ]);
        //                $idDocumentSentence = $docSentence->idDocumentSentence;
        //            }
        //            $idCosineNodeFrame = Criteria::byId("cosine_node", "idFrame", $docSentence->idFrame)->idCosineNode;
        //            Criteria::create("cosine_link", [
        //                "idCosineNodeSource" => $idCosineNodeSentence,
        //                "idCosineNodeTarget" => $idCosineNodeFrame,
        //                "value" => 1.0,
        //                "type" => "lu"
        //            ]);
        //        }
    }

    public static function deleteNodeByDocument(int $idDocument): void
    {
        // clear current network for the idDocument
        $nodes = Criteria::table('cosine_node')
            ->where('idDocument', $idDocument)
            ->all();
        foreach ($nodes as $node) {
            Criteria::table('cosine_link')
                ->where('idCosineNodeSource', $node->idCosineNode)
                ->delete();
            Criteria::table('cosine_node')
                ->where('idCosineNode', $node->idCosineNode)
                ->delete();
        }
        Criteria::table('cosine_node')
            ->where('idDocument', $idDocument)
            ->delete();
    }

    public static function deleteObjectNodeByDocument(int $idDocument): void
    {
        // clear current network for the idDocument
        $nodes = Criteria::table('cosine_node')
            ->where('idDocument', $idDocument)
            ->whereNotNull('idDynamicObject')
            ->all();
        foreach ($nodes as $node) {
            Criteria::table('cosine_link')
                ->where('idCosineNodeSource', $node->idCosineNode)
                ->delete();
            Criteria::table('cosine_node')
                ->where('idCosineNode', $node->idCosineNode)
                ->delete();
        }
        Criteria::table('cosine_node')
            ->where('idDocument', $idDocument)
            ->whereNotNull('idDynamicObject')
            ->delete();
    }

    public static function createLinkSentenceAnnotationTimeToFrame(int $idDocument): void
    {
        // clear current network for the idDocument
        self::deleteNodeByDocument($idDocument);
        //
        $sentences = Criteria::table('document_sentence as ds')
            ->join('view_sentence_timespan as st', 'ds.idSentence', 'st.idSentence')
            ->join('view_annotationset as a', 'ds.idSentence', 'a.idSentence')
            ->join('view_annotation_text_gl as t', 'a.idAnnotationSet', 't.idAnnotationSet')
            ->join('lu', 'lu.idLU', 'a.idLU')
            ->where('ds.idDocument', $idDocument)
            ->where('t.idLanguage', AppService::getCurrentIdLanguage())
            ->where('t.name', 'Target')
            ->select('ds.idSentence', 'lu.idFrame', 'ds.idDocumentSentence', 'ds.idDocument', 'st.idTimeSpan')
            ->distinct()
            ->orderBy('ds.idSentence')
            ->all();
        $idSentence = $idCosineNodeSentence = 0;
        foreach ($sentences as $sentence) {
            if ($sentence->idSentence != $idSentence) {
                $idCosineNodeSentence = Criteria::create('cosine_node', [
                    'name' => 'sen_'.$sentence->idSentence,
                    'type' => 'SEN',
                    'idDocument' => $sentence->idDocument,
                    'idSentence' => $sentence->idSentence,
                    'idTimespan' => $sentence->idTimeSpan,
                ]);
                $idSentence = $sentence->idSentence;
            }
            $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $sentence->idFrame)->idCosineNode;
            Criteria::create('cosine_link', [
                'idCosineNodeSource' => $idCosineNodeSentence,
                'idCosineNodeTarget' => $idCosineNodeFrame,
                'value' => 1.0,
                'type' => 'sn',
            ]);
        }
    }

    public static function createLinkObjectAnnotationTimeToFrame(int $idDocument): void
    {
        // clear current network for the idDocument
        self::deleteObjectNodeByDocument($idDocument);
        //
        debug("==== createLinkObjectAnnotationTimeToFrame document {$idDocument}");
        $timespans = Criteria::table('cosine_node as n')
            ->join('timespan as ts', 'n.idTimespan', 'ts.idTimespan')
            ->where('n.idDocument', $idDocument)
            ->select('n.idDocument', 'n.idTimespan', 'ts.startTime', 'ts.endTime')
            ->all();
        foreach ($timespans as $timespan) {
            $objects = Criteria::table('view_annotation_dynamic as a')
                ->join('lu', 'a.idLU', 'lu.idLU')
                ->select('a.idDynamicObject', 'lu.idFrame as idFrameLU', 'a.idFrame as idFrameFE', 'a.startTime', 'a.endTime')
                ->where('a.idDocument', $idDocument)
//                ->where("a.startTime", ">=", $timespan->startTime)
//                ->where("a.endTime", "<=", $timespan->endTime)
                ->where('a.idLanguage', AppService::getCurrentIdLanguage())
                ->all();
            foreach ($objects as $object) {
                if ((($object->startTime <= $timespan->startTime) && ($object->endTime >= $timespan->startTime))
                    || (($object->startTime > $timespan->startTime) && ($object->startTime <= $timespan->endTime))) {
                    //                    print_r($object->idDynamicObject . '    '.$object->startTime . "-" . $object->endTime . '   === ' . $timespan->startTime . '-' .$timespan->endTime . "\n");
                    $idCosineNodeObject = Criteria::create('cosine_node', [
                        'name' => 'dob_'.$object->idDynamicObject,
                        'type' => 'DOB',
                        'idDocument' => $idDocument,
                        'idDynamicObject' => $object->idDynamicObject,
                        'idTimespan' => $timespan->idTimespan,
                    ]);
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $object->idFrameLU)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeObject,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'lu',
                    ]);
                    $idCosineNodeFrame = Criteria::byId('cosine_node', 'idFrame', $object->idFrameFE)->idCosineNode;
                    Criteria::create('cosine_link', [
                        'idCosineNodeSource' => $idCosineNodeObject,
                        'idCosineNodeTarget' => $idCosineNodeFrame,
                        'value' => 1.0,
                        'type' => 'fe',
                    ]);

                }
            }
        }
    }

    private static function getLinksFromTarget(int $idCosineNode, array &$links, float $weight = 1.0): array
    {
        if (! isset(self::$processed[$idCosineNode])) {
            self::$processed[$idCosineNode] = true;
            $linksFromTarget = Criteria::table('cosine_link')
                ->where('idCosineNodeSource', $idCosineNode)
                ->all();
            foreach ($linksFromTarget as $link) {
                $links[$link->idCosineNodeTarget] = $link->value * $weight;
                self::getLinksFromTarget($link->idCosineNodeTarget, $links, $weight * 0.9);
            }
        }

        return $links;
    }

    public static function createVectorFromNode(int $idCosineNode, string $type = ''): array
    {
        $vector = [];
        self::$processed = [];
        // links to start frames
        $linkToFrames = Criteria::table('cosine_link')
            ->where('idCosineNodeSource', $idCosineNode);
        if ($type != '') {
            $linkToFrames = $linkToFrames->where('type', $type);
        }
        $linkToFrames = $linkToFrames->all();
        foreach ($linkToFrames as $linkToFrame) {
            $vector[$linkToFrame->idCosineNodeTarget] = $linkToFrame->value;
            $links = [];
            self::getLinksFromTarget($linkToFrame->idCosineNodeTarget, $links);
            foreach ($links as $idNode => $value) {
                $vector[$idNode] = $value;
            }
        }

        return $vector;
    }

    private static function createVectorForSentence(int $idSentence): array
    {
        $sentenceNode = Criteria::byId('cosine_node', 'idSentence', $idSentence);

        return self::createVectorFromNode($sentenceNode->idCosineNode);
    }

    private static function createVectorForDocumentSentence(int $idDocumentSentence): array
    {
        $sentenceNode = Criteria::byId('cosine_node', 'idDocumentSentence', $idDocumentSentence);
        if (is_null($sentenceNode)) {
            return [];
        }
        $vector = self::createVectorFromNode($sentenceNode->idCosineNode);
        return $vector;
    }

    private static function createVectorForReference(int $idReference): array
    {
        print_r('idReference = '.$idReference."\n");
        $referenceNode = Criteria::byId('cosine_node', 'idReference', $idReference);
        if (is_null($referenceNode)) {
            return [];
        }
        return self::createVectorFromNode($referenceNode->idCosineNode);
    }

    public static function compareTimespan(int $idDocument, int $idOriginMM, string $type = ''): array
    {
        $results = [];
        $timespans = Criteria::table('cosine_node as n')
            ->join('timespan as ts', 'n.idTimespan', 'ts.idTimespan')
            ->join('sentence as s', 'n.idSentence', 's.idSentence')
            ->where('n.idDocument', $idDocument)
            ->where('n.type', 'SEN')
            ->where('s.idOriginMM', $idOriginMM)
            ->select('n.idCosineNode', 'n.idTimespan', 'n.idSentence', 'ts.startTime', 'ts.endTime')
            ->all();
        foreach ($timespans as $timespan) {
            $vector1 = self::createVectorFromNode($timespan->idCosineNode, 'sn');
            $objects = Criteria::table('cosine_node as n')
                ->where('n.idDocument', $idDocument)
                ->where('n.type', 'DOB')
                ->where('n.idTimespan', $timespan->idTimespan)
                ->select('n.idCosineNode', 'n.idDynamicObject')
                ->all();
            $vector2 = [];
            $idObjects = [];
            foreach ($objects as $object) {
                $idObjects[$object->idDynamicObject] = $object->idDynamicObject;
                $vector = self::createVectorFromNode($object->idCosineNode, $type);
                foreach ($vector as $idFrame => $value) {
                    if (isset($vector2[$idFrame])) {
                        if ($value > $vector2[$idFrame]) {
                            $vector2[$idFrame] = $value;
                        }
                    } else {
                        $vector2[$idFrame] = $value;
                    }
                }
            }
            //            if ($type =='fe') {
            //                debug($vector1);
            //                debug($vector2);
            //                die;
            //            }
            $cosine = self::compareVectors($vector1, $vector2);
            $documentSentence = Criteria::table('document_sentence')
                ->where('idDocument', $idDocument)
                ->where('idSentence', $timespan->idSentence)
                ->first();
            $results[] = [
                'idDocumentSentence' => $documentSentence->idDocumentSentence,
                'idSentence' => $timespan->idSentence,
                'startTime' => $timespan->startTime,
                'endTime' => $timespan->endTime,
                'objects' => implode(',', $idObjects),
                'cosine' => $cosine->cosine,
            ];
        }
        $count = 0;
        $total = 0.0;
        foreach ($results as $result) {
            $count++;
            $total += ($result['cosine'] >= 0) ? (float) $result['cosine'] : 0.0;
        }
        $results[] = [
            'idDocumentSentence' => 'Total',
            'idSentence' => $count,
            'startTime' => '',
            'endTime' => '',
            'objects' => 'Média',
            'cosine' => ($total / $count),
        ];

        //        debug($results);
        return $results;
    }

    public static function compareSentences(int $idSentence1, int $idSentence2): object
    {
        $vector1 = self::createVectorForSentence($idSentence1);
        $vector2 = self::createVectorForSentence($idSentence2);

        return self::compareVectors($vector1, $vector2);
    }

    public static function compareDocumentSentences(int $idDocumentSentence1, int $idDocumentSentence2): object
    {
        $vector1 = self::createVectorForDocumentSentence($idDocumentSentence1);
        $vector2 = self::createVectorForDocumentSentence($idDocumentSentence2);
        return self::compareVectors($vector1, $vector2);
    }

    public static function compareReferences(int $idReference1, int $idReference2): object
    {
        $vector1 = self::createVectorForReference($idReference1);
        $vector2 = self::createVectorForReference($idReference2);

        return self::compareVectors($vector1, $vector2);
    }

    public static function compareVectors(array $vector1, array $vector2): object
    {
        // fill zeroes
        foreach ($vector2 as $idEntity => $a) {
            if (! isset($vector1[$idEntity])) {
                $vector1[$idEntity] = 0.0;
            }
        }
        foreach ($vector1 as $idEntity => $a) {
            if (! isset($vector2[$idEntity])) {
                $vector2[$idEntity] = 0.0;
            }
        }

        // cosine similarity
        // cos(theta) = sum(vector1, vector2) / (mod(vector1) * mod(vector2))

        //        print_r("Calculing sum \n");
        $sum = 0;
        foreach ($vector1 as $idEntity => $a) {
            $sum += ($a * $vector2[$idEntity]);
        }
        //        print_r('sum', $sum);
        $sumA = 0;
        foreach ($vector1 as $a) {
            $sumA += ($a * $a);
        }
        //        print_r('sumA', $sumA);
        $modA = sqrt($sumA);
        //        print_r('modA', $modA);
        $sumB = 0;
        foreach ($vector2 as $a) {
            $sumB += ($a * $a);
        }
        //        print_r('sumB', $sumB);
        $modB = sqrt($sumB);
        //        print_r('modB', $modB);
        //        print_r('modA * modB', ($modA * $modB));
        $m = $modA * $modB;
        if ($m > 0) {
            $cosine = round($sum / ($modA * $modB), 6);
        } else {
            $cosine = -1;
        }
        //        print_r("Sorting \n");
        asort($vector1);
        asort($vector2);

        //        print_r($vector1);
        //        print_r($vector2);

        $result = (object) [
            'array1' => $vector1,
            'array2' => $vector2,
            'cosine' => $cosine,
        ];

        if (is_null($result)) {
            $result = (object) [
                'array1' => [],
                'array2' => [],
                'cosine' => 0];
        }

        //        debug($result);
        return $result;

    }

    public static function writeToCSV(string $fileName, array $results)
    {
        $handle = fopen($fileName, 'w');
        fputcsv($handle, array_keys($results[0]));
        foreach ($results as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}

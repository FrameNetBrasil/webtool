<?php

namespace App\Services;

use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Base;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\FrameElement;
use App\Repositories\StaticAnnotationMM;
use App\Repositories\StaticBBoxMM;
use App\Repositories\StaticObjectSentenceMM;
use App\Repositories\StaticSentenceMM;
use App\Repositories\Task;
use App\Repositories\User;
use App\Repositories\UserAnnotation;
use App\Repositories\Timeline;
use Illuminate\Support\Facades\DB;


class AnnotationStaticEventService
{

    public static function listSentences(int $idDocument): array
    {
        $userTask = AnnotationService::getCurrentUserTask($idDocument);
        $task = Task::byId($userTask->idTask);
        $text = ($task->type == 'sentence') ? "sentence.text" : "'' as text";
        $sentences = Criteria::table("sentence")
            ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->join("view_image_sentence as is", "sentence.idSentence", "=", "is.idSentence")
            ->join("image as i", "is.idImage", "=", "i.idImage")
            ->where("d.idDocument", $idDocument)
            ->select("sentence.idSentence", "i.name as imageName", "ds.idDocumentSentence")
            ->selectRaw($text)
            ->distinct()
            ->orderBy("ds.idDocumentSentence")
            ->limit(1500)
            ->get()->keyBy("idSentence")->all();
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

    public static function getObjectsForAnnotationImage(int $idDocument, int $idSentence): array
    {
        $usertask = AnnotationService::getCurrentUserTask($idDocument);
        if (is_null($usertask)) {
            return [
                'objects' => [],
                'frames' => []
            ];
        }
        $task = Task::byId($usertask->idTask);
//        debug($task);
        //objects for document_sentence
        $criteria = Criteria::table("view_staticobject_textspan")
            ->where("idDocument", $idDocument)
            ->where("idSentence", $idSentence);
        $idLanguage = AppService::getCurrentIdLanguage();
        $objects = $criteria->get()->keyBy('idStaticObject')->all();
        $idObject = 1;
        foreach ($objects as $i => $object) {
            $bboxes = Criteria::table("view_staticobject_boundingbox")
                ->where("idStaticObject", $i)
                ->select("x", "y", "width", "height")
                ->all();
//            debug($bboxes);
            $object->idObject = $idObject++;
            $object->bboxes = $bboxes;
        }
        $frames = [];
        foreach ($objects as $object) {
//            debug($object);
            $annotations = Criteria::table("view_annotation as a")
                ->join("view_frameelement as fe", "a.idEntity", "=", "fe.idEntity")
                ->where("a.idAnnotationObject", "=", $object->idAnnotationObject)
                ->where("a.idUserTask", "=", $usertask->idUserTask)
                ->where("fe.idLanguage", "=", $idLanguage)
                ->select([
                    "a.idAnnotation",
                    "fe.idFrame",
                    "fe.frameName as frameName",
                    "fe.idFrameElement"
                ])
                ->orderBy("fe.idFrame")
                ->all();
            foreach ($annotations as $annotation) {
                if (!isset($frames[$annotation->idFrame])) {
                    $frames[$annotation->idFrame] = [
                        'idFrame' => $annotation->idFrame,
                        'name' => $annotation->frameName,
                        'objects' => []
                    ];
                }
                if (is_null($annotation->idFrameElement)) {
                    $annotation->idFrameElement = -1;
                }
                $frames[$annotation->idFrame]['objects'][$object->idStaticObject] = $annotation;
            }
        }
        return [
            'type' => $task->type,
            'objects' => $objects,
            'frames' => $frames
        ];
    }

    public static function deleteAnnotationByFrame(int $idDocumentSentence, int $idFrame)
    {
        DB::transaction(function () use ($idDocumentSentence, $idFrame) {
            $idLanguage = AppService::getCurrentIdLanguage();
            $ds = Criteria::table("view_document_sentence")
                ->where("idDocumentSentence", $idDocumentSentence)
                ->select("idDocument","idSentence")
                ->first();
            $usertask = self::getCurrentUserTask($ds->idDocument);
            if (is_null($usertask)) {
                throw new \Exception("UserTask not found!");
            }
            $criteria = Criteria::table("view_staticobject_textspan")
                ->where("idDocument", $ds->idDocument)
                ->where("idSentence", $ds->idSentence);
            $objects = $criteria->get()->pluck('idAnnotationObject')->all();
            $annotations = Criteria::table("annotation as a")
                ->join("view_frameelement as fe", "a.idEntity", "=", "fe.idEntity")
                ->where("a.idUserTask", "=", $usertask->idUserTask)
                ->where("fe.idFrame", "=", $idFrame)
                ->where("fe.idLanguage", "=", $idLanguage)
                ->whereIn("a.idAnnotationObject", $objects)
                ->select("a.idAnnotation")
                ->get()->pluck('idAnnotation')->all();
            Criteria::table("annotation")
                ->whereIn("idAnnotation", $annotations)
                ->delete();
        });
    }

    public static function updateAnnotation(int $idDocumentSentence, int $idFrame, array $staticObjectFEs)
    {
        DB::transaction(function () use ($idDocumentSentence, $idFrame, $staticObjectFEs) {
            $idLanguage = AppService::getCurrentIdLanguage();
            $relation = Criteria::table("view_document_sentence")
                ->where("idDocumentSentence", $idDocumentSentence)
                ->select("idDocument")
                ->first();
            $idUser = AppService::getCurrentIdUser();
            $usertask = self::getCurrentUserTask($relation->idDocument);
            if (is_null($usertask)) {
                throw new \Exception("UserTask not found!");
            }
            $idStaticObject = array_keys($staticObjectFEs[$idFrame]);
            $annotations = Criteria::table("annotation as a")
                ->join("view_frameelement as fe", "a.idEntity", "=", "fe.idEntity")
                ->where("a.idAnnotationObject", "IN", $idStaticObject)
                ->where("a.idUserTask", "=", $usertask->idUserTask)
                ->where("fe.idFrame", "=", $idFrame)
                ->where("fe.idLanguage", "=", $idLanguage)
                ->select("a.idAnnotation")
                ->get()->pluck('idAnnotation')->all();
            Criteria::table("annotation")
                ->whereIn("idAnnotation", $annotations)
                ->delete();
            foreach ($staticObjectFEs[$idFrame] as $idStaticObject => $idFrameElement) {
                if ($idFrameElement != -1) {
                    $fe = Criteria::table("frameelement")
                        ->where("idFrameElement", $idFrameElement)
                        ->first();
                    $data = json_encode([
                        'idEntity' => $fe->idEntity,
                        'idAnnotationObject' => $idStaticObject,
                        'relationType' => 'rel_annotation',
                        'idUserTask' => $usertask->idUserTask,
                        'idUser' => $idUser
                    ]);
                    $idAnnotation = Criteria::function("annotation_create(?)", [$data]);
                    Timeline::addTimeline("annotation", $idAnnotation, "C");
                }
            }
        });
    }

    /*
     * Visual Units: documentos anotados para um dado frame de evento
     */

    public static function getDocumentsForVU(int $idFrame, int $idLanguage): array
    {

//        select distinct i.idImage, d.idDocument, d.corpusName, d.name
//from staticobject as sob
//join view_object_relation or1 on (sob.idAnnotationObject = or1.idAnnotationObject2)
//join view_object_relation or2 on (or1.idAnnotationObject1 = or2.idAnnotationObject2)
//join image i on (or1.idAnnotationObject1 = i.idAnnotationObject)
//join view_document d on (or2.idAnnotationObject1 = d.idAnnotationObject)
//join annotation a on (sob.idAnnotationObject = a.idAnnotationObject)
//join frameelement fe on (a.idEntity = fe.idEntity)
//where (or1.relationType = 'rel_image_staobj')
//and (or2.relationType = 'rel_document_image')
//and (fe.idFrame = 664)
//and d.idcorpus between 140 and 147
//    and d.idLanguage = 1
//order by 3,4,1;
//
        $annotations = Criteria::table("staticobject as sob")
            ->join("image_staticobject as is", "sob.idStaticObject", "=", "is.idStaticObject")
            ->join("image as i", "is.idImage", "=", "i.idImage")
            ->join("document_image as di", "di.idImage", "=", "i.idImage")
            ->join("view_document as d", "di.idDocument", "=", "d.idDocument")
            ->join("annotation as a", "sob.idStaticObject", "=", "a.idStaticObject")
            ->join("frameelement as fe", "a.idEntity", "=", "fe.idEntity")
            ->where("fe.idFrame", "=", $idFrame)
            ->where("d.idLanguage", "=", $idLanguage)
            ->whereBetween("d.idCorpus", [140,147])
            ->orderBy("d.corpusName")
            ->orderBy("d.name")
            ->orderBy("i.idImage")
            ->distinct()
            ->select([
                "d.corpusName",
                "d.name as documentName",
                "d.idDocument",
                "i.idImage"
            ])
            ->all();
        return $annotations;
    }

    public static function getObjectsForVU(int $idDocument, int $idSentence): array
    {
        $usertask = AnnotationService::getCurrentUserTask($idDocument);
        if (is_null($usertask)) {
            return [
                'objects' => [],
                'frames' => []
            ];
        }
        $task = Task::byId($usertask->idTask);
//        debug($task);
        //objects for document_sentence
        $criteria = Criteria::table("view_staticobject_textspan")
            ->where("idDocument", $idDocument)
            ->where("idSentence", $idSentence);
        $idLanguage = AppService::getCurrentIdLanguage();
        $objects = $criteria->get()->keyBy('idStaticObject')->all();
        $idObject = 1;
        foreach ($objects as $i => $object) {
            $bboxes = Criteria::table("view_staticobject_boundingbox")
                ->where("idStaticObject", $i)
                ->select("x", "y", "width", "height")
                ->all();
//            debug($bboxes);
            $object->idObject = $idObject++;
            $object->bboxes = $bboxes;
        }
        $frames = [];
        foreach ($objects as $object) {
//            debug($object);
            $annotations = Criteria::table("view_annotation as a")
                ->join("view_frameelement as fe", "a.idEntity", "=", "fe.idEntity")
                ->where("a.idAnnotationObject", "=", $object->idAnnotationObject)
                ->where("a.idUserTask", "=", $usertask->idUserTask)
                ->where("fe.idLanguage", "=", $idLanguage)
                ->select([
                    "a.idAnnotation",
                    "fe.idFrame",
                    "fe.frameName as frameName",
                    "fe.idFrameElement"
                ])
                ->orderBy("fe.idFrame")
                ->all();
            foreach ($annotations as $annotation) {
                if (!isset($frames[$annotation->idFrame])) {
                    $frames[$annotation->idFrame] = [
                        'idFrame' => $annotation->idFrame,
                        'name' => $annotation->frameName,
                        'objects' => []
                    ];
                }
                if (is_null($annotation->idFrameElement)) {
                    $annotation->idFrameElement = -1;
                }
                $frames[$annotation->idFrame]['objects'][$object->idStaticObject] = $annotation;
            }
        }
        return [
            'type' => $task->type,
            'objects' => $objects,
            'frames' => $frames
        ];
    }




}

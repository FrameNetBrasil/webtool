<?php

namespace App\Services;

use App\Data\Annotation\StaticBBox\CloneData;
use App\Data\Annotation\StaticBBox\CommentData;
use App\Data\Annotation\StaticBBox\ObjectAnnotationData;
use App\Data\Annotation\StaticBBox\ObjectData;
use App\Data\Annotation\StaticBBox\UpdateBBoxData;
use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Image;
use App\Repositories\Task;
use App\Repositories\Timeline;
use App\Repositories\User;
use Illuminate\Support\Facades\DB;

class AnnotationStaticBBoxService
{
    private static function deleteBBoxesByStaticBBoxObject(int $idStaticObject)
    {
        $bboxes = Criteria::table("view_staticobject_boundingbox as sb")
            ->join("boundingbox as bb", "sb.idBoundingBox", "=", "bb.idBoundingBox")
            ->where("sb.idStaticObject", $idStaticObject)
            ->select("bb.idAnnotationObject")
            ->chunkResult("idAnnotationObject", "idAnnotationObject");
        Criteria::table("annotationobjectrelation")
            ->whereIn("idAnnotationObject2", $bboxes)
            ->delete();
        Criteria::table("boundingbox")
            ->whereIn("idAnnotationObject", $bboxes)
            ->delete();
        Criteria::table("annotationobject")
            ->whereIn("idAnnotationObject", $bboxes)
            ->delete();
    }

    public static function getPrevious(object $document)
    {
        $i = Criteria::table("view_document")
            ->where("idCorpus", "=", $document->idCorpus)
            ->where("idDocument", "<", $document->idDocument)
            ->max('idDocument');
        return $i ?? null;
    }

    public static function getNext(object $document)
    {
        $i = Criteria::table("view_document")
            ->where("idCorpus", "=", $document->idCorpus)
            ->where("idDocument", ">", $document->idDocument)
            ->min('idDocument');
        return $i ?? null;
    }

    public static function getObject(int $idStaticObject): object|null
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $do = Criteria::table("view_annotation_static")
            ->where("idLanguage", "left", $idLanguage)
            ->where("idStaticObject", $idStaticObject)
            ->select("idStaticObject", "name", "idAnnotationLU", "idLU", "lu", "idAnnotationFE", "idFrameElement", "idFrame", "frame", "fe", "color","idDocument")
            ->orderBy("idStaticObject")
            ->first();
        return $do;
    }

    public static function getObjectComment(int $idStaticObject): object|null
    {
        $so = Criteria::table("staticobject as so")
            ->leftJoin("annotationcomment as ac", "so.idStaticObject", "=", "ac.idStaticObject")
            ->leftJoin("user as u", "ac.idUser", "=", "u.idUser")
            ->where("so.idStaticObject", $idStaticObject)
            ->select("so.idStaticObject", "ac.comment", "ac.createdAt", "ac.updatedAt", "u.email")
            ->first();
        return $so;
    }

    public static function getObjectsByDocument(int $idDocument): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $usertask = AnnotationService::getCurrentUserTask($idDocument);
        if (is_null($usertask)) {
            return [
                'objects' => [],
                'frames' => []
            ];
        }
        //$task = Task::byId($usertask->idTask);
        $result = Criteria::table("view_annotation_static as sta")
            ->leftJoin("view_lu", "sta.idLu", "=", "view_lu.idLU")
            ->leftJoin("view_frame", "view_lu.idFrame", "=", "view_frame.idFrame")
            ->leftJoin("annotationcomment as ac", "sta.idStaticObject", "=", "ac.idStaticObject")
            ->where("sta.idLanguage", "left", $idLanguage)
            ->where("idDocument", $idDocument)
            ->where("view_frame.idLanguage", "left", $idLanguage)
            ->select("idDocument","sta.idStaticObject", "sta.name", "idAnnotationLU", "sta.idLU", "lu", "view_lu.name as luName", "sta.idUserTaskLU",
                "view_frame.name as luFrameName", "idAnnotationFE", "idFrameElement", "sta.idFrame", "frame", "fe", "color", "sta.idUserTaskFE","ac.comment")
            ->orderBy("idStaticObject")
            ->all();
        $oMM = [];
        $bbox = [];
        $valids = [];
        foreach ($result as $i => $row) {
            $valid = true;
            if ($row->idUserTaskFE) {
                $valid = ($valid && ($row->idUserTaskFE == $usertask->idUserTask)) || ($usertask->idUserTask == 1);
            }
            if ($row->idUserTaskLU) {
                $valid = ($valid && ($row->idUserTaskLU == $usertask->idUserTask)) || ($usertask->idUserTask == 1);
            }
            if ($valid) {
                $valids[$i] = $i;
                $oMM[$row->idStaticObject] = $row->idStaticObject;
            }
        }
        if (count($result) > 0) {
            $bboxObjects = Criteria::table("view_staticobject_boundingbox")
                ->whereIN("idStaticObject", $oMM)
                ->all();
            foreach ($bboxObjects as $bboxObject) {
                $bbox[$bboxObject->idStaticObject] = $bboxObject;
            }
        }
        $objects = [];
        $j = 1;
        foreach ($result as $i => $row) {
            if (isset($valids[$i])) {
                $row->order = $j++;
                $row->bbox = $bbox[$row->idStaticObject] ?? null;
                $objects[] = $row;
            }
        }
        return $objects;
    }

    public static function updateObjectAnnotation(ObjectAnnotationData $data): int
    {
        $usertask = AnnotationService::getCurrentUserTask($data->idDocument);
        $idUser = AppService::getCurrentIdUser();
        $sob = Criteria::byId("staticobject", "idStaticObject", $data->idStaticObject);
        Criteria::deleteById("annotation", "idAnnotationObject", $sob->idAnnotationObject);
        if ($data->idFrameElement) {
            $fe = Criteria::byId("frameelement", "idFrameElement", $data->idFrameElement);
            $json = json_encode([
                'idEntity' => $fe->idEntity,
                'idAnnotationObject' => $sob->idAnnotationObject,
                'relationType' => 'rel_annotation',
                'idUserTask' => $usertask->idUserTask,
                'idUser' => $idUser
            ]);
            debug($json);
            $idAnnotation = Criteria::function("annotation_create(?)", [$json]);
            Timeline::addTimeline("annotation", $idAnnotation, "C");
        }
        if ($data->idLU) {
            $lu = Criteria::byId("lu", "idLU", $data->idLU);
            $json = json_encode([
                'idEntity' => $lu->idEntity,
                'idAnnotationObject' => $sob->idAnnotationObject,
                'relationType' => 'rel_annotation',
                'idUserTask' => $usertask->idUserTask,
                'idUser' => $idUser
            ]);
            $idAnnotation = Criteria::function("annotation_create(?)", [$json]);
            Timeline::addTimeline("annotation", $idAnnotation, "C");
        }
        return $data->idStaticObject;
    }

    public static function updateObject(ObjectData $data): int
    {
        debug($data);
        $idUser = AppService::getCurrentIdUser();
        // if idStaticObject = null : object create
        if (is_null($data->idStaticObject)) {
            $sob = json_encode([
                'name' => $data->name,
                'scene' => 0,
                'idFlickr30kEntitiesChain' => -1,
                'nobndbox' => 0,
                'idUser' => $idUser
            ]);
            $idStaticObject = Criteria::function("staticobject_create(?)", [$sob]);
            $staticObject = Criteria::byId("staticobject", "idStaticObject", $idStaticObject);
            $documentImage = Criteria::table("view_document_image")
                ->where("idDocument", $data->idDocument)
                ->first();
            $image = Image::byId($documentImage->idImage);
            // create annotationobjectrelation for rel_image_staobj
            $relation = json_encode([
                'idAnnotationObject1' => $image->idAnnotationObject,
                'idAnnotationObject2' => $staticObject->idAnnotationObject,
                'relationType' => 'rel_image_staobj'
            ]);
            $idObjectRelation = Criteria::function("objectrelation_create(?)", [$relation]);
            if (count($data->bbox)) {
                $bbox = $data->bbox;
                $json = json_encode([
                    'frameNumber' => 0,
                    'frameTime' => 0,
                    'x' => (int)$bbox['x'],
                    'y' => (int)$bbox['y'],
                    'width' => (int)$bbox['width'],
                    'height' => (int)$bbox['height'],
                    'blocked' => (int)$bbox['blocked'],
                    'idStaticObject' => (int)$idStaticObject
                ]);
                $idBoundingBox = Criteria::function("boundingbox_static_create(?)", [$json]);
            }
        } else {
            // if idStaticObject != null : update object and  boundingbox
            $idStaticObject = $data->idStaticObject;
            if (count($data->bbox)) {
                $bbox = $data->bbox;
                $json = json_encode([
                    'frameNumber' => 0,
                    'frameTime' => 0,
                    'x' => (int)$bbox['x'],
                    'y' => (int)$bbox['y'],
                    'width' => (int)$bbox['width'],
                    'height' => (int)$bbox['height'],
                    'blocked' => (int)$bbox['blocked'],
                    'idStaticObject' => (int)$idStaticObject
                ]);
                $idBoundingBox = Criteria::function("boundingbox_static_create(?)", [$json]);
            }
        }
        return $idStaticObject;
    }

    public static function updateObjectComment(CommentData $data): int
    {
        $idStaticObject = $data->idStaticObject;
        $comment = Criteria::byId("annotationcomment", "idStaticObject", $idStaticObject);
        if (is_null($comment)) {
            Criteria::create("annotationcomment", [
                "idStaticObject" => $idStaticObject,
                "comment" => $data->comment,
                "idUser" => $data->idUser,
                "createdAt" => $data->createdAt,
                "updatedAt" => $data->updatedAt,
            ]);
        } else {
            Criteria::table("annotationcomment")
                ->where("idStaticObject", $idStaticObject)
                ->update([
                    "comment" => $data->comment,
                    "updatedAt" => $data->updatedAt,
                ]);
        }
        return $idStaticObject;
    }
    public static function cloneObject(CloneData $data): int
    {
        $idUser = AppService::getCurrentIdUser();
        $idStaticObject = $data->idStaticObject;
        $sob = self::getObject($idStaticObject);
        $clone = json_encode([
            'name' => $sob->name,
            'scene' => 0,
            'nobdnbox' => 0,
            'idUser' => $idUser
        ]);
        $idStaticObjectClone = Criteria::function("staticobject_create(?)", [$clone]);
        $staticObjectClone = Criteria::byId("staticobject", "idStaticObject", $idStaticObjectClone);
        $documentImage = Criteria::table("view_document_image")
            ->where("idDocument", $data->idDocument)
            ->first();
        $image = Image::byId($documentImage->idImage);
        // create annotationobjectrelation for rel_image_staobj
        $relation = json_encode([
            'idAnnotationObject1' => $image->idAnnotationObject,
            'idAnnotationObject2' => $staticObjectClone->idAnnotationObject,
            'relationType' => 'rel_image_staobj'
        ]);
        $idObjectRelation = Criteria::function("objectrelation_create(?)", [$relation]);
        // cloning bboxes
        $bbox = Criteria::table("view_staticobject_boundingbox")
            ->where("idStaticObject", $idStaticObject)
            ->first();
        $json = json_encode([
            'frameNumber' => 0,
            'frameTime' => 0,
            'x' => (int)$bbox->x,
            'y' => (int)$bbox->y,
            'width' => (int)$bbox->width,
            'height' => (int)$bbox->height,
            'blocked' => (int)$bbox->blocked,
            'idStaticObject' => (int)$idStaticObjectClone
        ]);
        $idBoundingBox = Criteria::function("boundingbox_static_create(?)", [$json]);
        return $idStaticObjectClone;
    }

    public static function deleteObject(int $idStaticObject): void
    {
        // se pode remover o objeto se for Manager ou se for o criador do objeto
        $idUser = AppService::getCurrentIdUser();
        $user = User::byId($idUser);
        if (!User::isManager($user)) {
            $tl = Criteria::table("timeline")
                ->where("tablename", "staticobject")
                ->where("id", $idStaticObject)
                ->select("idUser")
                ->first();
            if ($tl->idUser != $idUser) {
                throw new \Exception("Object can not be removed.");
            }
        }
        DB::transaction(function () use ($idStaticObject) {
            // remove boundingbox
            self::deleteBBoxesByStaticBBoxObject($idStaticObject);
            // remove staticobject
            $idUser = AppService::getCurrentIdUser();
            Criteria::function("staticobject_delete(?,?)", [$idStaticObject, $idUser]);
        });
    }

    public static function deleteObjectComment(int $idStaticObject): void
    {
        Criteria::deleteById("annotationcomment", "idStaticObject", $idStaticObject);
    }
    public static function updateBBox(UpdateBBoxData $data): int
    {
        $bbox = Criteria::table("view_staticobject_boundingbox")
            ->where("idStaticObject", $data->idStaticObject)
            ->first();
        Criteria::table("boundingbox")
            ->where("idBoundingBox", $bbox->idBoundingBox)
            ->update($data->bbox);
        return $data->idStaticObject;
    }

    public static function listSentencesByDocument($idDocument): array
    {
        $sentences = Criteria::table("sentence")
            ->join("view_document_sentence as ds", "sentence.idSentence", "=", "ds.idSentence")
            ->join("document as d", "ds.idDocument", "=", "d.idDocument")
            ->leftJoin("originmm as o", "sentence.idOriginMM", "=", "o.idOriginMM")
            ->where("d.idDocument", $idDocument)
            ->select("sentence.idSentence", "sentence.text", "ds.idDocumentSentence", "o.origin", "d.idDocument")
            ->orderBy("sentence.idSentence")
            ->limit(1000)
            ->get()->keyBy("idDocumentSentence")->all();
        if (!empty($sentences)) {
            $targets = collect(AnnotationSet::listTargetsForDocumentSentence(array_keys($sentences)))->groupBy('idDocumentSentence')->toArray();
            foreach ($targets as $idDocumentSentence => $spans) {
                $sentences[$idDocumentSentence]->text = self::decorateSentenceTarget($sentences[$idDocumentSentence]->text, $spans);
            }
        }
        return $sentences;
    }


    public static function decorateSentenceTarget($text, $spans)
    {
        $decorated = "";
        $i = 0;
        foreach ($spans as $span) {
            if ($span->startChar >= 0) {
                $decorated .= mb_substr($text, $i, $span->startChar - $i);
                $decorated .= "<span class='color_target' style='cursor:default' title='{$span->frameName}'>" . mb_substr($text, $span->startChar, $span->endChar - $span->startChar + 1) . "</span>";
                $i = $span->endChar + 1;
            }
        }
        $decorated = $decorated . mb_substr($text, $i);
        return $decorated;
    }
}

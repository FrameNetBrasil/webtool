<?php

namespace App\Services\Annotation;

use App\Data\Annotation\Comment\CommentData;
use App\Data\Annotation\Image\CloneData;
use App\Data\Annotation\Image\CreateBBoxData;
use App\Data\Annotation\Image\CreateObjectData;
use App\Data\Annotation\Image\ObjectAnnotationData;
use App\Data\Annotation\Image\ObjectSearchData;
use App\Data\Annotation\Image\UpdateBBoxData;
use App\Database\Criteria;
use App\Enum\AnnotationType;
use App\Repositories\AnnotationSet;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\Image;
use App\Repositories\Timeline;
use App\Repositories\User;
use App\Repositories\Video;
use App\Services\AnnotationService;
use App\Services\AppService;
use App\Services\CommentService;
use Illuminate\Support\Facades\DB;

class ImageService
{
    public static function getResourceData(int $idDocument, ?int $idObject = null, string $annotationType = ''): array
    {
        $document = Document::byId($idDocument);
        if (!$document) {
            throw new \Exception("Document with ID {$idDocument} not found.");
        }

        $corpus = Corpus::byId($document->idCorpus);
        if (!$corpus) {
            throw new \Exception("Corpus with ID {$document->idCorpus} not found.");
        }

        $documentImage = Criteria::table('view_document_image')
            ->where('idDocument', $idDocument)
            ->first();
        if (!$documentImage) {
            throw new \Exception("Image not found for document ID {$idDocument}.");
        }

        $image = Image::byId($documentImage->idImage);
        if (!$image) {
            throw new \Exception("Image with ID {$documentImage->idImage} not found.");
        }

        $at = ($annotationType == 'staticBBox') ? AnnotationType::STATICBBOX->value : AnnotationType::STATICEVENT->value;
        $comment = $idObject ? CommentService::getComment($idObject, $idDocument, $at) : null;
        $objects = self::getObjectsByDocument($idDocument);
        $bboxes = [];
        foreach ($objects as $object) {
            $object->bbox->order = $object->order;
            $bboxes[$object->bbox->idBoundingBox] = $object->bbox;
        }
        return [
            'idDocument' => $idDocument,
            'document' => $document,
            'corpus' => $corpus,
            'image' => $image,
            'annotationType' => $annotationType,
            'objects' => $objects,
            'bboxes' => $bboxes,
            'idObject' => is_null($idObject) ? 0 : $idObject,
            'comment' => $comment,
            'idPrevious' => self::getPrevious($document),
            'idNext' => self::getNext($document),
        ];
    }

    private static function deleteBBoxesByStaticBBoxObject(int $idStaticObject)
    {
//        $bboxes = Criteria::table("view_staticobject_boundingbox as sb")
//            ->join("boundingbox as bb", "sb.idBoundingBox", "=", "bb.idBoundingBox")
//            ->where("sb.idStaticObject", $idStaticObject)
//            ->select("bb.idAnnotationObject")
//            ->chunkResult("idAnnotationObject", "idAnnotationObject");
//        Criteria::table("annotationobjectrelation")
//            ->whereIn("idAnnotationObject2", $bboxes)
//            ->delete();
//        Criteria::table("boundingbox")
//            ->whereIn("idAnnotationObject", $bboxes)
//            ->delete();
//        Criteria::table("annotationobject")
//            ->whereIn("idAnnotationObject", $bboxes)
//            ->delete();
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

    public static function getObject(int $idObject): object|null
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $object = Criteria::table("view_staticobject")
            ->where("idStaticObject", $idObject)
            ->where("idLanguage", $idLanguage)
            ->select("idStaticObject", "name","scene","idFlickr30kEntitiesChain","nobndbox","origin", "idImage", "idDocument", "idLayerType", "nameLayerType")
            ->orderBy("idStaticObject")
            ->keyBy("idStaticObject")
            ->first();

        $fe = Criteria::table("view_staticobject as sob")
            ->join("view_annotation_static_fe as fe", "sob.idStaticObject", "=", "fe.idStaticObject")
            ->where("sob.idStaticObject", $idObject)
            ->where("fe.idLanguage", $idLanguage)
            ->select("fe.idAnnotation", "fe.idStaticObject", "fe.idFrameElement", "fe.idFrame", "fe.frameName", "fe.name", "fe.idEntity", "fe.idLanguage", "fe.bgColor", "fe.fgColor")
            ->keyBy("idStaticObject")
            ->first();

        $lu = Criteria::table("view_staticobject as sob")
            ->join("view_annotation_static_lu as lu", "sob.idStaticObject", "=", "lu.idStaticObject")
            ->where("sob.idStaticObject", $idObject)
            ->where("lu.idLanguage", $idLanguage)
            ->select("lu.idAnnotation", "lu.idStaticObject", "lu.idLU", "lu.idEntity", "lu.name", "lu.frameName", "lu.idLanguage")
            ->keyBy("idStaticObject")
            ->first();

        if (!is_null($object)) {
            $bboxObject = Criteria::table("view_staticobject_boundingbox")
                ->where("idStaticObject", $idObject)
                ->first();
            $object->bbox = $bboxObject;
            $object->idObject = $object->idStaticObject;
            $object->fe = $fe;
            $object->lu = $lu;
        }
        return $object;
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
//        $usertask = AnnotationService::getCurrentUserTask($idDocument);
//        if (is_null($usertask)) {
//            return [
//                'objects' => [],
//                'frames' => []
//            ];
//        }

        $result = Criteria::table("view_staticobject")
            ->where("idDocument", $idDocument)
            ->where("idLanguage", $idLanguage)
            ->select("idStaticObject", "origin", "idImage", "idDocument", "idLayerType", "nameLayerType")
            ->orderBy("idStaticObject")
            ->keyBy("idStaticObject")
            ->all();

        $fes = Criteria::table("view_staticobject as sob")
            ->join("view_annotation_static_fe as fe", "sob.idStaticObject", "=", "fe.idStaticObject")
            ->where("sob.idDocument", $idDocument)
            ->where("fe.idLanguage", $idLanguage)
            ->select("fe.idAnnotation", "fe.idStaticObject", "fe.idFrameElement", "fe.idFrame", "fe.frameName", "fe.name", "fe.idEntity", "fe.idLanguage", "fe.bgColor", "fe.fgColor")
            ->keyBy("idStaticObject")
            ->all();

        $lus = Criteria::table("view_staticobject as sob")
            ->join("view_annotation_static_lu as lu", "sob.idStaticObject", "=", "lu.idStaticObject")
            ->where("sob.idDocument", $idDocument)
            ->where("lu.idLanguage", $idLanguage)
            ->select("lu.idAnnotation", "lu.idStaticObject", "lu.idLU", "lu.idEntity", "lu.name", "lu.frameName", "lu.idLanguage")
            ->keyBy("idStaticObject")
            ->all();

        //$task = Task::byId($usertask->idTask);
//        $result = Criteria::table("view_annotation_static")
//            ->where("idLanguage", $idLanguage)
//            ->where("idDocument", $idDocument)
//            ->select("idDocument","idStaticObject as idObject", "name", "origin", "idAnnotationLU", "idLU", "lu",
//                "idAnnotationFE", "idFrameElement", "idFrame", "frame", "fe", "bgColorFE", "fgColorFE")
//            ->orderBy("idStaticObject")
//            ->all();
//        $oMM = [];
//        $bbox = [];
//        $valids = [];
//        foreach ($result as $i => $row) {
//            $valid = true;
////            if ($row->idUserTaskFE) {
////                $valid = ($valid && ($row->idUserTaskFE == $usertask->idUserTask)) || ($usertask->idUserTask == 1);
////            }
////            if ($row->idUserTaskLU) {
////                $valid = ($valid && ($row->idUserTaskLU == $usertask->idUserTask)) || ($usertask->idUserTask == 1);
////            }
////            if ($valid) {
////                $valids[$i] = $i;
//            $oMM[$row->idStaticObject] = $row->idStaticObject;
////            }
//        }
//        if (count($result) > 0) {
//            $bboxObject = Criteria::table("view_staticobject_boundingbox")
//                ->whereIN("idStaticObject", $oMM)
//                ->first();
//            foreach ($bboxObjects as $bboxObject) {
//                $bbox[$bboxObject->idBoundingBox] = $bboxObject;
//            }
//        }
        $objects = [];
        $j = 1;
        foreach ($result as $object) {
            $object->order = $j++;
            $object->idObject = $object->idStaticObject;
            $object->bbox = Criteria::byId("view_staticobject_boundingbox", "idStaticObject", $object->idObject);
            $object->fe = $fes[$object->idObject] ?? null;
            $object->lu = $lus[$object->idObject] ?? null;
            $objects[] = $object;
        }
        return $objects;
    }

    public static function updateObjectAnnotation(ObjectAnnotationData $data): int
    {
        $idUser = AppService::getCurrentIdUser();
        $sob = Criteria::byId("staticobject", "idStaticObject", $data->idObject);
        Criteria::table("annotation")
            ->where("idStaticObject", $data->idObject)
            ->update(['status' => 'DELETED']);
        if ($data->idFrameElement) {
            $fe = Criteria::byId("frameelement", "idFrameElement", $data->idFrameElement);
            $json = json_encode([
                'idEntity' => $fe->idEntity,
                'idStaticObject' => $data->idObject,
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
                'idStaticObject' => $data->idObject,
                'idUser' => $idUser
            ]);
            $idAnnotation = Criteria::function("annotation_create(?)", [$json]);
            Timeline::addTimeline("annotation", $idAnnotation, "C");
        }
        return $data->idObject;
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
        $idStaticObject = $data->idObject;
        $so = self::getObject($idStaticObject);
        $clone = json_encode([
            'name' => $so->name,
            'scene' => $so->scene ?? 0,
            'idFlickr30kEntitiesChain' => $so->idFlickr30kEntitiesChain ?? 0,
            'nobndbox' => $so->nobndbox ?? 0,
            'idLayerType' => $so->idLayerType,
            'origin' => (int)$so->origin,
            'idUser' => $idUser,
        ]);
        $idStaticObjectClone = Criteria::function('staticobject_create(?)', [$clone]);
//        $staticObjectClone = Criteria::byId('staticobject', 'idStaticObject', $idStaticObjectClone);
        $documentImage = Criteria::table('view_document_image')
            ->where('idDocument', $data->idDocument)
            ->first();
        $image = Image::byId($documentImage->idImage);
        // create relation video_dynamicobject
        Criteria::create('image_staticobject', [
            'idImage' => $image->idImage,
            'idStaticObject' => $idStaticObjectClone,
        ]);
        // cloning bbox
        $json = json_encode([
            'frameNumber' => 1,
            'frameTime' => 1,
            'x' => $so->bbox->x,
            'y' => $so->bbox->y,
            'width' => $so->bbox->width,
            'height' => $so->bbox->height,
            'blocked' => $so->bbox->blocked,
            'isGroundTruth' => $so->bbox->isGroundTruth,
            'idStaticObject' => $idStaticObjectClone,
        ]);
        $idBoundingBox = Criteria::function('boundingbox_static_create(?)', [$json]);
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
        debug($data);
        Criteria::table("boundingbox")
            ->where("idBoundingBox", $data->idBoundingBox)
            ->update($data->bbox);
        return $data->idBoundingBox;
    }

    public static function createObjectBBox(CreateBBoxData $data): object
    {
        // Para annotation image, a criação da BBox indica a criação de um novo Object
        $newObject = CreateObjectData::from([
            'annotationType' => 'staticBBox',
            'idDocument' => $data->idDocument,
            'idLayerType' => null
        ]);
        $object = self::createNewObjectAtLayer($newObject);
        debug($object);
        $json = json_encode([
            'frameNumber' => 1,
            'frameTime' => 1,
            'x' => (int)$data->bbox['x'],
            'y' => (int)$data->bbox['y'],
            'width' => (int)$data->bbox['width'],
            'height' => (int)$data->bbox['height'],
            'blocked' => (int)$data->bbox['blocked'],
            'isGroundTruth' => $data->bbox['isGroundTruth'] ? 1 : 0,
            'idStaticObject' => (int)$object->idStaticObject,
        ]);
        debug($json);
        $idBoundingBox = Criteria::function('boundingbox_static_create(?)', [$json]);
        debug($idBoundingBox);
        return $object;
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

    public static function objectSearch(ObjectSearchData $data)
    {
//        $objects = [];
//
//        if (!empty($data->frame) || !empty($data->lu) || !empty($data->searchIdLayerType) || ($data->idObject > 0)) {
        $idLanguage = AppService::getCurrentIdLanguage();

//            $result = Criteria::table("view_staticobject")
//                ->where("idLanguage", $idLanguage)
//                ->where("idDocument", $data->idDocument)
//                ->select("idStaticObject", "origin", "idImage", "idDocument")
//                ->orderBy("idStaticObject")
//                ->keyBy("idStaticObject")
//                ->all();

//            $fes = Criteria::table("view_staticobject as sob")
//                ->join("view_annotation_static_fe as fe", "sob.idStaticObject", "=", "fe.idStaticObject")
//                ->where("sob.idDocument", $data->idDocument)
//                ->where("fe.idLanguage", $idLanguage)
//                ->select("fe.idAnnotation", "fe.idStaticObject", "fe.idFrameElement", "fe.idFrame", "fe.frameName", "fe.name", "fe.idEntity", "fe.idLanguage", "fe.bgColor", "fe.fgColor")
//                ->keyBy("idStaticObject")
//                ->all();
//
//            $lus = Criteria::table("view_staticobject as sob")
//                ->join("view_annotation_static_lu as lu", "sob.idStaticObject", "=", "lu.idStaticObject")
//                ->where("sob.idDocument", $data->idDocument)
//                ->where("lu.idLanguage", $idLanguage)
//                ->select("lu.idAnnotation", "lu.idStaticObject", "lu.idLU", "lu.idEntity", "lu.name", "lu.frameName", "lu.idLanguage")
//                ->keyBy("idStaticObject")
//                ->all();

        $query = Criteria::table("view_staticobject as sob")
            ->leftJoin("view_annotation_static_fe as fe", "sob.idStaticObject", "=", "fe.idStaticObject")
            ->leftJoin("view_annotation_static_lu as lu", "sob.idStaticObject", "=", "lu.idStaticObject")
            ->where("sob.idLanguage", $idLanguage)
            ->where('fe.idLanguage', 'left', $idLanguage)
            ->where('lu.idLanguage', 'left', $idLanguage)
            ->where('sob.idDocument', $data->idDocument);

        if (!empty($data->frame)) {
            $query->whereRaw('(fe.frameName LIKE ? OR fe.name LIKE ?)', [
                $data->frame . '%',
                $data->frame . '%',
            ]);
        }

        if (!empty($data->lu)) {
            $searchTerm = '%' . $data->lu . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('lu.name', 'like', $searchTerm);
            });
        }

        if ($data->idObject != 0) {
            $query->where('sob.idStaticObject', $data->idObject);
        }

        $objects = $query
            ->select(
                'sob.idStaticObject as idObject',
                'sob.name',
                'lu.name as luName',
                'lu.frameName as luFrameName',
                'fe.frameName as feFrameName',
                'fe.name as feName'
            )
            ->orderBy('sob.idStaticObject')
            ->all();

        // Format search results for display
        foreach ($objects as $i => $object) {
            $object->order = ++$i;
            $object->fe = (object)[];
            $object->lu = (object)[];
            if (!is_null($object->luName)) {
                $object->lu->name = $object->luName;
                $object->lu->frameName = $object->luFrameName;
            }
            if (!is_null($object->fe)) {
                $object->fe->name = $object->feName;
                $object->fe->frameName = $object->feFrameName;
            }
        }
//        }

        return $objects;
    }


    public static function createNewObjectAtLayer(CreateObjectData $data): object
    {
        debug($data);
        if ($data->annotationType == 'staticBBox') {
            $layerType = Criteria::table("view_layertype")
                ->where("idLanguage", AppService::getCurrentIdLanguage())
                ->where("layerGroup", "StaticAnnotation")
                ->first();
            $data->idLayerType = $layerType->idLayerType;
        }
        $origin = ($data->annotationType == 'staticBBox') ? 2 : 1;
        $idUser = AppService::getCurrentIdUser();
        $so = json_encode([
            'name' => '',
            'idLayerType' => $data->idLayerType,
            'status' => 0,
            'origin' => $origin,
            'idUser' => $idUser,
        ]);
        $idStaticObject = Criteria::function('staticobject_create(?)', [$so]);
        $staticObject = Criteria::byId('staticobject', 'idStaticObject', $idStaticObject);
        $staticObject->idDocument = $data->idDocument;
//        Criteria::table('staticobject')
//            ->where('idStaticObject', $idStaticObject)
//            ->update(['idLayerType' => $data->idLayerType]);
        $documentImage = Criteria::table('view_document_image')
            ->where('idDocument', $data->idDocument)
            ->first();
        $image = Image::byId($documentImage->idImage);
        // create relation image_staticobject
        Criteria::create('image_staticobject', [
            'idImage' => $image->idImage,
            'idStaticObject' => $idStaticObject,
        ]);

        return $staticObject;
    }

}

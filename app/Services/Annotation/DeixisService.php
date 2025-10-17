<?php

namespace App\Services\Annotation;

use App\Data\Annotation\Deixis\CreateObjectData;
use App\Data\Annotation\Deixis\ObjectAnnotationData;
use App\Data\Annotation\Deixis\ObjectFrameData;
use App\Database\Criteria;
use App\Repositories\Task;
use App\Repositories\Timeline;
use App\Repositories\User;
use App\Repositories\Video;
use App\Services\AppService;
use App\Services\CommentService;
use Illuminate\Support\Facades\DB;

class DeixisService
{
    private static function deleteBBoxesByDynamicObject(int $idDynamicObject)
    {
        $bboxes = Criteria::table('view_dynamicobject_boundingbox as db')
            ->where('db.idDynamicObject', $idDynamicObject)
            ->select('db.idBoundingBox')
            ->chunkResult('idBoundingBox', 'idBoundingBox');
        Criteria::table('dynamicobject_boundingbox')
            ->whereIn('idBoundingBox', $bboxes)
            ->delete();
        Criteria::table('boundingbox')
            ->whereIn('idBoundingBox', $bboxes)
            ->delete();
    }

    public static function createNewObjectAtLayer(CreateObjectData $data): object
    {
        $idUser = AppService::getCurrentIdUser();
        $do = json_encode([
            'name' => '',
            'startFrame' => $data->startFrame,
            'endFrame' => $data->endFrame,
            'startTime' => ($data->startFrame - 1) * 0.040,
            'endTime' => ($data->endFrame) * 0.040,
            'status' => 0,
            'origin' => 5,
            'idUser' => $idUser,
        ]);
        $idDynamicObject = Criteria::function('dynamicobject_create(?)', [$do]);
        $dynamicObject = Criteria::byId('dynamicobject', 'idDynamicObject', $idDynamicObject);
        $dynamicObject->idDocument = $data->idDocument;
        Criteria::table('dynamicobject')
            ->where('idDynamicObject', $idDynamicObject)
            ->update(['idLayerType' => $data->idLayerType]);
        $documentVideo = Criteria::table('view_document_video')
            ->where('idDocument', $data->idDocument)
            ->first();
        $video = Video::byId($documentVideo->idVideo);
        // create relation video_dynamicobject
        Criteria::create('video_dynamicobject', [
            'idVideo' => $video->idVideo,
            'idDynamicObject' => $idDynamicObject,
        ]);

        return $dynamicObject;
    }

    public static function getObject(int $idDynamicObject): ?object
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $object = Criteria::table('view_annotation_deixis')
            ->where('idLanguageLT', 'left', $idLanguage)
            ->where('idLanguageFE', 'left', $idLanguage)
            ->where('idLanguageGL', 'left', $idLanguage)
            ->where('idDynamicObject', $idDynamicObject)
            ->select('idDynamicObject', 'name', 'startFrame', 'endFrame', 'startTime', 'endTime', 'status', 'origin', 'idLayerType', 'nameLayerType', 'idLanguageLT',
                'idAnnotationLU', 'idLU', 'lu', 'idAnnotationFE', 'idFrameElement', 'idFrame', 'frame', 'fe', 'colorFE', 'idLanguageFE',
                'idAnnotationGL', 'idGenericLabel', 'gl', 'bgColorGL', 'fgColorGL', 'idLanguageGL', 'layerGroup', 'idDocument')
            ->first();
        if (! is_null($object)) {
            $object->comment = CommentService::getDynamicObjectComment($idDynamicObject);
            $object->textComment = $object->comment?->comment;
            $object->bgColor = 'white';
            $object->fgColor = 'black';
            if ($object->gl != '') {
                $object->name = $object->gl;
                $object->bgColor = $object->bgColorGL;
                $object->fgColor = $object->fgColorGL;
            }
            if ($object->lu != '') {
                $object->name .= ' | '.$object->lu;
            }
            if ($object->fe != '') {
                $object->name .= ' | '.$object->frame.'.'.$object->fe;
            }
            $object->bboxes = Criteria::table('view_dynamicobject_boundingbox')
                ->where('idDynamicObject', $idDynamicObject)
                ->orderBy('frameNumber')
                ->all();
        }

        return $object;
    }

    public static function getLayersByDocument(int $idDocument): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $objects = Criteria::table('view_annotation_deixis as ad')
            ->join('layertype as lt', 'ad.idLayerType', '=', 'lt.idLayerType')
            ->join('layergroup as lg', 'lt.idLayerGroup', '=', 'lg.idLayerGroup')
            ->leftJoin('view_lu', 'ad.idLu', '=', 'view_lu.idLU')
            ->leftJoin('view_frame', 'view_lu.idFrame', '=', 'view_frame.idFrame')
            ->leftJoin('annotationcomment as ac', 'ad.idDynamicObject', '=', 'ac.idDynamicObject')
            ->where('ad.idLanguageFE', 'left', $idLanguage)
            ->where('ad.idLanguageGL', 'left', $idLanguage)
            ->where('ad.idLanguageLT', '=', $idLanguage)
            ->where('ad.idDocument', $idDocument)
            ->where('view_frame.idLanguage', 'left', $idLanguage)
            ->select('ad.idDynamicObject', 'ad.name', 'ad.startFrame', 'ad.endFrame', 'ad.startTime', 'ad.endTime', 'ad.status', 'ad.origin', 'ad.idLayerType', 'ad.nameLayerType',
                'ad.idAnnotationLU', 'ad.idLU', 'lu', 'view_lu.name as luName', 'view_frame.name as luFrameName', 'idAnnotationFE', 'idFrameElement', 'ad.idFrame', 'ad.frame', 'ad.fe', 'ad.colorFE',
                'ad.idAnnotationGL', 'ad.idGenericLabel', 'ad.gl', 'ad.bgColorGL', 'ad.fgColorGL', 'ad.layerGroup', 'ac.comment as textComment')
            ->orderBy('lg.name')
            ->orderBy('ad.nameLayerType')
            ->orderBy('ad.startFrame')
            ->orderBy('ad.endFrame')
            ->orderBy('ad.idDynamicObject')
            ->keyBy('idDynamicObject')
            ->all();
        $bboxes = [];
        $idDynamicObjectList = array_keys($objects);
        if (count($idDynamicObjectList) > 0) {
            $bboxList = Criteria::table('view_dynamicobject_boundingbox')
                ->whereIN('idDynamicObject', $idDynamicObjectList)
                ->all();
            foreach ($bboxList as $bbox) {
                $bboxes[$bbox->idDynamicObject][] = $bbox;
            }
        }
        $order = 0;
        foreach ($objects as $object) {
            $object->order = ++$order;
            $object->bgColorGL = '#'.$object->bgColorGL;
            $object->fgColorGL = '#'.$object->fgColorGL;
            $object->startTime = (int) ($object->startTime * 1000);
            $object->endTime = (int) ($object->endTime * 1000);
            $object->bboxes = $bboxes[$object->idDynamicObject] ?? [];
            $object->name = '';
            $object->bgColor = 'white';
            $object->fgColor = 'black';
            if ($object->gl != '') {
                $object->name = $object->gl;
                $object->bgColor = $object->bgColorGL;
                $object->fgColor = $object->fgColorGL;
            }
            if ($object->lu != '') {
                $object->name .= ' | '.$object->lu;
            }
            if ($object->fe != '') {
                $object->name .= ' | '.$object->frame.'.'.$object->fe;
            }
        }
        $objectsRows = [];
        $objectsRowsEnd = [];
        $idLayerTypeCurrent = 0;
        foreach ($objects as $i => $object) {
            if ($object->idLayerType != $idLayerTypeCurrent) {
                $idLayerTypeCurrent = $object->idLayerType;
                $objectsRows[$object->idLayerType][0][] = $object;
                $objectsRowsEnd[$object->idLayerType][0] = $object->endFrame;
            } else {
                $allocated = false;
                foreach ($objectsRows[$object->idLayerType] as $idLayer => $objectRow) {
                    if ($object->startFrame > $objectsRowsEnd[$object->idLayerType][$idLayer]) {
                        $objectsRows[$object->idLayerType][$idLayer][] = $object;
                        $objectsRowsEnd[$object->idLayerType][$idLayer] = $object->endFrame;
                        $allocated = true;
                        break;
                    }
                }
                if (! $allocated) {
                    $idLayer = count($objectsRows[$object->idLayerType]);
                    $objectsRows[$object->idLayerType][$idLayer][] = $object;
                    $objectsRowsEnd[$object->idLayerType][$idLayer] = $object->endFrame;
                }

            }
        }

        $result = [];
        foreach ($objectsRows as $idLayerType => $layers) {
            foreach ($layers as $idLayer => $objects) {
                $result[] = [
                    'layer' => $objects[0]->nameLayerType,
                    'objects' => $objects,
                ];
            }
        }

        return $result;
    }

    public static function updateObjectAnnotation(ObjectAnnotationData $data): object
    {
        $usertask = Task::getCurrentUserTask($data->idDocument);
        $do = Criteria::byId('dynamicobject', 'idDynamicObject', $data->idDynamicObject);
        Criteria::deleteById('annotation', 'idDynamicObject', $do->idDynamicObject);
        if ($data->idFrameElement) {
            $fe = Criteria::byId('frameelement', 'idFrameElement', $data->idFrameElement);
            $json = json_encode([
                'idEntity' => $fe->idEntity,
                'idDynamicObject' => $do->idDynamicObject,
                'idUserTask' => $usertask->idUserTask,
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$json]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }
        if ($data->idLU) {
            $lu = Criteria::byId('lu', 'idLU', $data->idLU);
            $json = json_encode([
                'idEntity' => $lu->idEntity,
                'idDynamicObject' => $do->idDynamicObject,
                'idUserTask' => $usertask->idUserTask,
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$json]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }
        if ($data->idGenericLabel) {
            $gl = Criteria::byId('genericlabel', 'idGenericLabel', $data->idGenericLabel);
            $json = json_encode([
                'idEntity' => $gl->idEntity,
                'idDynamicObject' => $do->idDynamicObject,
                'idUserTask' => $usertask->idUserTask,
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$json]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }

        return $do;
    }

    public static function deleteObject(int $idDynamicObject): void
    {
        // se pode remover o objeto se for Manager da task ou se for o criador do objeto
        $dynamicObjectAnnotation = Criteria::byId('view_annotation_deixis', 'idDynamicObject', $idDynamicObject);
        $taskManager = Task::getTaskManager($dynamicObjectAnnotation->idDocument);
        $idUser = AppService::getCurrentIdUser();
        $user = User::byId($idUser);
        if (! User::isManager($user)) {
            if ($taskManager->idUser != $idUser) {
                $tl = Criteria::table('timeline')
                    ->where('tablename', 'dynamicobject')
                    ->where('id', $idDynamicObject)
                    ->select('idUser')
                    ->first();
                if ($tl->idUser != $idUser) {
                    throw new \Exception('Object can not be removed.');
                }
            }
        }
        DB::transaction(function () use ($idDynamicObject) {
            self::deleteBBoxesByDynamicObject($idDynamicObject);
            $idUser = AppService::getCurrentIdUser();
            Criteria::function('dynamicobject_delete(?,?)', [$idDynamicObject, $idUser]);
        });
    }

    public static function updateObjectFrame(ObjectFrameData $data): int
    {
        $object = self::getObject($data->idDynamicObject);
        debug($object->bboxes);
        if (! empty($object->bboxes)) {
            $frameFirstBBox = $object->bboxes[0]->frameNumber;
            // se o novo startFrame for menor que o atual, remove todas as bboxes
            if ($data->startFrame < $frameFirstBBox) {
                self::deleteBBoxesByDynamicObject($data->idDynamicObject);
            } else {
                $idUser = AppService::getCurrentIdUser();
                // remove as bboxes em frames menores que o newStartFrame
                $bboxes = Criteria::table('view_dynamicobject_boundingbox')
                    ->where('idDynamicObject', $data->idDynamicObject)
                    ->where('frameNumber', '<', $data->startFrame)
                    ->chunkResult('idBoundingBox', 'idBoundingBox');
                foreach ($bboxes as $idBoundingBox) {
                    Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
                }
                // remove as bboxes em frames maiores que o newEndFrame
                $bboxes = Criteria::table('view_dynamicobject_boundingbox')
                    ->where('idDynamicObject', $data->idDynamicObject)
                    ->where('frameNumber', '>', $data->endFrame)
                    ->chunkResult('idBoundingBox', 'idBoundingBox');
                foreach ($bboxes as $idBoundingBox) {
                    Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
                }
            }
        }
        Criteria::table('dynamicobject')
            ->where('idDynamicObject', $data->idDynamicObject)
            ->update([
                'startFrame' => $data->startFrame,
                'endFrame' => $data->endFrame,
                'startTime' => $data->startTime,
                'endTime' => $data->endTime,
            ]);

        return $data->idDynamicObject;
    }

    public static function deleteBBoxesFromObject(int $idDynamicObject): int
    {
        $idUser = AppService::getCurrentIdUser();
        $bboxes = Criteria::table('view_dynamicobject_boundingbox')
            ->where('idDynamicObject', $idDynamicObject)
            ->chunkResult('idBoundingBox', 'idBoundingBox');
        foreach ($bboxes as $idBoundingBox) {
            Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
        }

        return $idDynamicObject;
    }
}

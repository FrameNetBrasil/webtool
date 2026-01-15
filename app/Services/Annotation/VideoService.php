<?php

namespace App\Services\Annotation;

use App\Data\Annotation\Video\CloneData;
use App\Data\Annotation\Video\CreateBBoxData;
use App\Data\Annotation\Video\CreateObjectData;
use App\Data\Annotation\Video\ObjectAnnotationData;
use App\Data\Annotation\Video\ObjectFrameData;
use App\Data\Annotation\Video\ObjectLayerLabelData;
use App\Data\Annotation\Video\ObjectSearchData;
use App\Data\Annotation\Video\UpdateBBoxData;
use App\Database\Criteria;
use App\Enum\AnnotationType;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\Timeline;
use App\Repositories\User;
use App\Repositories\Video;
use App\Services\AppService;
use App\Services\CommentService;
use Illuminate\Support\Facades\DB;

class VideoService
{
    public static function getResourceData(int $idDocument, ?int $idObject = null, string $annotationType = '', ?int $frameNumber = null): array
    {
        $document = Document::byId($idDocument);
        if (! $document) {
            throw new \Exception("Document with ID {$idDocument} not found.");
        }

        $corpus = Corpus::byId($document->idCorpus);
        if (! $corpus) {
            throw new \Exception("Corpus with ID {$document->idCorpus} not found.");
        }

        $documentVideo = Criteria::table('view_document_video')
            ->where('idDocument', $idDocument)
            ->first();
        if (! $documentVideo) {
            throw new \Exception("Video not found for document ID {$idDocument}.");
        }

        $video = Video::byId($documentVideo->idVideo);
        if (! $video) {
            throw new \Exception("Video with ID {$documentVideo->idVideo} not found.");
        }
        $timelineData = self::getLayersByDocument($idDocument, $annotationType);
        $timelineConfig = self::getTimelineConfig($timelineData);
        $groupedLayers = self::groupLayersByName($timelineData);

        $at = ($annotationType == 'deixis') ? AnnotationType::DEIXIS->value : (($annotationType == 'canvas') ? AnnotationType::CANVAS->value : AnnotationType::DYNAMICMODE->value);
        $comment = $idObject ? CommentService::getComment($idObject, $idDocument, $at) : null;

        return [
            'idDocument' => $idDocument,
            'document' => $document,
            'corpus' => $corpus,
            'video' => $video,
            'annotationType' => $annotationType,
            'fragment' => 'fe',
            'searchResults' => [],
            'timeline' => [
                'data' => $timelineData,
                'config' => $timelineConfig,
            ],
            'groupedLayers' => $groupedLayers,
            'idObject' => is_null($idObject) ? 0 : $idObject,
            'frameNumber' => is_null($frameNumber) ? 0 : $frameNumber,
            'comment' => $comment,
        ];

    }

    public static function getLayersByDocument(int $idDocument, string $annotationType): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $view = 'view_annotation_dynamic as ad';
        if ($annotationType == 'deixis') {
            $view = 'view_annotation_deixis as ad';
        }
        if ($annotationType == 'canvas') {
            $view = 'view_annotation_canvas as ad';
        }
        $objects = Criteria::table($view)
            ->where('ad.idLanguage', $idLanguage)
            ->where('ad.idDocument', $idDocument)
            ->select('ad.idDynamicObject as idObject', 'ad.name', 'ad.startFrame', 'ad.endFrame', 'ad.startTime', 'ad.endTime', 'ad.status', 'ad.origin',
                'ad.layerGroup', 'ad.layerOrder', 'ad.idLayerType', 'ad.nameLayerType',
                'ad.idAnnotationGL', 'ad.idGenericLabel', 'ad.gl',
                'ad.idAnnotationLU', 'ad.idLU', 'ad.lu', 'ad.lu as luName', 'ad.frame as luFrameName', 'ad.idAnnotationFE', 'ad.idFrameElement', 'ad.idFrame', 'ad.frame', 'ad.fe',
                'ad.fgColorGL', 'ad.bgColorGL', 'ad.fgColorFE', 'ad.bgColorFE')
            ->orderBy('ad.layerGroup')
            ->orderBy('ad.nameLayerType')
            ->orderBy('ad.startFrame')
            ->orderBy('ad.endFrame')
            ->orderBy('ad.idDynamicObject')
            ->keyBy('idObject')
            ->all();
        $countBBoxes = [];
        $idDynamicObjectList = array_keys($objects);
        if (count($idDynamicObjectList) > 0) {
            $bboxes = Criteria::table('view_dynamicobject_boundingbox')
                ->whereIN('idDynamicObject', $idDynamicObjectList)
                ->select('idDynamicObject')
                ->selectRaw('count(*) as count')
                ->all();
            foreach ($bboxes as $bbox) {
                $countBBoxes[$bbox->idDynamicObject][] = $bbox->count;
            }
        }
        $order = 0;
        foreach ($objects as $object) {
            $object->comment = CommentService::getComment($object->idObject, $idDocument, $annotationType);
            $object->order = ++$order;
            $object->startTime = (int) ($object->startTime * 1000);
            $object->endTime = (int) ($object->endTime * 1000);
            $object->bboxes = $bboxes[$object->idObject] ?? [];
            if ($object->gl == '') {
                $object->name = '';
                $object->bgColor = 'white';
                $object->fgColor = 'black';
                if ($object->lu != '') {
                    $object->name .= $object->lu;
                }
                if ($object->fe != '') {
                    $object->bgColor = "#{$object->bgColorFE}";
                    $object->fgColor = "#{$object->fgColorFE}";
                    $object->name .= ($object->name != '' ? ' | ' : '').$object->frame.'.'.$object->fe;
                }
            } else {
                $object->name = $object->gl;
                $object->bgColor = "#{$object->bgColorGL}";
                $object->fgColor = "#{$object->fgColorGL}";
            }
            $object->hasBBoxes = $countBBoxes[$object->idObject] ?? 0;
        }
        $objectsRows = [];
        $objectsRowsEnd = [];
        // estou considerando que todos os objetos estão num "layertype"
        $idLayerTypeCurrent = -1;
        $idLayerType = 0;
        foreach ($objects as $i => $object) {
            if ($idLayerType != $idLayerTypeCurrent) {

                $idLayerTypeCurrent = $idLayerType;
                $objectsRows[$idLayerType][0][] = $object;
                $objectsRowsEnd[$idLayerType][0] = $object->endFrame;
            } else {
                $allocated = false;
                foreach ($objectsRows[$idLayerType] as $idLayer => $objectRow) {
                    if ($object->startFrame > $objectsRowsEnd[$idLayerType][$idLayer]) {
                        $objectsRows[$idLayerType][$idLayer][] = $object;
                        $objectsRowsEnd[$idLayerType][$idLayer] = $object->endFrame;
                        $allocated = true;
                        break;
                    }
                }
                if (! $allocated) {
                    $idLayer = count($objectsRows[$idLayerType]);
                    $objectsRows[$idLayerType][$idLayer][] = $object;
                    $objectsRowsEnd[$idLayerType][$idLayer] = $object->endFrame;
                }
            }
            $idLayerType = $object->idLayerType;
        }
        $result = [];
        if (($annotationType == 'deixis') || ($annotationType == 'canvas')) {
            foreach ($objectsRows as $layers) {
                foreach ($layers as $objects) {
                    $result[] = [
                        'layer' => $objects[0]->nameLayerType,
                        'objects' => $objects,
                    ];
                }
            }
        } else {
            foreach ($objectsRows as $layers) {
                foreach ($layers as $objects) {
                    $result[] = [
                        'layer' => 'Single_layer',
                        'objects' => $objects,
                    ];
                }
            }
        }
//        debug($result);

        return $result;
    }

    /**
     * timeline
     */
    private static function getTimelineConfig($timelineData): array
    {
        $maxFrame = PHP_INT_MIN;

        foreach ($timelineData as $layer) {
            foreach ($layer['objects'] as $object) {
                $maxFrame = max($maxFrame, $object->endFrame);
            }
        }

        // Add padding to maxFrame
        $maxFrame = $maxFrame + 100;

        return [
            'minFrame' => 0,
            'maxFrame' => $maxFrame,
            'frameToPixel' => 1,
            'minObjectWidth' => 16,
            'objectHeight' => 24,
            'labelWidth' => 150,
            'timelineWidth' => $maxFrame * 1,
            'timelineHeight' => (24 * count($timelineData)) + 10,
        ];
    }

    private static function groupLayersByName($timelineData): array
    {
        $layerGroups = [];

        foreach ($timelineData as $originalIndex => $layer) {
            $layerName = $layer['layer'];

            if (! isset($layerGroups[$layerName])) {
                $layerGroups[$layerName] = [
                    'name' => $layerName,
                    'lines' => [],
                ];
            }

            $layerGroups[$layerName]['lines'][] = array_merge($layer, [
                'originalIndex' => $originalIndex,
            ]);
        }

        return array_values($layerGroups);
    }

    public static function getObject(ObjectSearchData $data): ?object
    {
        $view = 'view_annotation_dynamic as ad';
        if ($data->annotationType == 'deixis') {
            $view = 'view_annotation_deixis as ad';
        }
        if ($data->annotationType == 'canvas') {
            $view = 'view_annotation_canvas as ad';
        }
        $idObject = $data->idObject;
        $idLanguage = AppService::getCurrentIdLanguage();

        $object = Criteria::table($view)
            ->where('ad.idLanguage', $idLanguage)
            ->where('ad.idDynamicObject', $idObject)
            ->select('ad.idDynamicObject as idObject', 'ad.name', 'ad.startFrame', 'ad.endFrame', 'ad.startTime', 'ad.endTime', 'ad.status', 'ad.origin',
                'ad.layerGroup', 'ad.layerOrder', 'ad.idLayerType', 'ad.nameLayerType',
                'ad.idAnnotationGL', 'ad.idGenericLabel', 'ad.gl',
                'ad.idAnnotationLU', 'ad.idLU', 'ad.lu', 'ad.lu as luName', 'ad.frame as luFrameName', 'ad.idAnnotationFE', 'ad.idFrameElement', 'ad.idFrame', 'ad.frame', 'ad.fe',
                'ad.fgColorGL', 'ad.bgColorGL', 'ad.fgColorFE', 'ad.bgColorFE')
            ->first();
        if (! is_null($object)) {
            $object->idDocument = $data->idDocument;
            $object->comment = CommentService::getComment($object->idObject, $data->idDocument, $data->annotationType);
            $object->startTime = (int) ($object->startTime * 1000);
            $object->endTime = (int) ($object->endTime * 1000);
            $object->bboxes = $bboxes[$object->idObject] ?? [];
            if ($object->gl == '') {
                $object->name = '';
                $object->bgColor = 'white';
                $object->fgColor = 'black';
                if ($object->lu != '') {
                    $object->name .= $object->lu;
                }
                if ($object->fe != '') {
                    $object->bgColor = "#{$object->bgColorFE}";
                    $object->fgColor = "#{$object->fgColorFE}";
                    $object->name .= ($object->name != '' ? ' | ' : '').$object->frame.'.'.$object->fe;
                }
            } else {
                $object->name = $object->gl;
                $object->bgColor = "#{$object->bgColorGL}";
                $object->fgColor = "#{$object->fgColorGL}";
            }
            $object->bboxes = Criteria::table('view_dynamicobject_boundingbox')
                ->where('idDynamicObject', $idObject)
                ->keyBy('frameNumber')
                ->all();
            $object->hasBBoxes = (count($object->bboxes) > 0);
        }

        return $object;
    }

    public static function objectSearch(ObjectSearchData $data)
    {
        $view = ($data->annotationType == 'deixis') ? 'view_annotation_deixis as ad' : 'view_annotation_dynamic as ad';

        $searchResults = [];

        //        if (! empty($data->frame) || ! empty($data->lu) || ! empty($data->searchIdLayerType) || ($data->idObject > 0)) {
        $idLanguage = AppService::getCurrentIdLanguage();

        $query = Criteria::table($view)
            ->where('ad.idLanguage', 'left', $idLanguage)
            ->where('ad.idDocument', $data->idDocument);

        if (! empty($data->frame)) {
            $query->whereRaw('(ad.frame LIKE ? OR ad.fe LIKE ?)', [
                $data->frame.'%',
                $data->frame.'%',
            ]);
        }

        if (! empty($data->lu)) {
            $searchTerm = '%'.$data->lu.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('ad.lu', 'like', $searchTerm);
            });
        }

        if ($data->idObject != 0) {
            $query->where('ad.idDynamicObject', $data->idObject);
        }

        if ($data->useFrameNumber > 0) {
            $query->where('ad.startFrame', "<=" ,$data->frameNumber);
            $query->where('ad.endFrame', ">=" ,$data->frameNumber);
        }

        $searchResults = $query
            ->select(
                'ad.idDynamicObject as idObject',
                'ad.name',
                'ad.startFrame',
                'ad.endFrame',
                'ad.startTime',
                'ad.endTime',
                'ad.lu',
                'ad.frame',
                'ad.fe'
            )
            ->orderBy('ad.startFrame')
            ->orderBy('ad.endFrame')
            ->orderBy('ad.idDynamicObject')
            ->all();

        // Format search results for display
        foreach ($searchResults as $object) {
            $object->displayName = '';
            if (! empty($object->lu)) {
                $object->displayName .= ($object->displayName ? ' | ' : '').$object->lu;
            }
            if (! empty($object->fe)) {
                $object->displayName .= ($object->displayName ? ' | ' : '').$object->frame.'.'.$object->fe;
            }
            if (empty($object->displayName)) {
                $object->displayName = 'None';
            }
        }
        //        }

        return $searchResults;
    }

    public static function createNewObjectAtLayer(CreateObjectData $data): object
    {
        if ($data->annotationType == 'dynamicMode') {
            $layerType = Criteria::table('view_layertype')
                ->where('idLanguage', AppService::getCurrentIdLanguage())
                ->where('layerGroup', 'DynamicAnnotation')
                ->first();
            $data->idLayerType = $layerType->idLayerType;
            $data->endFrame = $data->startFrame;
        }
        $origin = 1;
        if ($data->annotationType == 'deixis') {
            $origin = 5;
        }
        if ($data->annotationType == 'canvas') {
            $origin = 6;
        }
        $idUser = AppService::getCurrentIdUser();
        $do = json_encode([
            'name' => '',
            'startFrame' => $data->startFrame,
            'endFrame' => $data->endFrame,
            'startTime' => ($data->startFrame - 1) * 0.040,
            'endTime' => ($data->endFrame) * 0.040,
            'idLayerType' => $data->idLayerType,
            'status' => 0,
            'origin' => $origin,
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

    public static function updateObjectFrame(ObjectFrameData $data): int
    {
        $searchData = ObjectSearchData::from($data);
        $object = self::getObject($searchData);
        $object->bboxes = Criteria::table('view_dynamicobject_boundingbox')
            ->where('idDynamicObject', $data->idObject)
            ->orderBy('frameNumber')
            ->all();
//        debug($object->bboxes);
        if (! empty($object->bboxes)) {
            $frameFirstBBox = $object->bboxes[0]->frameNumber;
            // se o novo startFrame for menor que o atual, remove todas as bboxes
            if ($data->startFrame < $frameFirstBBox) {
                self::deleteBBoxesByObject($data->idObject);
            } else {
                $idUser = AppService::getCurrentIdUser();
                // remove as bboxes em frames menores que o newStartFrame
                $bboxes = Criteria::table('view_dynamicobject_boundingbox')
                    ->where('idDynamicObject', $data->idObject)
                    ->where('frameNumber', '<', $data->startFrame)
                    ->chunkResult('idBoundingBox', 'idBoundingBox');
                foreach ($bboxes as $idBoundingBox) {
                    Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
                }
                // remove as bboxes em frames maiores que o newEndFrame
                $bboxes = Criteria::table('view_dynamicobject_boundingbox')
                    ->where('idDynamicObject', $data->idObject)
                    ->where('frameNumber', '>', $data->endFrame)
                    ->chunkResult('idBoundingBox', 'idBoundingBox');
                foreach ($bboxes as $idBoundingBox) {
                    Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
                }
            }
        }
        Criteria::table('dynamicobject')
            ->where('idDynamicObject', $data->idObject)
            ->update([
                'startFrame' => $data->startFrame,
                'endFrame' => $data->endFrame,
                'startTime' => $data->startTime,
                'endTime' => $data->endTime,
            ]);

        return $data->idObject;
    }

    public static function updateLayerLabel(ObjectLayerLabelData $data): int
    {
        $searchData = ObjectSearchData::from($data);
        $object = self::getObject($searchData);
        if ($data->idGenericLabelNew) {
            $gl = Criteria::byId('genericlabel', 'idGenericLabel', $data->idGenericLabelNew);
            $annotation = json_encode([
                'idDynamicObject' => $object->idObject,
                'idEntity' => $gl->idEntity,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$annotation]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }
        Criteria::table('dynamicobject')
            ->where('idDynamicObject', $data->idObject)
            ->update([
                'idLayerType' => $data->idLayerTypeNew
            ]);

        return $data->idObject;
    }
    private static function deleteBBoxesByObject(int $idObject)
    {
        $bboxes = Criteria::table('view_dynamicobject_boundingbox as db')
            ->where('db.idDynamicObject', $idObject)
            ->select('db.idBoundingBox')
            ->chunkResult('idBoundingBox', 'idBoundingBox');
        Criteria::table('dynamicobject_boundingbox')
            ->whereIn('idBoundingBox', $bboxes)
            ->delete();
        Criteria::table('boundingbox')
            ->whereIn('idBoundingBox', $bboxes)
            ->delete();
    }

    public static function updateObjectAnnotation(ObjectAnnotationData $data): int
    {
        //        $usertask = Task::getCurrentUserTask($data->idDocument);
        $do = Criteria::byId('dynamicobject', 'idDynamicObject', $data->idObject);
        Criteria::deleteById('annotation', 'idDynamicObject', $do->idDynamicObject);
        if ($data->idFrameElement) {
            $fe = Criteria::byId('frameelement', 'idFrameElement', $data->idFrameElement);
            $annotation = json_encode([
                'idDynamicObject' => $do->idDynamicObject,
                'idEntity' => $fe->idEntity,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$annotation]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }
        if ($data->idLU) {
            $lu = Criteria::byId('lu', 'idLU', $data->idLU);
            $annotation = json_encode([
                'idDynamicObject' => $do->idDynamicObject,
                'idEntity' => $lu->idEntity,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$annotation]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }
        if ($data->idGenericLabel) {
            $gl = Criteria::byId('genericlabel', 'idGenericLabel', $data->idGenericLabel);
            $annotation = json_encode([
                'idDynamicObject' => $do->idDynamicObject,
                'idEntity' => $gl->idEntity,
                'idUser' => AppService::getCurrentIdUser(),
            ]);
            $idAnnotation = Criteria::function('annotation_create(?)', [$annotation]);
            Timeline::addTimeline('annotation', $idAnnotation, 'C');
        }

        return $data->idObject;
    }

    public static function deleteBBoxesFromObject(int $idDynamicObject): int
    {
        $idUser = AppService::getCurrentIdUser();
        $bboxes = Criteria::table('dynamicobject_boundingbox')
            ->where('idDynamicObject', $idDynamicObject)
            ->chunkResult('idBoundingBox', 'idBoundingBox');
        foreach ($bboxes as $idBoundingBox) {
            Criteria::function('boundingbox_dynamic_delete(?,?)', [$idBoundingBox, $idUser]);
        }

        return $idDynamicObject;
    }

    public static function deleteObject(int $idObject): void
    {
        // se pode remover o objeto se for Manager ou se for o criador do objeto
        $idUser = AppService::getCurrentIdUser();
        $user = User::byId($idUser);
        if (! User::isManager($user)) {
            $tl = Criteria::table('timeline')
                ->where('tablename', 'dynamicobject')
                ->where('id', $idObject)
                ->select('idUser')
                ->first();
            if ($tl->idUser != $idUser) {
                throw new \Exception('Object can not be removed.');
            }
        }
        DB::transaction(function () use ($idObject) {
            // remove boundingbox
            self::deleteBBoxesByObject($idObject);
            // remove dynamicobject
            $idUser = AppService::getCurrentIdUser();
            Criteria::function('dynamicobject_delete(?,?)', [$idObject, $idUser]);
        });
    }

    public static function cloneObject(CloneData $data): int
    {
        $idUser = AppService::getCurrentIdUser();
        $idDynamicObject = $data->idObject;
        $searchData = ObjectSearchData::from($data);
        $do = self::getObject($searchData);
        $clone = json_encode([
            'name' => '',
            'startFrame' => (int) $do->startFrame,
            'endFrame' => (int) $do->endFrame,
            'startTime' => (float) $do->startTime,
            'endTime' => (float) $do->endTime,
            'status' => (int) $do->status,
            'origin' => (int) $do->origin,
            'idLayerType' => (int) $do->idLayerType,
            'idUser' => $idUser,
        ]);
        $idDynamicObjectClone = Criteria::function('dynamicobject_create(?)', [$clone]);
        $dynamicObjectClone = Criteria::byId('dynamicobject', 'idDynamicObject', $idDynamicObjectClone);
        $documentVideo = Criteria::table('view_document_video')
            ->where('idDocument', $data->idDocument)
            ->first();
        $video = Video::byId($documentVideo->idVideo);
        // create relation video_dynamicobject
        Criteria::create('video_dynamicobject', [
            'idVideo' => $video->idVideo,
            'idDynamicObject' => $idDynamicObjectClone,
        ]);
        // cloning bboxes
        $bboxes = Criteria::table('view_dynamicobject_boundingbox')
            ->where('idDynamicObject', $idDynamicObject)
            ->all();
        foreach ($bboxes as $bbox) {
            $json = json_encode([
                'frameNumber' => (int) $bbox->frameNumber,
                'frameTime' => (float) $bbox->frameTime,
                'x' => (int) $bbox->x,
                'y' => (int) $bbox->y,
                'width' => (int) $bbox->width,
                'height' => (int) $bbox->height,
                'blocked' => (int) $bbox->blocked,
                'idDynamicObject' => (int) $idDynamicObjectClone,
            ]);
            $idBoundingBox = Criteria::function('boundingbox_dynamic_create(?)', [$json]);
        }

        return $idDynamicObjectClone;
    }

    public static function createBBox(CreateBBoxData $data): int
    {
//        debug($data);
        $boundingBox = Criteria::table('dynamicobject_boundingbox as dbb')
            ->join('boundingbox as bb', 'dbb.idBoundingBox', '=', 'bb.idBoundingBox')
            ->where('dbb.idDynamicObject', $data->idObject)
            ->where('bb.frameNumber', $data->frameNumber)
            ->first();
        if ($boundingBox) {
            Criteria::function('boundingbox_dynamic_delete(?,?)', [$boundingBox->idBoundingBox, AppService::getCurrentIdUser()]);
        }
        $dynamicObject = Criteria::byId('dynamicobject', 'idDynamicObject', $data->idObject);
        if ($dynamicObject->endFrame < $data->frameNumber) {
            Criteria::table('dynamicobject')
                ->where('idDynamicObject', $data->idObject)
                ->update(['endFrame' => $data->frameNumber]);
        }
        $json = json_encode([
            'frameNumber' => (int) $data->frameNumber,
            'frameTime' => $data->frameNumber * 0.04,
            'x' => (int) $data->bbox['x'],
            'y' => (int) $data->bbox['y'],
            'width' => (int) $data->bbox['width'],
            'height' => (int) $data->bbox['height'],
            'blocked' => (int) $data->bbox['blocked'],
            'isGroundTruth' => $data->bbox['isGroundTruth'] ? 1 : 0,
            'idDynamicObject' => (int) $data->idObject,
        ]);
        $idBoundingBox = Criteria::function('boundingbox_dynamic_create(?)', [$json]);

        return $idBoundingBox;
    }

    public static function updateBBox(UpdateBBoxData $data): int
    {
        Criteria::table('boundingbox')
            ->where('idBoundingBox', $data->idBoundingBox)
            ->update($data->bbox);

        return $data->idBoundingBox;
    }
}

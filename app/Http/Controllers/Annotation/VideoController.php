<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Video\CloneData;
use App\Data\Annotation\Video\CreateBBoxData;
use App\Data\Annotation\Video\CreateObjectData;
use App\Data\Annotation\Video\GetBBoxData;
use App\Data\Annotation\Video\ObjectAnnotationData;
use App\Data\Annotation\Video\ObjectFrameData;
use App\Data\Annotation\Video\ObjectLayerLabelData;
use App\Data\Annotation\Video\ObjectSearchData;
use App\Data\Annotation\Video\UpdateBBoxData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use App\Services\Annotation\VideoService;
use App\Services\CommentService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class VideoController extends Controller
{
    #[Get(path: '/annotation/video/script/{folder}')]
    public function jsObjects(string $folder)
    {
        return response()
            ->view("Annotation.Video.Scripts.{$folder}")
            ->header('Content-type', 'text/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Get(path: '/annotation/video/object')]
    public function getObject(ObjectSearchData $data)
    {
        if ($data->idObject == 0) {
            return view('Annotation.Video.Forms.formNewObject');
        }
        $object = VideoService::getObject($data);
        $object->annotationType = $data->annotationType;
        $object->frameNumber = $data->frameNumber;
        if (is_null($object)) {
            return $this->renderNotify('error', 'Object not found.');
        }

        $comment = CommentService::getComment($data->idObject, $data->idDocument, $data->annotationType);

        return response()
            ->view('Annotation.Video.Panes.object', [
                'object' => $object,
                'annotationType' => $data->annotationType,
                'comment' => $comment,
            ])->header('HX-Push-Url', "/annotation/{$data->annotationType}/{$data->idDocument}/{$object->idObject}");
    }

    #[Post(path: '/annotation/video/object/search')]
    public function objectSearch(ObjectSearchData $data)
    {
        $searchResults = VideoService::objectSearch($data);

        return view('Annotation.Video.Panes.search', [
            'searchResults' => $searchResults,
            'idDocument' => $data->idDocument,
            'annotationType' => $data->annotationType,
        ])->fragment('search');
    }

    #[Get(path: '/annotation/video/labels/{idLayerType}')]
    public function getLabels(int $idLayerType)
    {
        $object = (object)[
            "idLayerType" => $idLayerType,
        ];
        return view('Annotation.Video.Partials.comboboxLabel', [
            'object' => $object,
        ]);
    }

    #[Post(path: '/annotation/video/updateLayerLabel}')]
    public function updateLayerLabel(ObjectLayerLabelData $data)
    {
        try {
            VideoService::updateLayerLabel($data);

            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$data->idObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }

    }

    #[Post(path: '/annotation/video/createNewObjectAtLayer')]
    public function createNewObjectAtLayer(CreateObjectData $data)
    {
//        debug($data);
        try {
            $object = VideoService::createNewObjectAtLayer($data);
            if ($data->annotationType == 'dynamicAnnotation') {
                $this->trigger('goto-bbox');
            }

            return $this->redirect("/annotation/{$data->annotationType}/{$object->idDocument}/{$object->idDynamicObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/video/cloneObject')]
    public function cloneObject(CloneData $data)
    {
        try {
            $idDynamicObjectClone = VideoService::cloneObject($data);

            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$idDynamicObjectClone}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/video/updateObjectAnnotation')]
    public function updateObjectAnnotation(ObjectAnnotationData $data)
    {
//        debug($data);
        try {
            $idDynamicObject = VideoService::updateObjectAnnotation($data);
            $this->trigger('updateObjectAnnotationEvent');

            // return Criteria::byId("dynamicobject", "idDynamicObject", $idDynamicObject);
            //return $this->renderNotify('success', 'Object updated.');
            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$idDynamicObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/video/updateObjectRange')]
    public function updateObjectRange(ObjectFrameData $data)
    {
        try {
//            debug($data);
            VideoService::updateObjectFrame($data);

            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$data->idObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/annotation/video/getBBox')]
    public function getBBox(GetBBoxData $data)
    {
        try {
            return Criteria::table('view_dynamicobject_boundingbox')
                ->where('idDynamicObject', $data->idObject)
                ->where('frameNumber', $data->frameNumber)
                ->first();
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/video/createBBox')]
    public function createBBox(CreateBBoxData $data)
    {
//        debug($data);
        try {
            return VideoService::createBBox($data);
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/video/updateBBox')]
    public function updateBBox(UpdateBBoxData $data)
    {
        try {
            $idBoundingBox = VideoService::updateBBox($data);
            $boundingBox = Criteria::byId('boundingbox', 'idBoundingBox', $idBoundingBox);
            if (! $boundingBox) {
                return $this->renderNotify('error', 'Updated bounding box not found.');
            }

            return $boundingBox;
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/annotation/video/getAllBBoxes/{idDocument}/{frameNumber}')]
    public function getAllBBoxes(int $idDocument, int $frameNumber)
    {
        try {
            $idLanguage = AppService::getCurrentIdLanguage();

            $bboxes = Criteria::table('view_dynamicobject_boundingbox as db')
                ->join("video_dynamicobject as do", "db.idDynamicObject", "=", "do.idDynamicObject")
                ->join("document_video as vd", "do.idVideo", "=", "vd.idVideo")
                ->join("view_annotation_dynamic as ad", function($join) use ($idLanguage) {
                    $join->on("do.idDynamicObject", "=", "ad.idDynamicObject")
                         ->where("ad.idLanguage", "=", $idLanguage);
                })
                ->where('vd.idDocument', $idDocument)
                ->where('db.frameNumber', $frameNumber)
                ->select(
                    'db.idBoundingBox', 'db.idDynamicObject', 'db.frameNumber',
                    'db.x', 'db.y', 'db.width', 'db.height', 'db.blocked',
                    'ad.gl', 'ad.lu', 'ad.fe', 'ad.frame',
                    'ad.fgColorGL', 'ad.bgColorGL', 'ad.fgColorFE', 'ad.bgColorFE'
                )
                ->all();

            // Process each bbox to compute final colors based on annotation type
            foreach ($bboxes as $bbox) {
                if ($bbox->gl == '') {
                    // No generic label - check for frame element colors
                    if ($bbox->fe != '') {
                        $bbox->bgColor = "#{$bbox->bgColorFE}";
                        $bbox->fgColor = "#{$bbox->fgColorFE}";
                    } else {
                        // Default colors
                        $bbox->bgColor = 'white';
                        $bbox->fgColor = 'black';
                    }
                } else {
                    // Generic label exists - use GL colors
                    $bbox->bgColor = "#{$bbox->bgColorGL}";
                    $bbox->fgColor = "#{$bbox->fgColorGL}";
                }

                // Clean up - remove the raw color fields from response
                unset($bbox->fgColorGL, $bbox->bgColorGL, $bbox->fgColorFE, $bbox->bgColorFE);
                unset($bbox->gl, $bbox->lu, $bbox->fe, $bbox->frame);
            }

            return $bboxes;
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}

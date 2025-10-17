<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Image\CloneData;
use App\Data\Annotation\Image\CreateBBoxData;
use App\Data\Annotation\Image\CreateObjectData;
use App\Data\Annotation\Image\GetBBoxData;
use App\Data\Annotation\Image\ObjectAnnotationData;
use App\Data\Annotation\Image\ObjectFrameData;
use App\Data\Annotation\Image\ObjectSearchData;
use App\Data\Annotation\Image\UpdateBBoxData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\Annotation\ImageService;
use App\Services\CommentService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class ImageController extends Controller
{
    #[Get(path: '/annotation/image/script/{folder}')]
    public function jsObjects(string $folder)
    {
        return response()
            ->view("Annotation.Image.Scripts.{$folder}")
            ->header('Content-type', 'text/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Get(path: '/annotation/image/object')]
    public function getObject(ObjectSearchData $data)
    {
        debug($data);
        if ($data->idObject == 0) {
            return view('Annotation.Image.Forms.formNewObject');
        }
        $object = ImageService::getObject($data->idObject);
        debug($object);
        $object->annotationType = $data->annotationType;
        if (is_null($object)) {
            return $this->renderNotify('error', 'Object not found.');
        }

        $comment = CommentService::getComment($data->idObject, $data->idDocument, $data->annotationType);

        return response()
            ->view('Annotation.Image.Panes.object', [
                'object' => $object,
                'annotationType' => $data->annotationType,
                'comment' => $comment,
            ])->header('HX-Push-Url', "/annotation/{$data->annotationType}/{$object->idDocument}/{$object->idObject}");
    }

    #[Post(path: '/annotation/image/object/search')]
    public function objectSearch(ObjectSearchData $data)
    {
        $objects = ImageService::objectSearch($data);
        return view('Annotation.Image.Panes.search', [
            'objects' => $objects,
            'idDocument' => $data->idDocument,
            'annotationType' => $data->annotationType
        ])->fragment('search');
    }

    #[Post(path: '/annotation/image/createNewObjectAtLayer')]
    public function createNewObjectAtLayer(CreateObjectData $data)
    {
        debug($data);
        try {
            $object = ImageService::createNewObjectAtLayer($data);
            return $this->redirect("/annotation/{$data->annotationType}/{$object->idDocument}/{$object->idDynamicObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/image/cloneObject')]
    public function cloneObject(CloneData $data)
    {
        try {
            $idStaticObjectClone = ImageService::cloneObject($data);

            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$idStaticObjectClone}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/image/updateObjectAnnotation')]
    public function updateObjectAnnotation(ObjectAnnotationData $data)
    {
        debug($data);
        try {
            $idStaticObject = ImageService::updateObjectAnnotation($data);
            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$data->idObject}");
//            $this->trigger('updateObjectAnnotationEvent');
            //return Criteria::byId("dynamicobject", "idDynamicObject", $idDynamicObject);
//            return $this->renderNotify("success", "Object updated.");
        } catch (\Exception $e) {
            debug($e->getMessage());
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/annotation/image/updateObjectRange')]
    public function updateObjectRange(ObjectFrameData $data)
    {
        try {
            debug($data);
            ImageService::updateObjectFrame($data);

            return $this->redirect("/annotation/{$data->annotationType}/{$data->idDocument}/{$data->idObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/annotation/image/getBBox')]
    public function getBBox(GetBBoxData $data)
    {
        try {
            return Criteria::table('view_staticobject_boundingbox')
                ->where('idStaticObject', $data->idObject)
                ->first();
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/image/createBBox')]
    public function createBBox(CreateBBoxData $data)
    {
        debug($data);
        try {
            $object = ImageService::createObjectBBox($data);
            $object->annotationType = "staticBBox";
            return $object;
            //return $this->redirect("/annotation/staticBBox/{$data->idDocument}/{$staticObject->idStaticObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/annotation/image/updateBBox')]
    public function updateBBox(UpdateBBoxData $data)
    {
        try {
            debug("updateBBox",$data);
            $idBoundingBox = ImageService::updateBBox($data);
            $boundingBox = Criteria::byId('boundingbox', 'idBoundingBox', $idBoundingBox);
            if (! $boundingBox) {
                return $this->renderNotify('error', 'Updated bounding box not found.');
            }
            return $boundingBox;
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }



}

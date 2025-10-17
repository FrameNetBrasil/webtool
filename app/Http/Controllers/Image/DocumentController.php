<?php

namespace App\Http\Controllers\Image;

use App\Data\Image\DocumentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Document;
use App\Repositories\Image;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class DocumentController extends Controller
{
    #[Get(path: '/image/{id}/document')]
    public function document(int $id)
    {
        return view("Image.document", [
            'image' => Image::byId($id)
        ]);
    }

    #[Get(path: '/image/{id}/document/formNew')]
    public function documentFormNew(int $id)
    {
        return view("Image.documentNew", [
            'idImage' => $id
        ]);
    }

    #[Get(path: '/image/{id}/document/grid')]
    public function documentGrid(int $id)
    {
        $documents = Criteria::table("view_document_image as di")
            ->join("view_document as d", "di.idDocument", "=", "d.idDocument")
            ->where("di.idImage", $id)
            ->where("d.idLanguage", AppService::getCurrentIdLanguage())
            ->all();
        return view("Image.documentGrid", [
            'idImage' => $id,
            'documents' => $documents
        ]);
    }

    #[Post(path: '/image/{id}/document/new')]
    public function documentNew(DocumentData $data)
    {

        $image = Image::byId($data->idImage);
        $document = Document::byId($data->idDocument);
        $json = json_encode([
            'idAnnotationObject1' => $document->idAnnotationObject,
            'idAnnotationObject2' => $image->idAnnotationObject,
            'relationType' => 'rel_document_image'
        ]);
        Criteria::function("objectrelation_create(?)",[$json]);
        $this->trigger('reload-gridImageDocument');
        return $this->renderNotify("success", "Image associated with Document.");
    }

    #[Delete(path: '/image/{id}/document/{idDocument}')]
    public function delete(int $id, int $idDocument)
    {
        try {
            $image = Image::byId($id);
            $document = Document::byId($idDocument);
            Criteria::table("annotationobjectrelation")
                ->where("idAnnotationObject1", $document->idAnnotationObject)
                ->where("idAnnotationObject2", $image->idAnnotationObject)
                ->delete();
            $this->trigger('reload-gridImageDocument');
            return $this->renderNotify("success", "Image removed from Document.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

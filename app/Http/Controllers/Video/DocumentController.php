<?php

namespace App\Http\Controllers\Video;

use App\Data\Dataset\CorpusData;
use App\Data\Video\DocumentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Document;
use App\Repositories\Video;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class DocumentController extends Controller
{
    #[Get(path: '/video/{id}/document')]
    public function corpus(int $id)
    {
        return view("Video.document", [
            'idVideo' => $id
        ]);
    }

    #[Get(path: '/video/{id}/document/formNew')]
    public function documentFormNew(int $id)
    {
        return view("Video.documentNew", [
            'idVideo' => $id
        ]);
    }

    #[Get(path: '/video/{id}/document/grid')]
    public function documentGrid(int $id)
    {
        $documents = Criteria::table("view_document_video as dv")
            ->join("view_document as d", "dv.idDocument", "=", "d.idDocument")
            ->where("dv.idVideo", $id)
            ->where("d.idLanguage", AppService::getCurrentIdLanguage())
            ->all();
        return view("Video.documentGrid", [
            'idVideo' => $id,
            'documents' => $documents
        ]);
    }

    #[Post(path: '/video/{id}/document/new')]
    public function documentNew(DocumentData $data)
    {

        $video = Video::byId($data->idVideo);
        $document = Document::byId($data->idDocument);
        $json = json_encode([
            'idAnnotationObject1' => $document->idAnnotationObject,
            'idAnnotationObject2' => $video->idAnnotationObject,
            'relationType' => 'rel_document_video'
        ]);
        Criteria::function("objectrelation_create(?)",[$json]);
        $this->trigger('reload-gridVideoDocument');
        return $this->renderNotify("success", "Video associated with Document.");
    }

    #[Delete(path: '/video/{id}/document/{idDocument}')]
    public function delete(int $id, int $idDocument)
    {
        try {
            $video = Video::byId($id);
            $document = Document::byId($idDocument);
            Criteria::table("annotationobjectrelation")
                ->where("idAnnotationObject1", $video->idAnnotationObject)
                ->where("idAnnotationObject2", $document->idAnnotationObject)
                ->delete();
            $this->trigger('reload-gridVideoDocument');
            return $this->renderNotify("success", "Video removed from Document.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

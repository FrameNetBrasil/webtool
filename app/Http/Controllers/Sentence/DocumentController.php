<?php

namespace App\Http\Controllers\Sentence;

use App\Data\Sentence\DocumentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Document;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class DocumentController extends Controller
{
    #[Get(path: '/sentence/{id}/document')]
    public function document(int $id)
    {
        $sentence = Criteria::byId("view_sentence","idSentence",$id);
        return view("Sentence.document", [
            'sentence' => $sentence
        ]);
    }

    #[Get(path: '/sentence/{id}/document/formNew')]
    public function documentFormNew(int $id)
    {
        return view("Sentence.documentNew", [
            'idSentence' => $id
        ]);
    }

    #[Get(path: '/sentence/{id}/document/grid')]
    public function documentGrid(int $id)
    {
        $documents = Criteria::table("view_document_sentence as ds")
            ->join("view_document as d", "ds.idDocument", "=", "d.idDocument")
            ->where("ds.idSentence", $id)
            ->where("d.idLanguage", AppService::getCurrentIdLanguage())
            ->all();
        return view("Sentence.documentGrid", [
            'idSentence' => $id,
            'documents' => $documents
        ]);
    }

    #[Post(path: '/sentence/{id}/document/new')]
    public function documentNew(DocumentData $data)
    {

        $sentence = Criteria::byId("view_sentence","idSentence",$data->idSentence);
        $document = Document::byId($data->idDocument);
        $json = json_encode([
            'idAnnotationObject1' => $document->idAnnotationObject,
            'idAnnotationObject2' => $sentence->idAnnotationObject,
            'relationType' => 'rel_document_sentence'
        ]);
        Criteria::function("objectrelation_create(?)",[$json]);
        $this->trigger('reload-gridSentenceDocument');
        return $this->renderNotify("success", "Sentence associated with Document.");
    }

    #[Delete(path: '/sentence/{id}/document/{idDocument}')]
    public function delete(int $id, int $idDocument)
    {
        try {
            $sentence = Criteria::byId("view_sentence","idSentence",$id);
            $document = Document::byId($idDocument);
            Criteria::table("annotationobjectrelation")
                ->where("idAnnotationObject1", $document->idAnnotationObject)
                ->where("idAnnotationObject2", $sentence->idAnnotationObject)
                ->delete();
            $this->trigger('reload-gridSentenceDocument');
            return $this->renderNotify("success", "Sentence removed from Document.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

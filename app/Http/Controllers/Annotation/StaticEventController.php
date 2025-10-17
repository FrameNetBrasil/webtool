<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\StaticEvent\AnnotationCommentData;
use App\Data\Annotation\StaticEvent\CreateData;
use App\Data\Annotation\StaticEvent\SearchData;
use App\Data\Annotation\StaticEvent\SentenceData;
use App\Data\Annotation\StaticEvent\ObjectFrameData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Corpus;
use App\Repositories\Document;
use App\Repositories\Frame;
use App\Repositories\LU;
use App\Services\AnnotationStaticEventService;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class StaticEventController extends Controller
{
    #[Get(path: '/annotation/staticEvent')]
    public function browse()
    {
        $search = session('searchStaticEvent') ?? SearchData::from();
        return view("Annotation.StaticEvent.browse", [
            'search' => $search
        ]);
    }

    #[Post(path: '/annotation/staticEvent/grid')]
    public function grid(SearchData $search)
    {
        return view("Annotation.StaticEvent.grids", [
            'search' => $search,
            'sentences' => []
        ]);
    }

    #[Get(path: '/annotation/staticEvent/grid/{idDocument}/sentences')]
    public function documentSentences(int $idDocument)
    {
        $document = Document::byId($idDocument);
        $sentences = AnnotationStaticEventService::listSentences($idDocument);
        return view("Annotation.StaticEvent.sentences", [
            'document' => $document,
            'sentences' => $sentences
        ]);
    }

    private function getData(int $idDocumentSentence): SentenceData
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $ds = Criteria::byFilter("view_document_sentence", ["idDocumentSentence", "=", $idDocumentSentence])->first();
        $document = Criteria::byFilter("view_document", [
            ["idDocument", "=", $ds->idDocument],
            ["idLanguage","=", $idLanguage],
        ])->first();
        $corpus = Corpus::byId($document->idCorpus);
        $sentence = Criteria::byFilter("view_sentence", ["idSentence", "=", $ds->idSentence])->first();
        $is = Criteria::byFilter("view_image_sentence", [
            ["idSentence", "=", $sentence->idSentence]
        ])->first();
        $image = Criteria::byFilter("image", ["idImage", "=", $is->idImage])->first();
        $annotation = AnnotationStaticEventService::getObjectsForAnnotationImage($document->idDocument, $sentence->idSentence);
        return SentenceData::from([
            'idDocumentSentence' => $idDocumentSentence,
            'idPrevious' => AnnotationStaticEventService::getPrevious($document->idDocument,$idDocumentSentence),
            'idNext' => AnnotationStaticEventService::getNext($document->idDocument,$idDocumentSentence),
            'document' => $document,
            'sentence' => $sentence,
            'corpus' => $corpus,
            'image' => $image,
            'objects' => $annotation['objects'],
            'frames' => $annotation['frames'],
            'type' => $annotation['type']
        ]);
    }

    #[Get(path: '/annotation/staticEvent/sentence/{idDocumentSentence}')]
    public function annotationSentence(int $idDocumentSentence)
    {
        $data = $this->getData($idDocumentSentence);
        debug($data);
        return view("Annotation.StaticEvent.annotationSentence", $data->toArray());
    }

    #[Post(path: '/annotation/staticEvent/addFrame')]
    public function annotationSentenceFes(CreateData $input)
    {
        $idFrame = '';
        if (is_numeric($input->idLU)) {
            $lu = LU::byId($input->idLU);
            $idFrame = $lu->idFrame;
        } else if (is_numeric($input->idFrame)) {
            $idFrame = $input->idFrame;
        }
        if ($idFrame != '') {
            $data = $this->getData($input->idDocumentSentence);
            if (!isset($data->frames[$idFrame])) {
                $frame = Frame::byId($idFrame);
                $data->frames[$idFrame] = [
                    'name' => $frame->name,
                    'idFrame' => $idFrame,
                    'objects' => []
                ];
            }
            return view("Annotation.StaticEvent.fes", $data);
        } else {
            return $this->renderNotify("error", "Frame not found!");
        }
    }

    #[Put(path: '/annotation/staticEvent/fes/{idDocumentSentence}/{idFrame}')]
    public function annotationSentenceFesSubmit(int $idDocumentSentence, int $idFrame, ObjectFrameData $data)
    {
        debug($data);
        try {
            foreach ($data->objects as $objects) {
                foreach ($objects as $idFrameElement) {
                    if ($idFrameElement == '') {
                        throw new \Exception("FrameElement must be informed.");
                    }
                }
            }
            AnnotationStaticEventService::updateAnnotation($idDocumentSentence, $idFrame, $data->objects);
            return $this->renderNotify("success", "Annotations updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/annotation/staticEvent/fes/{idDocumentSentence}/{idFrame}')]
    public function annotationSentenceFesDelete(int $idDocumentSentence, int $idFrame)
    {
        try {
            AnnotationStaticEventService::deleteAnnotationByFrame($idDocumentSentence, $idFrame);
            return $this->clientRedirect("/annotation/staticEvent/sentence/{$idDocumentSentence}");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/annotation/staticEvent/comment')]
    public function annotationComment(AnnotationCommentData $data)
    {
        debug($data);
        try {
            $comment = Criteria::byFilter("annotationcomment", ["id1", "=", $data->idDocumentSentence])->first();
            if ($comment->idAnnotationComment) {
                Criteria::table("annotationcomment")
                    ->where("idAnnotationComment", "=", $comment->idAnnotationComment)
                    ->update([
                        "type" => "StaticEvent",
                        "id1" => $data->idDocumentSentence,
                        "comment" => $data->comment
                    ]);
            } else {
                Criteria::table("annotationcomment")
                    ->insert([
                        "type" => "StaticEvent",
                        "id1" => $data->idDocumentSentence,
                        "comment" => $data->comment
                    ]);
            }
            return $this->renderNotify("success", "Comment added.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

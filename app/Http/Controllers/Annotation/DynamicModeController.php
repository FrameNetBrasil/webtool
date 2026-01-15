<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\VideoService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;


#[Middleware(name: 'auth')]
class DynamicModeController extends Controller
{
    #[Get(path: '/annotation/dynamicMode')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], "DynamicAnnotation");

        return view('Annotation.browseDocuments', [
            'page' => "Dynamic Annotation",
            'url' => "/annotation/dynamicMode",
            'data' => $data,
            'taskGroupName' => 'DynamicAnnotation'
        ]);
    }

    private function getData(int $idDocument, ?int $idDynamicObject = null, ?int $frameNumber = null): array
    {
        return VideoService::getResourceData($idDocument, $idDynamicObject, 'dynamicMode', $frameNumber);
    }

    #[Get(path: '/annotation/dynamicMode/sentences/{idDocument}')]
    public function gridSentences(int $idDocument)
    {
        $sentences = BrowseService::listSentences($idDocument);
        return view("Annotation.Video.Panes.sentences", [
            'sentences' => $sentences
        ]);
    }


    #[Get(path: '/annotation/dynamicMode/{idDocument}/videoFrame/{frameNumber}')]
    public function annotationFrame(int|string $idDocument, int $frameNumber = null)
    {
        $data = $this->getData($idDocument, null);
        $data['frameNumber'] = $frameNumber;
        return response()
            ->view('Annotation.Video.annotation', $data)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Get(path: '/annotation/dynamicMode/{idDocument}/{idDynamicObject?}/{frameNumber?}')]
    public function annotation(int|string $idDocument, ?int $idDynamicObject = null, ?int $frameNumber = null)
    {
        $data = $this->getData($idDocument, $idDynamicObject, $frameNumber);

        return response()
            ->view('Annotation.Video.annotation', $data)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Delete(path: '/annotation/dynamicMode/{idDocument}/{idDynamicObject}')]
    public function deleteObject(int $idDocument, int $idDynamicObject)
    {
        try {
            VideoService::deleteObject($idDynamicObject);

            return $this->redirect("/annotation/dynamicMode/{$idDocument}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
    #[Delete(path: '/annotation/dynamicMode/deleteAllBBoxes/{idDocument}/{idDynamicObject}')]
    public function deleteAllBBoxes(int $idDocument, int $idDynamicObject)
    {
        try {
            VideoService::deleteBBoxesFromObject($idDynamicObject);

            return $this->redirect("/annotation/dynamicMode/{$idDocument}/{$idDynamicObject}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }




//    #[Get(path: '/annotation/dynamicMode')]
//    public function browse()
//    {
//        $search = session('searchCorpus') ?? SearchData::from();
//        return view("Annotation.DynamicMode.browse", [
//            'search' => $search
//        ]);
//    }
//
//    #[Post(path: '/annotation/dynamicMode/grid')]
//    public function grid(SearchData $search)
//    {
//        return view("Annotation.DynamicMode.grid", [
//            'search' => $search
//        ]);
//    }
//
//    private function getData(int $idDocument): DocumentData
//    {
//        $document = Document::byId($idDocument);
//        $corpus = Corpus::byId($document->idCorpus);
//        $documentVideo = Criteria::table("view_document_video")
//            ->where("idDocument", $idDocument)
//            ->first();
//        $video = Video::byId($documentVideo->idVideo);
//        return DocumentData::from([
//            'idDocument' => $idDocument,
//            'idDocumentVideo' => $documentVideo->idDocumentVideo,
//            'document' => $document,
//            'corpus' => $corpus,
//            'video' => $video,
//            'fragment' => 'fe',
//        ]);
//    }
//
//    #[Get(path: '/annotation/dynamicMode/object/{idDynamicObject}')]
//    public function getObject(int $idDynamicObject)
//    {
//        return AnnotationDynamicService::getObject($idDynamicObject ?? 0);
//    }
//
//
//    #[Post(path: '/annotation/dynamicMode/formObject')]
//    public function formObject(ObjectData $data)
//    {
//        debug($data);
//        $object = AnnotationDynamicService::getObject($data->idDynamicObject ?? 0);
//        return view("Annotation.DynamicMode.Panes.formPane", [
//            'order' => $data->order,
//            'object' => $object
//        ]);
//    }
//
//
//    #[Get(path: '/annotation/dynamicMode/gridObjects/{idDocument}')]
//    public function objectsForGrid(int $idDocument)
//    {
//        ini_set("memory_limit", "8192M");
//        return AnnotationDynamicService::getObjectsByDocument($idDocument);
//    }
//
//    #[Get(path: '/annotation/dynamicMode/formObject/{idDynamicObject}/{order}')]
//    public function getFormObject(int $idDynamicObject, int $order)
//    {
//        $object = AnnotationDynamicService::getObject($idDynamicObject ?? 0);
//        return view("Annotation.DynamicMode.Panes.formPane", [
//            'order' => $order,
//            'object' => $object
//        ]);
//    }
//    #[Post(path: '/annotation/dynamicMode/updateObject')]
//    public function updateObject(ObjectData $data)
//    {
//        debug($data);
//        try {
//            $idDynamicObject = AnnotationDynamicService::updateObject($data);
//            return Criteria::byId("dynamicobject", "idDynamicObject", $idDynamicObject);
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Post(path: '/annotation/dynamicMode/updateObjectAnnotation')]
//    public function updateObjectAnnotation(ObjectAnnotationData $data)
//    {
//        debug($data);
//        try {
//            $idDynamicObject = AnnotationDynamicService::updateObjectAnnotation($data);
//            $this->trigger('updateObjectAnnotationEvent');
//            //return Criteria::byId("dynamicobject", "idDynamicObject", $idDynamicObject);
//            return $this->renderNotify("success", "Object updated.");
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Post(path: '/annotation/dynamicMode/cloneObject')]
//    public function cloneObject(CloneData $data)
//    {
//        debug($data);
//        try {
//            $idDynamicObject = AnnotationDynamicService::cloneObject($data);
//            return Criteria::byId("dynamicobject", "idDynamicObject", $idDynamicObject);
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Delete(path: '/annotation/dynamicMode/{idDynamicObject}')]
//    public function deleteObject(int $idDynamicObject)
//    {
//        try {
//            AnnotationDynamicService::deleteObject($idDynamicObject);
//            return $this->renderNotify("success", "Object removed.");
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Post(path: '/annotation/dynamicMode/updateBBox')]
//    public function updateBBox(UpdateBBoxData $data)
//    {
//        try {
//            debug($data);
//            $idBoundingBox = AnnotationDynamicService::updateBBox($data);
//            return Criteria::byId("boundingbox", "idBoundingBox", $idBoundingBox);
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Post(path: '/annotation/dynamicMode/createBBox')]
//    public function createBBox(CreateBBoxData $data)
//    {
//        try {
//            debug($data);
//            return AnnotationDynamicService::createBBox($data);
//        } catch (\Exception $e) {
//            debug($e->getMessage());
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Get(path: '/annotation/dynamicMode/fes/{idFrame}')]
//    public function feCombobox(int $idFrame)
//    {
//        return view("Annotation.DynamicMode.Panes.fes", [
//            'idFrame' => $idFrame
//        ]);
//    }
//
//    #[Get(path: '/annotation/dynamicMode/sentences/{idDocument}')]
//    public function gridSentences(int $idDocument)
//    {
//        $sentences = AnnotationDynamicService::listSentencesByDocument($idDocument);
//        return view("Annotation.DynamicMode.Panes.sentences", [
//            'sentences' => $sentences
//        ]);
//    }
//
//    /*
//     * Build sentence
//     */
//
//    #[Get(path: '/annotation/dynamicMode/buildSentences/{idDocument}')]
//    public function buildSentences(int $idDocument)
//    {
//        $data = $this->getData($idDocument);
//        return view("Annotation.DynamicMode.buildSentences", $data->toArray());
//    }
//
//    #[Get(path: '/annotation/dynamicMode/formSentence/{idDocument}/{idSentence}')]
//    public function formSentence(int $idDocument, int $idSentence)
//    {
//        $sentence = Criteria::byId("view_sentence", "idSentence", $idSentence);
//        if (is_null($sentence)) {
//            $sentence = (object)[
//                "idSentence" => 0,
//                "text" => ''
//            ];
//        } else {
//            $ts = Criteria::byId("view_sentence_timespan", "idSentence", $idSentence);
//            $sentence->startTime = $ts->startTime;
//            $sentence->endTime = $ts->endTime;
//        }
//        $documentVideo = Criteria::table("view_document_video")
//            ->where("idDocument", $idDocument)
//            ->first();
//        $video = Video::byId($documentVideo->idVideo);
//        return view("Annotation.DynamicMode.Panes.formSentencePane", [
//            'idDocument' => $idDocument,
//            "sentence" => $sentence,
//            'idLanguage' => $video->idLanguage
//        ]);
//    }
//
//    #[Post(path: '/annotation/dynamicMode/formSentence')]
//    public function sentence(SentenceData $data)
//    {
//        try {
//            if ($data->text == "") {
//                return $this->renderNotify("error", "No data.");
//            }
//            debug($data);
//            if ($data->idSentence == 0) {
//                AnnotationDynamicService::createSentence($data);
//            } else {
//                AnnotationDynamicService::updateSentence($data);
//            }
//            $this->trigger('reload-gridSentence');
//            return $this->renderNotify("success", "Sentence updated.");
//        } catch (\Exception $e) {
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Get(path: '/annotation/dynamicMode/words/{idVideo}')]
//    public function words(int $idVideo)
//    {
//        $words = Criteria::table("view_video_wordmm")
//            ->where("idVideo", "=", $idVideo)
//            ->whereNull("idDocumentSentence")
//            ->all();
//        return $words;
//    }
//
//    #[Post(path: '/annotation/dynamicMode/joinWords')]
//    public function joinWords(WordData $data)
//    {
//        try {
//            debug($data);
//            $idSentence = AnnotationDynamicService::buildSentenceFromWords($data);
//            if ($idSentence == 0) {
//                throw new \Exception("Error joining words.");
//            }
//            return $idSentence;
//        } catch (\Exception $e) {
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    #[Get(path: '/annotation/dynamicMode/buildSentences/sentences/{idDocument}')]
//    public function buildSentenceSentences(int $idDocument)
//    {
//
//        $sentences = AnnotationDynamicService::listSentencesByDocument($idDocument);
//        return view("Annotation.DynamicMode.Panes.buildSentences", [
//            'idDocument' => $idDocument,
//            'sentences' => $sentences
//        ]);
//    }
//
//    #[Post(path: '/annotation/dynamicMode/splitSentence')]
//    public function splitSentence(SentenceData $data)
//    {
//        try {
//            AnnotationDynamicService::splitSentence($data);
//            $this->trigger('reload-gridSentence');
//            return $this->renderNotify("success", "Sentence updated.");
//        } catch (\Exception $e) {
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    /*
//     * Comment
//     */
//
//    #[Get(path: '/annotation/dynamicMode/formComment')]
//    public function getFormComment(CommentData $data)
//    {
//        $object = CommentService::getDynamicObjectComment($data->idDynamicObject);
//        return view("Annotation.DynamicMode.Panes.formComment", [
//            'idDocument' => $data->idDocument,
//            'order' => $data->order,
//            'object' => $object
//        ]);
//    }
//    #[Post(path: '/annotation/dynamicMode/updateObjectComment')]
//    public function updateObjectComment(CommentData $data)
//    {
//        try {
//            debug($data);
//            CommentService::updateDynamicObjectComment($data);
//            $this->trigger('updateObjectAnnotationEvent');
//            return $this->renderNotify("success", "Comment registered.");
//        } catch (\Exception $e) {
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//    #[Delete(path: '/annotation/dynamicMode/comment/{idDocument}/{idDynamicObject}')]
//    public function deleteObjectComment(int $idDocument,int $idDynamicObject)
//    {
//        try {
//            CommentService::deleteDynamicObjectComment($idDocument,$idDynamicObject);
//            return $this->renderNotify("success", "Object comment removed.");
//        } catch (\Exception $e) {
//            return $this->renderNotify("error", $e->getMessage());
//        }
//    }
//
//    /*
//     * get Object
//     */
//
//    #[Get(path: '/annotation/dynamicMode/{idDocument}/{idDynamicObject?}')]
//    public function annotation(int|string $idDocument, int $idDynamicObject = null)
//    {
//        $data = $this->getData($idDocument);
//        if (!is_null($idDynamicObject)) {
//            $data->idDynamicObject = $idDynamicObject;
//        }
//        return view("Annotation.DynamicMode.annotation", $data->toArray());
//    }



}

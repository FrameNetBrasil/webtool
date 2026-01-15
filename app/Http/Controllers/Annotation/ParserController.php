<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Data\Annotation\Corpus\AnnotationData;
use App\Data\Annotation\Corpus\CreateASData;
use App\Data\Annotation\Corpus\DeleteObjectData;
use App\Data\Annotation\Corpus\LOMEAcceptedData;
use App\Data\Annotation\Corpus\SelectionData;
use App\Data\Annotation\Session\SessionData;
use App\Database\Criteria;
use App\Enum\AnnotationSetStatus;
use App\Enum\Status;
use App\Http\Controllers\Controller;
use App\Repositories\AnnotationSet;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class ParserController extends Controller
{

    #[Get(path: '/annotation/parser/script/{folder}')]
    public function jsObjects(string $folder)
    {
        return response()
            ->view("Annotation.Parser.Scripts.{$folder}")
            ->header('Content-type', 'text/javascript');
    }

    #[Get(path: '/annotation/parser')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], 'CorpusAnnotation');
        session(["corpusAnnotationType" => "parser"]);
        return view('Annotation.browseSentences', [
            'page' => 'Parser Annotation',
            'url' => '/annotation/parser',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/parser/sentence/{idDocumentSentence}/{idAnnotationSet?}')]
    public function annotation(int $idDocumentSentence, ?int $idAnnotationSet = null)
    {
        $data = CorpusService::getResourceData($idDocumentSentence, $idAnnotationSet, 'fe');
        $page = 'Parser Annotation';
        $url = '/annotation/fe';

        return view('Annotation.Parser.annotation', array_merge($data, compact('page', 'url')));
    }
    #[Get(path: '/annotation/parser/as/{corpusAnnotationType}/{idAS}/{token?}')]
    public function annotationSet(string $corpusAnnotationType, int $idAS, string $token = '')
    {
        $data = CorpusService::getAnnotationSetData($idAS, $token,$corpusAnnotationType);
        return view('Annotation.Parser.Panes.annotationSet', $data);
    }


    #[Get(path: '/annotation/parser/lus/{corpusAnnotationType}/{idDocumentSentence}/{idWord}')]
    public function getLUs(string $corpusAnnotationType, int $idDocumentSentence, int $idWord)
    {
        $data = CorpusService::getLUs($idDocumentSentence, $idWord);
        return view("Annotation.Parser.Panes.lus", array_merge($data,compact("idWord","idDocumentSentence","corpusAnnotationType")));
    }
    #[Delete(path: '/annotation/parser/annotationset/{idAnnotationSet}/{corpusAnnotationType}')]
    public function deleteAS(int $idAnnotationSet, string $corpusAnnotationType)
    {
        try {
            $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $idAnnotationSet);
            AnnotationSet::delete($idAnnotationSet);
            return $this->clientRedirect("/annotation/{$corpusAnnotationType}/sentence/{$annotationSet->idDocumentSentence}");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/annotation/parser/createAS')]
    public function createAS(CreateASData $input)
    {
        $idAnnotationSet = CorpusService::createAnnotationSet($input);
        if (is_null($idAnnotationSet)) {
            return $this->renderNotify('error', 'Error creating AnnotationSet.');
        } else {
            return $this->clientRedirect("/annotation/{$input->corpusAnnotationType}/sentence/{$input->idDocumentSentence}/{$idAnnotationSet}");
        }
    }
    #[Post(path: '/annotation/parser/object')]
    public function annotate(AnnotationData $object)
    {
        try {
            $object->range = SelectionData::from($object->selection);
            if ($object->range->end < $object->range->start) {
                throw new \Exception("Wrong selection.");
            }
            if ($object->range->type != '') {
                $data = CorpusService::annotateObject($object);
                if ($object->corpusAnnotationType == 'fe') {
                    return view("Annotation.Parser.Panes.FE.asAnnotation", $data);
                } else {
                    return view("Annotation.Parser.Panes.FullText.asAnnotation", $data);
                }
            } else {
                return $this->renderNotify("error", "No selection.");
            }
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
    #[Delete(path: '/annotation/parser/object')]
    public function deleteObject(DeleteObjectData $object)
    {
        try {
            CorpusService::deleteObject($object);
            $data = CorpusService::getAnnotationSetData($object->idAnnotationSet, $object->token);
            if ($object->corpusAnnotationType == 'fe') {
                return view("Annotation.Parser.Panes.FE.asAnnotation", $data);
            } else {
                return view("Annotation.Parser.Panes.FullText.asAnnotation", $data);
            }
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/annotation/parser/annotationset/{idAnnotationSet}/change/{idLU}/{corpusAnnotationType}')]
    public function changeAnnotationSet(int $idAnnotationSet, int $idLU, string $corpusAnnotationType = '')
    {
        $target = Criteria::table("view_annotation_text_target")
            ->where("idAnnotationSet", $idAnnotationSet)
            ->select("startChar","endChar")
            ->first();
        $wordList = json_encode([$target]);
        $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $idAnnotationSet);
        AnnotationSet::updateAST($annotationSet->idAnnotationSet, AnnotationSetStatus::ALTERNATIVE->value);
        AnnotationSet::delete($idAnnotationSet);
        $input = CreateASData::from([
            'idDocumentSentence' => $annotationSet->idDocumentSentence,
            'idLU' => $idLU,
            'corpusAnnotationType' => $corpusAnnotationType,
            'wordList' => $wordList
        ]);
        $idAnnotationSet = CorpusService::createAnnotationSet($input);
        return $this->clientRedirect("/annotation/{$input->corpusAnnotationType}/sentence/{$input->idDocumentSentence}/{$idAnnotationSet}");
    }

    #[Post(path: '/annotation/parser/lome/accepted')]
    public function lomeAccepted(LOMEAcceptedData $data) {
        AnnotationSet::updateStatusField($data->idAnnotationSet, Status::ACCEPTED->value);
        $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $data->idAnnotationSet);
        return view("Annotation.Parser.Panes.asStatusField", [
            "annotationSet" => $annotationSet
        ]);
    }

}

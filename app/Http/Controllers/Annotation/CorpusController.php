<?php

namespace App\Http\Controllers\Annotation;

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
use App\Services\Annotation\CorpusService;
use App\Services\Annotation\FlexService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class CorpusController extends Controller
{

    #[Get(path: '/annotation/corpus/script/{folder}')]
    public function jsObjects(string $folder)
    {
        return response()
            ->view("Annotation.Corpus.Scripts.{$folder}")
            ->header('Content-type', 'text/javascript');
    }

    #[Get(path: '/annotation/corpus/as/{corpusAnnotationType}/{idAS}/{token?}')]
    public function annotationSet(string $corpusAnnotationType, int $idAS, string $token = '')
    {
        $data = CorpusService::getAnnotationSetData($idAS, $token,$corpusAnnotationType);
        return view('Annotation.Corpus.Panes.annotationSet', $data);
    }


    #[Get(path: '/annotation/corpus/lus/{corpusAnnotationType}/{idDocumentSentence}/{idWord}')]
    public function getLUs(string $corpusAnnotationType, int $idDocumentSentence, int $idWord)
    {
        $data = CorpusService::getLUs($idDocumentSentence, $idWord);
        return view("Annotation.Corpus.Panes.lus", array_merge($data,compact("idWord","idDocumentSentence","corpusAnnotationType")));
    }
    #[Delete(path: '/annotation/corpus/annotationset/{idAnnotationSet}/{corpusAnnotationType}')]
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

    #[Post(path: '/annotation/corpus/createAS')]
    public function createAS(CreateASData $input)
    {
        $idAnnotationSet = CorpusService::createAnnotationSet($input);
        if (is_null($idAnnotationSet)) {
            return $this->renderNotify('error', 'Error creating AnnotationSet.');
        } else {
            return $this->clientRedirect("/annotation/{$input->corpusAnnotationType}/sentence/{$input->idDocumentSentence}/{$idAnnotationSet}");
        }
    }
    #[Post(path: '/annotation/corpus/object')]
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
                    return view("Annotation.Corpus.Panes.FE.asAnnotation", $data);
                } else if ($object->corpusAnnotationType == 'fullText') {
                    return view("Annotation.Corpus.Panes.FullText.asAnnotation", $data);
                } else if ($object->corpusAnnotationType == 'flex') {
                    return view("Annotation.Corpus.Panes.Flex.asAnnotation", $data);
                }
            } else {
                return $this->renderNotify("error", "No selection.");
            }
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
    #[Delete(path: '/annotation/corpus/object')]
    public function deleteObject(DeleteObjectData $object)
    {
        try {
            debug($object);
            CorpusService::deleteObject($object);
            if ($object->corpusAnnotationType == 'fe') {
                $data = CorpusService::getAnnotationSetData($object->idAnnotationSet, $object->token);
                return view("Annotation.Corpus.Panes.FE.asAnnotation", $data);
            } else if ($object->corpusAnnotationType == 'fullText') {
                $data = CorpusService::getAnnotationSetData($object->idAnnotationSet, $object->token);
                return view("Annotation.Corpus.Panes.FullText.asAnnotation", $data);
            } else if ($object->corpusAnnotationType == 'flex') {
                $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $object->idAnnotationSet);
                $data = FlexService::getAnnotationData($annotationSet->idDocumentSentence);
                return view("Annotation.Corpus.Panes.Flex.asAnnotation", $data);
            }
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/annotation/corpus/annotationset/{idAnnotationSet}/change/{idLU}/{corpusAnnotationType}')]
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

    #[Post(path: '/annotation/corpus/lome/accepted')]
    public function lomeAccepted(LOMEAcceptedData $data) {
        AnnotationSet::updateStatusField($data->idAnnotationSet, Status::ACCEPTED->value);
        $annotationSet = Criteria::byId("view_annotationset", "idAnnotationSet", $data->idAnnotationSet);
        return view("Annotation.Corpus.Panes.asStatusField", [
            "annotationSet" => $annotationSet
        ]);
    }

}

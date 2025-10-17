<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Repositories\AnnotationSet;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('auth')]
class FEController extends Controller
{
    #[Get(path: '/annotation/fe')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], 'CorpusAnnotation');
        session(["corpusAnnotationType" => "fe"]);
        return view('Annotation.browseSentences', [
            'page' => 'FE Annotation',
            'url' => '/annotation/fe',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/fe/sentence/{idDocumentSentence}/{idAnnotationSet?}')]
    public function annotation(int $idDocumentSentence, ?int $idAnnotationSet = null)
    {
        $data = CorpusService::getResourceData($idDocumentSentence, $idAnnotationSet, 'fe');
        $page = 'FE Annotation';
        $url = '/annotation/fe';

        return view('Annotation.Corpus.annotation', array_merge($data, compact('page', 'url')));
    }

    #[Get(path: '/annotation/fe/asExternal/{idAS}')]
    public function annotationSet(int $idAS)
    {
        $data = CorpusService::getAnnotationSetData($idAS, '', 'fe');

        return view('Annotation.Corpus.Panes.annotationSetExternal', $data);
    }

    #[Delete(path: '/annotation/fe/asExternal/{idAnnotationSet}')]
    public function deleteAS(int $idAnnotationSet)
    {
        try {
            AnnotationSet::delete($idAnnotationSet);

            return $this->renderTrigger('annotationset_deleted');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}

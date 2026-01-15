<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use App\Services\Annotation\FlexService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('auth')]
class FlexController extends Controller
{
    #[Get(path: '/annotation/flex')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], 'CorpusAnnotation');
        session(["corpusAnnotationType" => "flex"]);
        return view('Annotation.browseSentences', [
            'page' => 'Flex-syntax Annotation',
            'url' => '/annotation/flex',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/flex/sentence/{idDocumentSentence}')]
    public function annotation(int $idDocumentSentence)
    {
        $data = CorpusService::getResourceData($idDocumentSentence, null, 'flex');
        $page = 'Flex-syntax Annotation';
        $url = '/annotation/flex';

        return view('Annotation.Corpus.annotation', array_merge($data, compact('page', 'url')));
    }

    #[Get(path: '/annotation/flex/annotation/{idDocumentSentence}')]
    public function annotationSet(int $idDocumentSentence)
    {
        $data = FlexService::getAnnotationData($idDocumentSentence);
        return view('Annotation.Corpus.Panes.Flex.annotationSet', $data);
    }

}

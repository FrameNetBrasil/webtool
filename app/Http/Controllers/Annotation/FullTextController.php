<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('auth')]
class FullTextController extends Controller
{
    #[Get(path: '/annotation/fullText')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], 'CorpusAnnotation');
        session(["corpusAnnotationType" => "fullText"]);
        return view('Annotation.browseSentences', [
            'page' => 'FullText Annotation',
            'url' => '/annotation/fullText',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/fullText/sentence/{idDocumentSentence}/{idAnnotationSet?}')]
    public function annotation(int $idDocumentSentence, ?int $idAnnotationSet = null)
    {
        $data = CorpusService::getResourceData($idDocumentSentence, $idAnnotationSet, 'fullText');
        $page = 'FullText Annotation';
        $url = '/annotation/fullText';

        return view('Annotation.Corpus.annotation', array_merge($data, compact('page', 'url')));
    }
}

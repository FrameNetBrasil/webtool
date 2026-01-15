<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Repositories\AnnotationSet;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\CorpusService;
use App\Services\AnnotationASService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('auth')]
class AnnotationSetController extends Controller
{
    #[Get(path: '/annotation/as')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], 'CorpusAnnotation');

        return view('Annotation.browseSentences', [
            'page' => 'AnnotationSets',
            'url' => '/annotation/as',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/as/sentence/{idDocumentSentence}/{idAnnotationSet?}')]
    public function annotation(int $idDocumentSentence, ?int $idAnnotationSet = null)
    {
        $data = CorpusService::getResourceData($idDocumentSentence, $idAnnotationSet, 'as');
        $data['annotationSets'] = AnnotationSet::getTargets($idDocumentSentence);

        return view('Annotation.AS.annotation', $data);
    }

}

<?php

namespace App\Http\Controllers\Annotation;

use App\Data\LU\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\AnnotationSet;
use App\Services\Annotation\CorpusService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('auth')]
class LUController extends Controller
{
    #[Get(path: '/annotation/lu')]
    public function browse(SearchData $search)
    {
        $data = [];

        return view('Annotation.browseLU', [
            'page' => 'AnnotationSets by LU',
            'url' => '/annotation/lu',
            'data' => $data,
        ]);
    }

    #[Get(path: '/annotation/lu/annotationset/{idAnnotationSet}')]
    public function annotationset(int $idAnnotationSet = null)
    {
        $as = Criteria::byId("annotationset","idAnnotationSet",$idAnnotationSet);
        $data['idLU'] = $as->idLU;
        $data['annotationSets'] = AnnotationSet::getTargetsByIdAnnotationSet($idAnnotationSet);
        return view('Annotation.LU.annotation', $data);
    }

    #[Get(path: '/annotation/lu/{idLU}/{idAnnotationSet?}')]
    public function annotation(int $idLU, ?int $idAnnotationSet = null)
    {
        $data['idLU'] = $idLU;
        $data['annotationSets'] = AnnotationSet::getTargetsByIdLU($idLU);
        return view('Annotation.LU.annotation', $data);
    }


}

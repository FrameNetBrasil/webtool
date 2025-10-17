<?php

namespace App\Http\Controllers\Frame;

use App\Data\CreateFrameData;
use App\Data\CreateRelationFEInternalData;
use App\Data\Relation\FEInternalData;
use App\Data\SearchFrameData;
use App\Data\UpdateFrameClassificationData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\FEController;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Services\AppService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class FEInternalRelationController extends Controller
{
    #[Get(path: '/frame/{id}/feRelations')]
    public function feRelations(string $id)
    {
        return view("Relation.feInternalChild",[
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/frame/{id}/feRelations/formNew/{error?}')]
    public function formNewFERelations(string $id, ?string $error = null)
    {
        $view = view("Relation.feInternalFormNew",[
            'idFrame' => $id
        ]);
        return is_null($error) ? $view : $view->fragment("form");
    }

    #[Get(path: '/frame/{id}/feRelations/grid')]
    public function gridFERelations(string $id)
    {
        return view("Relation.feInternalGrid",[
            'idFrame' => $id,
            'relations' => RelationService::listRelationsFEInternal($id)
        ]);
    }



}

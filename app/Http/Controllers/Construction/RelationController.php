<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Repositories\Construction;
use App\Services\ReportConstructionService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class RelationController extends Controller
{
    #[Get(path: '/cxn/{id}/relations')]
    public function relations(string $id)
    {
        return view("Relation.cxnChild", [
            'idConstruction' => $id,
            'cxn' => Construction::byId($id)
        ]);
    }

    #[Get(path: '/cxn/{id}/relations/formNew')]
    public function formNewRelation(string $id)
    {
        return view("Relation.cxnFormNew", [
            'idConstruction' => $id
        ]);
    }

    #[Get(path: '/cxn/{id}/relations/grid')]
    public function gridRelation(string $id)
    {
        $cxn = Construction::byId($id);
        $relations = ReportConstructionService::getRelations($cxn);
        return view("Relation.cxnGrid", [
            'idConstruction' => $id,
            'relations' => $relations
        ]);
    }

}

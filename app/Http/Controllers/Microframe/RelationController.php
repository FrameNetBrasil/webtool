<?php

namespace App\Http\Controllers\Microframe;

use App\Http\Controllers\Controller;
use App\Repositories\Microframe;
use App\Services\Microframe\ReportService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class RelationController extends Controller
{
    #[Get(path: '/microframe/{id}/relations')]
    public function relations(string $id)
    {
        return view("Relation.mfChild", [
            'idFrame' => $id,
            'frame' => Microframe::byId($id)
        ]);
    }

    #[Get(path: '/microframe/{id}/relations/formNew')]
    public function formNewRelation(string $id)
    {
        return view("Relation.mfFormNew", [
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/microframe/{id}/relations/grid')]
    public function gridRelation(string $id)
    {
        $frame = Microframe::byId($id);
        $relations = ReportService::getRelations($frame);
        return view("Relation.mfGrid", [
            'idFrame' => $id,
            'relations' => $relations
        ]);
    }

}

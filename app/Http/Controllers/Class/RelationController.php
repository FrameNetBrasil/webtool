<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Repositories\Class_;
use App\Services\Class\ReportService;
use App\Services\ReportFrameService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class RelationController extends Controller
{
    #[Get(path: '/class/{id}/relations')]
    public function relations(string $id)
    {
        return view("Relation.classChild", [
            'idFrame' => $id,
            'frame' => Class_::byId($id)
        ]);
    }

    #[Get(path: '/class/{id}/relations/formNew')]
    public function formNewRelation(string $id)
    {
        return view("Relation.classFormNew", [
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/class/{id}/relations/grid')]
    public function gridRelation(string $id)
    {
        $frame = Class_::byId($id);
        $relations = ReportService::getRelations($frame);
        return view("Relation.classGrid", [
            'idFrame' => $id,
            'relations' => $relations
        ]);
    }

}

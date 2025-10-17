<?php

namespace App\Http\Controllers\CE;

use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class RelationController extends Controller
{
    #[Get(path: '/ce/relations/{idEntityRelation}/cxn/{idCxnBase}')]
    public function relations(string $idEntityRelation, string $idCxnBase)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        $cxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedCxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.ceChild",[
            'idEntityRelation' => $idEntityRelation,
            'idCxnBase' => $idCxnBase,
            'cxn' => $cxn,
            'relatedCxn' => $relatedCxn,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'relationType' => $relation->relationType
            ],
        ]);
    }

    #[Get(path: '/ce/relations/{idEntityRelation}/formNew')]
    public function relationsCEFormNew(int $idEntityRelation)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        $cxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedCxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.ceFormNew",[
            'idEntityRelation' => $idEntityRelation,
            'cxn' => $cxn,
            'relatedCxn' => $relatedCxn,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'entry' => $relation->relationType
            ]
        ]);
    }

    #[Get(path: '/ce/relations/{idEntityRelation}/grid')]
    public function gridRelationsCE(int $idEntityRelation)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        $cxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedCxn = Criteria::table("view_construction")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.ceGrid",[
            'idEntityRelation' => $idEntityRelation,
            'cxn' => $cxn,
            'relatedCxn' => $relatedCxn,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'relationType' => $relation->relationType
            ],
            'relations' => RelationService::listRelationsCE($idEntityRelation)
        ]);
    }
}

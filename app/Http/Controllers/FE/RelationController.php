<?php

namespace App\Http\Controllers\FE;

use App\Data\CreateFEData;
use App\Data\FE\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\EntityRelation;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\Relation;
use App\Repositories\ViewConstraint;
use App\Repositories\ViewFrameElement;
use App\Services\AppService;
use App\Services\EntryService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class RelationController extends Controller
{
    #[Get(path: '/fe/relations/{idEntityRelation}/frame/{idFrameBase}')]
    public function relations(string $idEntityRelation, string $idFrameBase)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        //$config = config('webtool.relations');
        $frame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedFrame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.feChild",[
            'idEntityRelation' => $idEntityRelation,
            'idFrameBase' => $idFrameBase,
            'frame' => $frame,
            'relatedFrame' => $relatedFrame,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'relationType' => $relation->relationType
            ],
        ]);
    }

    #[Get(path: '/fe/relations/{idEntityRelation}/formNew')]
    public function relationsFEFormNew(int $idEntityRelation)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        //$config = config('webtool.relations');
        $frame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedFrame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.feFormNew",[
            'idEntityRelation' => $idEntityRelation,
            'frame' => $frame,
            'relatedFrame' => $relatedFrame,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'entry' => $relation->relationType
            ]
        ]);
    }

    #[Get(path: '/fe/relations/{idEntityRelation}/grid')]
    public function gridRelationsFE(int $idEntityRelation)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $relation = Criteria::byId("view_relation","idEntityRelation", $idEntityRelation);
        //$config = config('webtool.relations');
        $frame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity1)
            ->where("idLanguage", $idLanguage)
            ->first();
        $relatedFrame = Criteria::table("view_frame")
            ->where("idEntity", $relation->idEntity2)
            ->where("idLanguage", $idLanguage)
            ->first();
        return view("Relation.feGrid",[
            'idEntityRelation' => $idEntityRelation,
            'frame' => $frame,
            'relatedFrame' => $relatedFrame,
            'relation' => (object)[
                'name' => $relation->nameDirect,
                'relationType' => $relation->relationType
            ],
            'relations' => RelationService::listRelationsFE($idEntityRelation)
        ]);
    }
}

<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;

class Construction
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_construction", ['idConstruction', '=', $id])->first();
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_construction", ['idEntity', '=', $idEntity])->first();
    }

    public static function listRelations(int $idEntity)
    {
        return Criteria::table("view_relation")
            ->join("view_semantictype", "view_relation.idEntity2", "=", "view_semantictype.idEntity")
            ->filter([
                ["view_relation.idEntity1", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_hassemtype"],
                ["view_semantictype.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->orderBy("view_semantictype.name")->all();
    }

    public static function listChildren(int $idEntity)
    {
        $rows = Criteria::table("view_relation")
            ->join("view_semantictype", "view_relation.idEntity1", "=", "view_semantictype.idEntity")
            ->filter([
                ["view_relation.idEntity2", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_subtypeof"],
                ["view_semantictype.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_semantictype.idSemanticType", "view_semantictype.idEntity", "view_semantictype.name", "view_relation.idEntityRelation")
            ->orderBy("view_semantictype.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listTree(?string $cxn = '',  ?int $idLanguage = 0)
    {
        $criteria = Criteria::table("view_construction as cxn")
            ->join("language as l", "cxn.cxIdLanguage", "=", "l.idLanguage");
        if ($cxn != '') {
            $criteria = $criteria->where("cxn.name", "startswith", $cxn);
        }
        if ($idLanguage != 0) {
            $criteria = $criteria->where("cxn.cxIdLanguage", "=", $idLanguage);
        }
        $rows = $criteria->where("cxn.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->select("cxn.idConstruction", "cxn.idEntity", "cxn.name", "l.language")
            ->orderBy("cxn.name")->all();
        return $rows;
    }

    public static function listRoots(): array
    {
        $rows = Criteria::table("view_construction as cxn")
            ->distinct(true)
            ->join("language as l", "cxn.cxIdLanguage", "=", "l.idLanguage")
            ->select("l.idLanguage", "l.description")
            ->orderBy("l.description")
            ->all();
//        foreach ($rows as $row) {
//            $row->n = Criteria::table("view_construction")
//                ->where("view_construction.cxIdLanguage", "=", $row->idLanguage)
//                ->count();
//        }
        return $rows;
    }

    public static function getEvokedFrame(int $idConstruction): object|null
    {
        $cxn = Construction::byId($idConstruction);
        $relation = Criteria::table("view_relation")
            ->filter([
                ["view_relation.idEntity1", "=", $cxn->idEntity],
                ["view_relation.relationType", "=", "rel_evokes"],
            ])->first();
        $frame = $relation ? Frame::byIdEntity($relation->idEntity2) : null;
        return $frame;
    }

}


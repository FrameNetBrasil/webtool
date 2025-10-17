<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;

class Qualia
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_qualia", ['idQualia', '=', $id])->first();
    }

    public static function byIdRaw(int $id): object
    {
        return Criteria::byFilter("qualia", ['idQualia', '=', $id])->first();
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_qualia", ['idEntity', '=', $idEntity])->first();
    }

    public static function listRelations(int $idEntity)
    {
        return Criteria::table("view_relation")
            ->join("view_qualia", "view_relation.idEntity2", "=", "view_qualia.idEntity")
            ->filter([
                ["view_relation.idEntity1", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_hassemtype"],
                ["view_qualia.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->orderBy("view_qualia.name")->all();
    }

    public static function listChildren(int $idEntity)
    {
        $rows = Criteria::table("view_relation")
            ->join("view_qualia", "view_relation.idEntity1", "=", "view_qualia.idEntity")
            ->filter([
                ["view_relation.idEntity2", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_subtypeof"],
                ["view_qualia.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_qualia.idSemanticType", "view_qualia.idEntity", "view_qualia.name", "view_relation.idEntityRelation")
            ->orderBy("view_qualia.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listTree(string $semanticType)
    {
        $rows = Criteria::table("view_qualia")
            ->filter([
                ["view_qualia.name", "startswith", $semanticType],
                ["view_qualia.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_qualia.idSemanticType", "view_qualia.idEntity", "view_qualia.name")
            ->orderBy("view_qualia.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listTypes(): array
    {
        return Criteria::table("view_typeinstance as ti")
            ->join("qualia as q","q.iDTypeInstance","=","ti.idTypeInstance")
            ->select("ti.idTypeInstance", "ti.name")
            ->distinct()
            ->where("ti.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->orderBy("name")->get()->keyBy("idTypeInstance")->all();
    }

    public static function listByType(int $idTypeInstance): array
    {
        $rows = Criteria::table("view_qualia")
            ->filter([
                ['idTypeInstance', '=', $idTypeInstance],
                ['view_qualia.idLanguage', '=', AppService::getCurrentIdLanguage()],
            ])->select("view_qualia.idQualia", "view_qualia.idEntity", "view_qualia.name", "view_qualia.info", "view_qualia.frameName")
            ->orderBy("view_qualia.info")->all();
//        foreach ($rows as $row) {
//            $row->n = Criteria::table("view_relation")
//                ->where("view_relation.idEntity2", "=", $row->idEntity)
//                ->where("view_relation.relationType", "=", "rel_subtypeof")
//                ->count();
//        }
        return $rows;
    }

    public static function listRoots(): array
    {
        $criteriaER = Criteria::table("view_relation")
            ->select('idEntity1')
            ->where("relationType", "=", 'rel_subtypeof');
        $rows = Criteria::table("view_qualia")
            ->where("view_qualia.idEntity", "NOT IN", $criteriaER)
            ->filter([
                ['view_qualia.idLanguage', '=', AppService::getCurrentIdLanguage()],
            ])->select("view_qualia.idSemanticType", "view_qualia.idEntity", "view_qualia.name")
            ->orderBy("view_qualia.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listByLU(int $idEntityLU)
    {
        return Criteria::table("view_relation")
            ->join("view_lu as lu1", "view_relation.idEntity1", "=", "lu1.idEntity")
            ->join("view_lu as lu2", "view_relation.idEntity2", "=", "lu2.idEntity")
            ->join("qualia as qlr", "view_relation.idEntity3", "=", "qlr.idEntity")
            ->filter([
                ["view_relation.idEntity1", "=", $idEntityLU],
                ["view_relation.relationGroup", "=", "rgp_qualia"],
                ["lu1.idLanguage", "=", AppService::getCurrentIdLanguage()],
                ["lu2.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_relation.idEntityRelation","lu1.name as lu1Name", "lu2.name as lu2Name", "qlr.info as qlrInfo")
            ->orderBy("qlr.info")
            ->orderBy("lu2.name")
            ->all();
    }

}

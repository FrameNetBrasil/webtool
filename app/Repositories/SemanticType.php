<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\RelationService;

class SemanticType
{
    public static function byId(int $id): object
    {
        $st = Criteria::byFilterLanguage("view_semantictype", ['idSemanticType', '=', $id])->first();
        $st->parent = self::getParent($id);
        return $st;
    }
    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_semantictype", ['idEntity', '=', $idEntity])->first();
    }
    public static function listFrameDomain(): array
    {
        return Criteria::byFilterLanguage("view_semantictype", ['entry', 'startswith', 'sty\_fd'])
            ->select('idSemanticType', 'name')
            ->orderBy('name')
            ->all();
    }

    public static function listFrameType()
    {
        return Criteria::byFilterLanguage("view_semantictype", ['entry', 'startswith', 'sty\_ft'])
            ->select('idSemanticType', 'name')
            ->orderBy('name')
            ->all();
    }

    public static function listNamespace()
    {
        return Criteria::byFilterLanguage("view_namespace",[])
            ->select('idSemanticType', 'name')
            ->orderBy('name')
            ->all();
    }

    public static function listRelations(int $idEntity)
    {
        return Criteria::table("view_relation as r")
            ->join("view_semantictype as st", "r.idEntity2", "=", "st.idEntity")
            ->where("r.idEntity1", "=", $idEntity)
            ->where("st.idLanguage",AppService::getCurrentIdLanguage())
            ->orderBy("st.name")->all();
    }

    public static function listTree(string $semanticType)
    {
        $rows = Criteria::table("view_semantictype")
            ->filter([
                ["view_semantictype.name", "startswith", $semanticType],
                ["view_semantictype.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_semantictype.idSemanticType", "view_semantictype.idEntity", "view_semantictype.name")
            ->orderBy("view_semantictype.name")->all();
        foreach($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listDomains(): array
    {
        return Criteria::table("view_domain")
            ->select("idDomain","name")
            ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
            ->orderBy("name")->get()->keyBy("idDomain")->all();
    }

    public static function listRootByDomain(int $idDomain) : array
    {
        $criteriaER = Criteria::table("view_relation")
            ->select('idEntity1')
            ->where("relationType", "=", 'rel_subtypeof');
        $rows = Criteria::table("view_semantictype")
            ->where("view_semantictype.idEntity", "NOT IN", $criteriaER)
            ->filter([
                ['idDomain', '=', $idDomain],
                ['view_semantictype.idLanguage', '=', AppService::getCurrentIdLanguage()],
            ])->select("view_semantictype.idSemanticType", "view_semantictype.idEntity", "view_semantictype.name")
            ->orderBy("view_semantictype.name")->all();
        foreach($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listRoots() : array
    {
        $criteriaER = Criteria::table("view_relation as r")
            ->select('r.idEntity1')
            ->where("r.name", "=", 'subsumption');
        $rows = Criteria::table("view_semantictype")
            ->where("view_semantictype.idEntity", "NOT IN", $criteriaER)
            ->where('view_semantictype.idLanguage', AppService::getCurrentIdLanguage())
            ->select("view_semantictype.idSemanticType", "view_semantictype.idEntity", "view_semantictype.name", "view_semantictype.description")
            ->orderBy("view_semantictype.name")->all();
        return $rows;
    }

    public static function listChildren(int $idSemanticType)
    {
        $rows = Criteria::table("view_relation as r")
            ->join("view_semantictype as child", "r.idEntity1", "=", "child.idEntity")
            ->join("view_semantictype as parent", "r.idEntity2", "=", "parent.idEntity")
            ->select("child.idSemanticType", "child.idEntity", "child.name", "child.description")
            ->where("r.name", "=", 'subsumption')
            ->where("child.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("parent.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("parent.idSemanticType", "=", $idSemanticType)
            ->orderBy("child.name")
            ->all();
        return $rows;
    }

    public static function countChildren(int $idSemanticType)
    {
        return Criteria::table("view_relation as r")
            ->join("view_semantictype as parent", "r.idEntity2", "=", "parent.idEntity")
            ->join("view_semantictype as child", "r.idEntity1", "=", "child.idEntity")
            ->where("r.name", "=", 'subsumption')
            ->where("parent.idSemanticType", "=", $idSemanticType)
            ->where("child.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("parent.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->count();
    }

    public static function getParent(int $idSemanticType)
    {
        $parent = Criteria::table("view_relation as r")
            ->join("view_microframe as mf", "r.idEntity1", "=", "mf.idEntity")
            ->join("view_semantictype as child", "r.idEntity2", "=", "child.idEntity")
            ->join("view_semantictype as parent", "r.idEntity3", "=", "parent.idEntity")
            ->where("mf.name", "=", 'subsumption')
            ->where("mf.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("child.idSemanticType", "=", $idSemanticType)
            ->select("parent.idSemanticType", "parent.name")
            ->first();
        return $parent ?? null;
    }

    public static function setParent(int $idSemanticType, int $idSemanticTypeParent)
    {
        $child = Criteria::table("semantictype")
            ->where("idSemanticType", "=", $idSemanticType)
            ->first();
        $parent = Criteria::table("semantictype")
            ->where("idSemanticType", "=", $idSemanticTypeParent)
            ->first();
        RelationService::createMicroframe("subsumption", $child->idEntity, $parent->idEntity);
    }

}


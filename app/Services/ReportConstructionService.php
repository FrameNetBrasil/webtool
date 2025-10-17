<?php

namespace App\Services;

use App\Database\Criteria;
use App\Repositories\Construction;
use App\Repositories\Language;

class ReportConstructionService
{

    public static function report(int|string $idConstruction, string $lang = ''): array
    {
        $report = [];
        if ($lang != '') {
            $language = Criteria::byId("language", "language", $lang);
            $idLanguage = $language->idLanguage;
            AppService::setCurrentLanguage($idLanguage);
        } else {
            $idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_numeric($idConstruction)) {
            $cxn = Construction::byId($idConstruction);
        } else {
            $cxn = Criteria::table("view_construction")
                ->where("name", $idConstruction)
                ->where("idLanguage", $idLanguage)
                ->first();
        }
        $ces = Criteria::table("view_constructionelement")
            ->where("idLanguage", "=", $idLanguage)
            ->where("idConstruction", "=", $cxn->idConstruction)
            ->all();
        $report['construction'] = $cxn;
        $report['ces'] = self::getCEData($ces);
        $report['construction']->description = self::decorate($cxn->description, $report['ces']['styles']);
        $report['concepts'] = self::getConcepts($cxn->idEntity);
        $report['evokes'] = self::getEvokes($cxn->idEntity);
        $report['relations'] = self::getRelations($cxn);
        $report['language'] = Language::byId($cxn->cxIdLanguage);
        foreach ($ces as $ce) {
            $report['conceptsCE'][$ce->idConstructionElement] = self::getConcepts($ce->idEntity);
            $report['evokesCE'][$ce->idConstructionElement] = self::getEvokesCE($ce->idEntity);
            $report['constraintsCE'][$ce->idConstructionElement] = self::getConstraints($ce->idEntity);
        }
        return $report;
    }

    public static function getCEData($ces): array
    {
        foreach ($ces as $ce) {
            $styles[strtolower($ce->name)] = "color_{$ce->idColor}";
        }
        foreach ($ces as $ce) {
            $ce->lower = strtolower($ce->name);
            $ce->description = self::decorate($ce->description, $styles);
        }
        return [
            'styles' => $styles,
            'ces' => $ces
        ];
    }

    public static function getRelations($cxn): array
    {
        $relations = [];
        $result = RelationService::listRelationsCxn($cxn->idConstruction);
        foreach ($result as $row) {
            $relationName = $row->relationType . '|' . $row->name;
            $relations[$relationName][$row->idCxnRelated] = [
                'idEntityRelation' => $row->idEntityRelation,
                'idConstruction' => $row->idCxnRelated,
                'name' => $row->related,
                'color' => $row->color
            ];
        }
        ksort($relations);
        return $relations;
    }

    public static function getRelationsCE($ce): array
    {
        $relations = [];
        $result = RelationService::listRelationsCE($ce->idConstructionElement);
        foreach ($result as $row) {
            $relationName = $row->relationType . '|' . $row->name;
            $relations[$relationName][$row->idCERelated] = [
                'idEntityRelation' => $row->idEntityRelation,
                'idConstructionElement' => $row->idCERelated,
                'name' => $row->related,
                'color' => $row->color
            ];
        }
        ksort($relations);
        return $relations;
    }

    public static function getConcepts(int $idEntity): array
    {
        $concepts = Criteria::table("view_relation as r")
            ->join("view_concept as c", "r.idEntity2", "=", "c.idEntity")
            ->where("r.idEntity1", $idEntity)
            ->where("r.relationType","rel_hasconcept")
            ->where("c.idLanguage", AppService::getCurrentIdLanguage())
            ->select("r.relationType","c.idConcept","c.name","c.type")
            ->orderBy("c.name")
            ->all();
        return $concepts;
    }

    public static function getEvokes(int $idEntity): array
    {
        $evokes = Criteria::table("view_relation as r")
            ->join("view_frame as f", "r.idEntity2", "=", "f.idEntity")
            ->where("r.idEntity1", $idEntity)
            ->where("r.relationType","rel_evokes")
            ->where("f.idLanguage", AppService::getCurrentIdLanguage())
            ->select("r.relationType","f.idFrame","f.name")
            ->orderBy("f.name")
            ->all();
        return $evokes;
    }

    public static function getEvokesCE(int $idEntity): array
    {
        $evokes = Criteria::table("view_relation as r")
            ->join("view_frameelement as f", "r.idEntity2", "=", "f.idEntity")
            ->where("r.idEntity1", $idEntity)
            ->where("r.relationType","rel_evokes")
            ->where("f.idLanguage", AppService::getCurrentIdLanguage())
            ->select("r.relationType","f.idFrame","f.name","f.frameName")
            ->orderBy("f.name")
            ->all();
        return $evokes;
    }

    public static function getConstraints(int $idEntity): array
    {
        $constraints = Criteria::table("view_constrainedby as c")
            ->where("c.idConstrained", $idEntity)
            ->where("c.idLanguage", AppService::getCurrentIdLanguage())
            ->select("c.conName","c.idConstraint","c.name")
            ->get()->groupBy("conName")->toArray();
        return $constraints;
    }

    public static function decorate($description, $styles)
    {
        $sentence = utf8_decode($description);
        $decorated = preg_replace_callback(
            "/\#([^\s\.\,\;\?\!\']*)/i",
            function ($matches) use ($styles) {
                $m = substr($matches[0], 1);
                $l = strtolower($m);
                foreach ($styles as $fe => $s) {
                    if(utf8_encode($l) ==  $fe) {
                        return "<span class='{$s}'>{$m}</span>";
                    }
                }
                return $m;
            },
            $sentence
        );
        $partial = utf8_encode($decorated);
        $final = preg_replace_callback(
            "/\[([^\]]*)\]/i",
            function ($matches) use ($styles) {
                $m = substr($matches[0], 1, -1);
                $l = strtolower($m);
                foreach ($styles as $fe => $s) {
                    if (str_contains(utf8_encode($l), '|target')) {
                        $m = substr($m, 0, strpos($m, '|'));
                        return "<span class='color_target'>{$m}</span>";
                    } else {
                        if (str_contains(utf8_encode($l), '|' . $fe)) {
                            $m = substr($m, 0, strpos($m, '|'));
                            return "<span class='{$s}'>{$m}</span>";
                        }
                    }
                }
                return $m;
            },
            $partial
        );
        return $final;
    }


}

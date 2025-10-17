<?php

namespace App\Services;

use App\Database\Criteria;
use App\Repositories\Concept;
use App\Repositories\Frame;
use App\Repositories\SemanticType;

class ReportC5Service
{

    public static function report(int|string $idConcept, string $lang = ''): array
    {
        $report = [];
        if ($lang != '') {
            $language = Criteria::byId("language", "language", $lang);
            $idLanguage = $language->idLanguage;
            AppService::setCurrentLanguage($idLanguage);
        } else {
            $idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_numeric($idConcept)) {
            $concept = Concept::byId($idConcept);
        } else {
            $concept = Criteria::table("view_concept")
                ->where("name", $idConcept)
                ->where("idLanguage", $idLanguage)
                ->first();
        }
        $report['concept'] = $concept;
        $report['constituents'] = self::getConstituents($concept);
        $relations = self::getRelations($concept);
        $report['relations'] = $relations["relations"];
        debug($relations["types"]);
        $report['relationTypes'] = $relations["types"];
        return $report;
    }

    public static function getRelations($concept): array
    {
        $relations = [];
        $result = RelationService::listRelationsConcept($concept->idConcept);
        foreach ($result as $row) {
            $relations['types'][$row->relationType] = $row->relationType;
            $relationName = $row->relationType . '|' . $row->name . '|' . $row->color;
            $relations['relations'][$relationName][$row->idConceptRelated] = [
                'idEntityRelation' => $row->idEntityRelation,
                'idConcept' => $row->idConceptRelated,
                'name' => $row->related,
                'color' => $row->color,
                'type' => $row->type
            ];
        }
        ksort($relations['relations']);
        return $relations;
    }

    public static function getConstituents($concept): array
    {
        $relations = [];
        $result = RelationService::listRelationsConcept($concept->idConcept);
        foreach ($result as $row) {
            if ($row->relationType == 'rel_constituentof') {
                $relationName = $row->relationType . '|' . $row->name . '|' . $row->color;
                $relations[$relationName][$row->idConceptRelated] = [
                    'idEntityRelation' => $row->idEntityRelation,
                    'idConcept' => $row->idConceptRelated,
                    'name' => $row->related,
                    'color' => $row->color,
                    'type' => $row->type
                ];
            }
        }
        ksort($relations);
        return $relations;
    }

}

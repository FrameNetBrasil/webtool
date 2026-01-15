<?php

namespace App\Services\SemanticType;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\RelationService;

class ReportService
{

    public static function report(int|string $idSemanticType, string $lang = ''): array
    {
        $report = [];
        if ($lang != '') {
            $language = Criteria::byId("language", "language", $lang);
            $idLanguage = $language->idLanguage;
            AppService::setCurrentLanguage($idLanguage);
        } else {
            $idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_numeric($idSemanticType)) {
            $semanticType = SemanticType::byId($idSemanticType);
        } else {
            $semanticType = Criteria::table("view_semantictype")
                ->where("name", $idSemanticType)
                ->where("idLanguage", $idLanguage)
                ->first();
        }
        $report['semanticType'] = $semanticType;
        $report['relations'] = self::getRelations($semanticType);
        return $report;
    }

    public static function getRelations($semanticType): array
    {
        $relations = [];
        $result = RelationService::listRelationsSemanticType($semanticType->idSemanticType);
        foreach ($result as $row) {
            $relationName = $row->relationType . '|' . $row->name;
            $relations[$relationName][$row->idSTRelated] = [
                'idEntityRelation' => $row->idEntityRelation,
                'idConcept' => $row->idSTRelated,
                'name' => $row->related,
                'color' => $row->color
            ];
        }
        ksort($relations);
        return $relations;
    }

}

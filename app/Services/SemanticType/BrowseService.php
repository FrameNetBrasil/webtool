<?php

namespace App\Services\SemanticType;

use App\Data\SemanticType\SearchData;
use App\Database\Criteria;
use App\Repositories\SemanticType;

class BrowseService
{
    public static int $limit = 300;

    public static function browseSemanticTypeBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->semanticType != '') {
            $semanticTypes = Criteria::byFilterLanguage('view_semantictype', ['name', 'startswith', $search->semanticType])
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();
        } else {
            if ($search->id != '') {
                $semanticTypes = SemanticType::listChildren($search->id);
            } else {
                $semanticTypes = SemanticType::listRoots();
            }
        }
//        debug($semanticTypes);
        foreach ($semanticTypes as $semanticType) {
            $result[$semanticType->idSemanticType] = [
                'id' => $semanticType->idSemanticType,
                'type' => 'semanticType',
                'name' => $semanticType->name,
                'text' => view('SemanticType.partials.semanticType', ['semanticType' => $semanticType])->render(),
                'leaf' => SemanticType::countChildren($semanticType->idSemanticType) <= 0,
                'state' => 'closed',
            ];
        }
        return $result;
    }

}

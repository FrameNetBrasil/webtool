<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;

class GenericLabel
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("genericlabel", ["idGenericLabel","=", $id])->first();
    }

    public static function listByLayerType(int $idLayerType): array
    {
        return Criteria::table("genericlabel")
            ->where('idLayerType', $idLayerType)
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->select('idGenericLabel', 'name', 'definition', 'idColor')
            ->orderBy('name')
            ->all();
    }

    public static function listForSelect(?string $name = ''): array
    {
        $criteria = Criteria::table("genericlabel")
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->select(['idGenericLabel as id', 'name'])
            ->orderBy('name');

        if (! empty($name) && strlen($name) > 1) {
            $criteria->where('name', 'startswith', $name);
        }

        return $criteria->all();
    }
}

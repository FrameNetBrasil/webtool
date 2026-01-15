<?php

namespace App\Repositories;

use App\Database\Criteria;

class LayerGroup
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("layergroup", ["idLayerGroup","=", $id])->first();
    }

    public static function listForSelect(?string $name = ''): array
    {
        $criteria = Criteria::table("layergroup")
            ->select(['idLayerGroup as id', 'name'])
            ->orderBy('name');

        if (! empty($name) && strlen($name) > 1) {
            $criteria->where('name', 'startswith', $name);
        }

        return $criteria->all();
    }

    public static function listByFilter(string $name = '', string $type = ''): array
    {
        $criteria = Criteria::table("layergroup")
            ->select('*')
            ->orderBy('name')
            ->limit(300);

        if (! empty($name)) {
            $criteria->where('name', 'startswith', $name);
        }

        if (! empty($type)) {
            $criteria->where('type', '=', $type);
        }

        return $criteria->all();
    }
}

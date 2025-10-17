<?php

namespace App\Repositories;

use App\Database\Criteria;

class LayerGroup
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("layergroup", ["idLayerGroup","=", $id])->first();
    }
}

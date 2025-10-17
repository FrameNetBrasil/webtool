<?php

namespace App\Repositories;

use App\Database\Criteria;

class GenericLabel
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("genericlabel", ["idGenericLabel","=", $id])->first();
    }
}

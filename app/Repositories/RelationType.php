<?php

namespace App\Repositories;

use App\Database\Criteria;

class RelationType
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_relationtype", ["idRelationType","=", $id])->first();
    }
}

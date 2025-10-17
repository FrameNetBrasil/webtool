<?php

namespace App\Repositories;

use App\Database\Criteria;

class RelationGroup
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_relationgroup", ["idRelationGroup","=", $id])->first();
    }
}

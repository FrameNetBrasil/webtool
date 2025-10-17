<?php

namespace App\Repositories;


use App\Database\Criteria;

class Domain
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_domain", ["idDomain", "=", $id])->first();
    }
}

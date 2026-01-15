<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\RelationService;
use Illuminate\Support\Facades\DB;

class Namespace_
{
    public static function byId(int $id): object
    {
        $frame = Criteria::byFilterLanguage("view_namespace", ['idNamespace', '=', $id])->first();
        return $frame;
    }

}

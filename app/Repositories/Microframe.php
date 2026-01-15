<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\RelationService;
use Illuminate\Support\Facades\DB;

class Microframe
{
    public static function byId(int $id): object
    {
        $frame = Criteria::byFilterLanguage("view_microframe", ['idFrame', '=', $id])->first();
        return $frame;
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_microframe", ['idEntity', '=', $idEntity])->first();
    }

}

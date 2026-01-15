<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\LU\AISuggestionService;

class Class_
{
    public static function byId(int $id): object
    {
        $frame = Criteria::byFilterLanguage("view_class", ['idFrame', '=', $id])->first();
        return $frame;
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_class", ['idEntity', '=', $idEntity])->first();
    }

    public static function getFETarget(int $idClass): object
    {
        return Criteria::table("view_frameelement")
            ->where("idFrame", $idClass)
            ->where("coreType", "cty_target")
            ->where("idLanguage",AppService::getCurrentIdLanguage())
            ->first();
    }

}

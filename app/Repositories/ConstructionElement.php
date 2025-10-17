<?php

namespace App\Repositories;

use App\Data\CE\UpdateData;
use App\Database\Criteria;
use App\Services\AppService;

class ConstructionElement
{
    public static function byId(int $id): object
    {
        $ce = Criteria::byFilterLanguage("view_constructionelement", ['idConstructionElement', '=', $id])->first();
        $ce->cxn = Construction::byId($ce->idConstruction);
        return $ce;
    }

    public static function update(UpdateData $object)
    {
        Criteria::table("constructionelement")
            ->where("idConstructionElement", "=", $object->idConstructionElement)
            ->update([
                'idColor' => $object->idColor
            ]);
    }

    public static function listForGridByCxn(int $idConstruction)
    {
        return Criteria::table("view_constructionelement")
            ->where("idConstruction", "=", $idConstruction)
            ->where("idLanguage", '=', AppService::getCurrentIdLanguage())
            ->all();
    }
}


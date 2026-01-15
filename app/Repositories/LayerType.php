<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;

class LayerType
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_layertype", ["idLayerType","=", $id])->first();
    }
    public static function listToLU(object $lu): array
    {
        $array = ['lty_fe', 'lty_gf', 'lty_pt', 'lty_other', 'lty_target', 'lty_sent'];
        $lPOS = ['V' => 'lty_verb', 'N' => 'lty_noun', 'A' => 'lty_adj', 'ADV' => 'lty_adv', 'PREP' => 'lty_prep'];
        $udPOS = Criteria::byId("pos_udpos","idUDPOS", $lu->idUDPOS);
        debug($udPOS);
        $pos = Criteria::byId("pos", "idPOS", $udPOS->idPOS);
        if (isset($lPOS[$pos->POS])) {
            $array[] = $lPOS[$pos->POS];
        }
        $criteria = Criteria::table("view_layertype")
            ->select('idLayerType','entry','name')
            ->where('entry', 'IN', $array)
            ->where('idLanguage',AppService::getCurrentIdLanguage())
            ->orderBy('layerOrder');
        return $criteria->all();
    }

    public static function listToFlex(): array
    {
        return Criteria::table("view_layertype")
            ->where('layerGroup', 'Flex-syntax')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->select('idLayerType', 'entry', 'name', 'layerOrder')
            ->orderBy('layerOrder')
            ->all();
    }

    public static function listByLayerGroup(int $idLayerGroup): array
    {
        return Criteria::table("view_layertype")
            ->where('idLayerGroup', $idLayerGroup)
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->select('idLayerType', 'entry', 'name', 'layerOrder')
            ->orderBy('layerOrder')
            ->all();
    }

    public static function listForSelect(?string $name = ''): array
    {
        $criteria = Criteria::table("view_layertype")
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->select(['idLayerType as id', 'name'])
            ->orderBy('name');

        if (! empty($name) && strlen($name) > 1) {
            $criteria->where('name', 'startswith', $name);
        }

        return $criteria->all();
    }

}


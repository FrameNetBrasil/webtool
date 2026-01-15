<?php

namespace App\Services\Cluster;

use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static function browseClusterBySearch(object $search): array
    {
        $result = [];
        $clusters = Criteria::table('view_cluster as c')
            ->where('c.name', 'startswith', $search->cluster)
            ->where('c.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('c.name')->all();
        foreach ($clusters as $cluster) {
            $result[$cluster->idFrame] = [
                'id' => $cluster->idFrame,
                'type' => 'cluster',
                'text' => view('Cluster.partials.cluster', ['cluster' => $cluster])->render(),
                'leaf' => true,
            ];
        }

        return $result;
    }
}

<?php

namespace App\Services\Frame;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Database\Query\JoinClause;

class BrowseService
{
    public static function browseFrameBySearch(object $search): array
    {
        $result = [];
        $subQuery = Criteria::table('view_frame_classification')
            ->selectRaw('idFrame, group_concat(name) as domain')
            ->where('relationType', 'rel_framal_domain')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->groupBy('idFrame');
        $frames = Criteria::table('view_frame as f')
            ->where('f.name', 'startswith', $search->frame)
            ->where('f.idLanguage', AppService::getCurrentIdLanguage())
            ->joinSub($subQuery, 'domains', function (JoinClause $join) {
                $join->on('f.idFrame', '=', 'domains.idFrame');
            })
            ->orderBy('name')->all();
        foreach ($frames as $frame) {
            $result[$frame->idFrame] = [
                'id' => $frame->idFrame,
                'type' => 'frame',
                'text' => view('Frame.partials.frame', (array) $frame)->render(),
                'leaf' => true,
            ];
        }

        return $result;
    }

    public static function browseLUBySearch(object $search, bool $leaf = true, bool $contains = false): array
    {
        $result = [];
        $op = $contains ? 'contains' : 'startswith';
        debug("op",$op);
        $lus = Criteria::byFilterLanguage('view_lu', ['name', $op, $search->lu], 'idLanguage')
            ->limit(300)
            ->orderBy('name')
            ->all();
        foreach ($lus as $lu) {
            $result[$lu->idLU] = [
                'id' => $lu->idLU,
                'type' => 'lu',
                'text' => view('Frame.partials.lu', (array) $lu)->render(),
                'leaf' => $leaf,
            ];
        }

        return $result;
    }

    public static function browseLUForReframingBySearch(object $search, bool $leaf = true): array
    {
        $result = [];
        if ($search->lu != '') {
            $lus = Criteria::byFilterLanguage('view_lu', ['name', 'startswith', $search->lu], 'idLanguage')
                ->limit(300)
                ->orderBy('name')
                ->all();
            foreach ($lus as $lu) {
                $result[$lu->idLU] = [
                    'id' => $lu->idLU,
                    'type' => 'lu',
                    'text' => view('LU.Reframing.partials.lu', (array) $lu)->render(),
                    'leaf' => $leaf,
                ];
            }
        }

        return $result;
    }
}

<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\RelationService;

class Cluster
{
    public static function byId(int $id): object
    {
        $frame = Criteria::byFilterLanguage('view_cluster', ['idFrame', '=', $id])->first();

        return $frame;
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage('view_cluster', ['idEntity', '=', $idEntity])->first();
    }

    public static function listFECoreSet(int $idFrame): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $result = Criteria::table('view_fe_internal_relation')
            ->where('relationType', 'rel_coreset')
            ->where('fe1IdFrame', $idFrame)
            ->where('idLanguage', $idLanguage)
            ->all();
        $index = [];
        $i = 0;
        foreach ($result as $row) {
            if (! isset($index[$row->fe1Name]) && ! isset($index[$row->fe2Name])) {
                $i++;
                $index[$row->fe1Name] = $i;
                $index[$row->fe2Name] = $i;
            } elseif (! isset($index[$row->fe1Name])) {
                $index[$row->fe1Name] = $index[$row->fe2Name];
            } else {
                $index[$row->fe2Name] = $index[$row->fe1Name];
            }
        }
        $feCoreSet = [];
        foreach ($index as $fe => $i) {
            $feCoreSet[$i][] = $fe;
        }

        return $feCoreSet;
    }

    public static function listScenarioFrames(int $idFrameScenario): array
    {
        $children = [];
        self::listScenarioChildren($idFrameScenario, $children);

        return $children;
    }

    private static function listScenarioChildren(int $idFrame, &$children = [])
    {
        $frames = RelationService::listFrameChildren($idFrame);
        foreach ($frames as $frame) {
            if (($frame->relationType == 'rel_inheritance') || ($frame->relationType == 'rel_subframe') || ($frame->relationType == 'rel_perspective_on')) {
                self::listScenarioChildren($frame->idFrame, $children);
                $children[$frame->name] = $frame;
            }
        }
    }

    public static function getClassification(int $idFrame): array
    {
        return Criteria::byFilterLanguage('view_frame_classification', ['idFrame', '=', $idFrame])
            ->treeResult('relationType')->all();
    }
}

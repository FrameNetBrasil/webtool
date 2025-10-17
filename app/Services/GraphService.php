<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\EntityRelation;
use App\Repositories\Frame;
use App\Repositories\SemanticType;
use App\Repositories\ViewRelation;

class GraphService extends Controller
{
    public static function listFrameRelationsForGraph(array $idArray, array $idRelationType)
    {
        $nodes = [];
        $links = [];
        $relation = new ViewRelation();
        foreach ($idArray as $idEntity) {
            $partial = $relation->listForFrameGraph($idEntity);
            foreach ($partial as $r) {
                if (in_array($r['idRelationType'], $idRelationType)) {
                    $nodes[$r['idEntity1']] = [
                        'type' => 'frame',
                        'name' => $r['frame1Name']
                    ];
                    $nodes[$r['idEntity2']] = [
                        'type' => 'frame',
                        'name' => $r['frame2Name']
                    ];
                    $links[$r['idEntity1']][$r['idEntity2']] = [
                        'type' => 'ff',
                        'idEntityRelation' => $r['idEntityRelation'],
                        'relationEntry' => $r['entry'],
                    ];
                }
            }
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    public static function listFrameFERelationsForGraph(int $idEntityRelation)
    {
        $nodes = [];
        $links = [];
        $baseRelation = new ViewRelation($idEntityRelation);
        $icon = config('webtool.fe.icon.grapher');
        $frame = new Frame();
        $relations = $frame->listFEDirectRelations($idEntityRelation);
        foreach($relations as $relation) {
            $nodes[$relation['feIdEntity']] = [
                'type' => 'fe',
                'name' => $relation['feName'],
                'icon' => $icon[$relation['feCoreType']],
                'idColor' => $relation['feIdColor']
            ];
            $nodes[$relation['relatedFEIdEntity']] = [
                'type' => 'fe',
                'name' => $relation['relatedFEName'],
                'icon' => $icon[$relation['relatedFECoreType']],
                'idColor' => $relation['relatedFEIdColor']
            ];
            $links[$baseRelation->idEntity1][$relation['feIdEntity']] = [
                'type' => 'ffe',
                'idEntityRelation' => $idEntityRelation,
                'relationEntry' => 'rel_has_element',
            ];
            $links[$relation['relatedFEIdEntity']][$baseRelation->idEntity2] = [
                'type' => 'ffe',
                'idEntityRelation' => $idEntityRelation,
                'relationEntry' => 'rel_has_element',
            ];
            $links[$relation['feIdEntity']][$relation['relatedFEIdEntity']] = [
                'type' => 'fefe',
                'idEntityRelation' => $relation['idEntityRelation'],
                'relationEntry' => $relation['entry'],
            ];
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

    public static function listDomainForGraph(int $idSemanticType, array $idRelationType)
    {
        $nodes = [];
        $links = [];
        if ($idSemanticType > 0) {
            $semanticType = new SemanticType($idSemanticType);
            $frames = Frame::listByFrameDomain($semanticType->idEntity)->getResult();
            $relation = new ViewRelation();
            foreach ($frames as $frame) {
                $idEntity = $frame['idEntity'];
                $partial = $relation->listForFrameGraph($idEntity);
                foreach ($partial as $r) {
                    if (in_array($r['idRelationType'], $idRelationType)) {
                        $nodes[$r['idEntity1']] = [
                            'type' => 'frame',
                            'name' => $r['frame1Name']
                        ];
                        $nodes[$r['idEntity2']] = [
                            'type' => 'frame',
                            'name' => $r['frame2Name']
                        ];
                        $links[$r['idEntity1']][$r['idEntity2']] = [
                            'type' => 'ff',
                            'idEntityRelation' => $r['idEntityRelation'],
                            'relationEntry' => $r['entry'],
                        ];
                    }
                }
            }
        }
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }

}

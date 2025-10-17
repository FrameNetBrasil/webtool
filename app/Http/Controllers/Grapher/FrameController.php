<?php

namespace App\Http\Controllers\Grapher;

use App\Data\Grapher\FrameData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Services\RelationService;
use App\Services\ReportFrameService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class FrameController extends Controller
{
    #[Get(path: '/grapher/frame')]
    public function frame()
    {
        $relations = Criteria::byFilterLanguage('view_relationtype', [
            'rgEntry', '=', 'rgp_frame_relations',
        ])->all();
        $dataRelations = [];
        // $config = config('webtool.relations');
        foreach ($relations as $relation) {
            $dataRelations[] = (object) [
                'idRelationType' => $relation->idRelationType,
                'name' => $relation->nameDirect,
                'entry' => $relation->entry,
                'color' => $relation->color,
            ];
        }

        return view('Grapher.Frame.frame', [
            'relations' => $dataRelations,
        ]);
    }

    #[Post(path: '/grapher/frame/graph/{idEntity?}')]
    public function frameGraph(FrameData $data, ?int $idEntity = null)
    {
        $nodes = session('graphNodes') ?? [];
        if (! is_null($data->idFrame)) {
            $frame = Frame::byId($data->idFrame);
            $nodes = [$frame->idEntity];
        }
        if (empty($data->frameRelation)) {
            $data->frameRelation = session('frameRelation') ?? [];
        }
        if (! is_null($idEntity)) {
            if ($idEntity == 0) {
                $nodes = [];
            } else {
                $nodes = [...$nodes, $idEntity];
            }
        }
        session([
            'graphNodes' => $nodes,
            'frameRelation' => $data->frameRelation,
        ]);

        return view('Grapher.Frame.frameGraph', [
            'graph' => RelationService::listFrameRelationsForGraph($nodes, $data->frameRelation),
        ]);
    }

    #[Post(path: '/grapher/framefe/graph/{idEntityRelation}')]
    public function frameFeGraph(?int $idEntityRelation = null)
    {
        $frameRelation = session('frameRelation') ?? [];
        $nodes = session('graphNodes') ?? [];
        $graph = RelationService::listFrameRelationsForGraph($nodes, $frameRelation);
        $feGraph = RelationService::listFrameFERelationsForGraph($idEntityRelation);
        foreach ($feGraph['nodes'] as $idNode => $node) {
            $graph['nodes'][$idNode] = $node;
        }
        foreach ($feGraph['links'] as $idSource => $links) {
            foreach ($links as $idTarget => $link) {
                $graph['links'][$idSource][$idTarget] = $link;
            }
        }

        return view('Grapher.Frame.frameGraph', [
            'graph' => $graph,
        ]);
    }

    #[Get(path: '/grapher/frame/report/{idEntityFrame}')]
    public function frameReport(int $idEntityFrame)
    {
        $frame = Frame::byIdEntity($idEntityFrame);
        $data = ReportFrameService::report($frame->idFrame);

        return view('Grapher.frameReport', $data);
    }
}

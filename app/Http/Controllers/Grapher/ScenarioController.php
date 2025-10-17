<?php

namespace App\Http\Controllers\Grapher;

use App\Data\Grapher\ScenarioData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ScenarioController extends Controller
{
    #[Get(path: '/grapher/scenario')]
    public function scenario()
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

        return view('Grapher.Scenario.scenario', [
            'relations' => $dataRelations,
        ]);
    }

    #[Post(path: '/grapher/scenario/graph/{idEntity?}')]
    public function scenarioGraph(ScenarioData $data, ?int $idEntity = null)
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

        return view('Grapher.Scenario.scenarioGraph', [
            'graph' => RelationService::listScenarioForGraph($data->idFrame, $data->frameRelation),
        ]);
    }
}

<?php

namespace App\Http\Controllers\LU;

use App\Data\ComboBox\QData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\LU;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class BrowseController extends Controller
{

    public static function listForTreeByFrame(int $idFrame)
    {
        $result = [];
        $lus = ViewLU::listByFrame($idFrame, AppService::getCurrentIdLanguage())->all();
        foreach ($lus as $lu) {
            $node = [];
            $node['id'] = 'l' . $lu->idLU;
            $node['type'] = 'lu';
            $node['name'] = [$lu->name, $lu->senseDescription];;
            $node['state'] = 'open';
            $node['iconCls'] = 'material-icons-outlined wt-tree-icon wt-icon-lu';
            $node['children'] = null;
            $result[] = $node;
        }
        return $result;
    }

    public static function listForTreeByName(string $name)
    {
        $result = [];
        $filter = (object)[
            'lu' => $name
        ];
        $lus = ViewLU::listByFilter($filter)->all();
        foreach ($lus as $i => $row) {
            $node = [];
            $node['id'] = 'l' . $row->idLU;
            $node['type'] = 'luFrame';
            $node['name'] = [$row->name, $row->senseDescription, $row->frameName];
            $node['state'] = 'closed';
            $node['iconCls'] = 'material-icons-outlined wt-tree-icon wt-icon-lu';
            $node['children'] = [];
            $result[] = $node;
        }
        return $result;
    }

    #[Get(path: '/lu/list/forEvent')]
    public function listForEvent(QData $data)
    {
        return LU::listForEvent($data->q ?? '');
    }

    #[Get(path: '/lu/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->lu) > 2) ? $data->lu : 'none';
        return ['results' => Criteria::byFilterLanguage("view_lu",["name","startswith",$name])->orderby("name")->all()];
    }
}

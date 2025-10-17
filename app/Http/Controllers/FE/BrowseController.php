<?php

namespace App\Http\Controllers\FE;

use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\FrameElement;
use App\Repositories\ViewFrameElement;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class BrowseController extends Controller
{
    public static function listForTreeByFrame(int $idFrame)
    {
        return Criteria::byFilterLanguage("view_frameelement", ['idFrame', "=", $idFrame])
            ->all();
    }

    public static function listForGridByFrame(int $idFrame)
    {
        return Criteria::byFilterLanguage("view_frameelement", ['idFrame', "=", $idFrame])
            ->get()
            ->groupBy('coreType')
            ->toArray();
    }

    public static function listForTreeByName(string $name)
    {
        $result = [];
        $filter = (object)[
            'fe' => $name
        ];
        $icon = config('webtool.fe.icon.grid');
        $fes = ViewFrameElement::listByFilter($filter)->all();
        foreach ($fes as $row) {
            $node = [];
            $node['id'] = 'e' . $row->idFrameElement;
            $node['type'] = 'feFrame';
            $node['name'] = [$row->name, $row->description, $row->frameName];
            $node['idColor'] = $row->idColor;
            $node['state'] = 'closed';
//            $node['iconCls'] = $icon[$row->coreType];
            $node['coreType'] = $row->coreType;
            $node['children'] = [];
            $result[] = $node;
        }
        return $result;
    }

}

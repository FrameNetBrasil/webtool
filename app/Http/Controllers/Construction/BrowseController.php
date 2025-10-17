<?php

namespace App\Http\Controllers\Construction;

use App\Data\Construction\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class BrowseController extends Controller
{
    #[Get(path: '/cxn')]
    public function browse()
    {
        $search = session('searchCxn') ?? SearchData::from();
        return view("Construction.browse", [
            'search' => $search
        ]);
    }

    #[Post(path: '/cxn/grid')]
    public function grid(SearchData $search)
    {
        debug($search);
        $result = [];
        $cxns = Criteria::table("view_construction")
            ->where("name", "startswith", $search->cxn)
            ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("cxIdLanguage", "=", $search->idLanguage)
            ->orderBy('name')->all();
        foreach ($cxns as $row) {
            $result[$row->idConstruction] = [
                'id' => 'c' . $row->idConstruction,
                'idConstruction' => $row->idConstruction,
                'type' => 'cxn',
                'name' => [$row->name, $row->description],
                'iconCls' => 'material-icons-outlined wt-icon wt-icon-cxn',
            ];
        }
        return view("Construction.grids", [
            'search' => $search,
            'cxns' => $result,
        ]);
    }

}

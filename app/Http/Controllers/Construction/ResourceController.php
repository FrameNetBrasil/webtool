<?php

namespace App\Http\Controllers\Construction;

use App\Data\Construction\CreateData;
use App\Data\Construction\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Construction;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/cxn')]
    public function index()
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

    #[Get(path: '/cxn/new')]
    public function new()
    {
        return view("Construction.new");
    }

    #[Post(path: '/cxn')]
    public function store(CreateData $data)
    {
        try {
            $idcxn = Criteria::function('cxn_create(?)', [$data->toJson()]);
            return $this->clientRedirect("/cxn/{$idcxn}");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/cxn/{idcxn}')]
    public function delete(string $idcxn)
    {
        try {
            debug($idcxn);
            Criteria::function('cxn_delete(?, ?)', [
                $idcxn,
                AppService::getCurrentIdUser()
            ]);
            return $this->clientRedirect("/cxn");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/cxn/{id}')]
    public function get(string $id)
    {
        return view("Construction.edit",[
            'cxn' => Construction::byId($id),
        ]);
    }

}

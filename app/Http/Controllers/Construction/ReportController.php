<?php

namespace App\Http\Controllers\Construction;

use App\Data\ComboBox\QData;
use App\Data\Construction\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Construction;
use App\Services\AppService;
use App\Services\ReportConstructionService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Post(path: '/report/cxn/grid')]
    public function grid(SearchData $search)
    {
        return view("Construction.Report.grid", [
            'search' => $search,
        ]);
    }

    #[Get(path: '/report/cxn/data')]
    public function data(SearchData $search)
    {
        debug($search);
        $languageIcon = view('components.icon.language')->render();
        $cxnIcon = view('components.icon.construction')->render();
        $showLanguage = false;
        $tree = [];
//        if (($search->cxn == '') && ($search->idLanguage == 0)) {
//            $languages = Construction::listRoots();
//            foreach ($languages as $language) {
//                $n = [];
//                $n['id'] = 'l' . $language->idLanguage;
//                $n['idLanguage'] = $language->idLanguage;
//                $n['type'] = 'language';
//                $n['text'] = $languageIcon . $language->description;
//                $n['state'] = ($language->n > 0) ? 'closed' : 'open';
//                $n['children'] = [];
//                $tree[] = $n;
//            }
//        } else {
            $cxns = Construction::listTree($search->cxn, $search->idLanguage);
            $showLanguage = ($search->idLanguage == 0);
            foreach ($cxns as $cxn) {
                $n = [];
                $n['id'] = $cxn->idConstruction;
                $n['idConstruction'] = $cxn->idConstruction;
                $n['type'] = 'construction';
                $n['text'] = $cxnIcon . $cxn->name . ($showLanguage ? ' [' . $cxn->language . ']' : '');
                $n['state'] = 'open';
                $n['children'] = [];
                $tree[] = $n;
            }
//        }
        return $tree;
    }

    #[Get(path: '/report/cxn/{idConstruction?}/{view?}')]
    public function report(int|string $idConstruction = '', string $view = '')
    {
        $search = session('searchCxn') ?? SearchData::from();
        if ($idConstruction == '') {
            return view("Construction.Report.main", [
                'search' => $search,
                'idConstruction' => null
            ]);
        } else {
            $data = ReportConstructionService::report($idConstruction);
            $data['search'] = $search;
            $data['idConstruction'] = $idConstruction;
            if ($view != '') {
                return view("Construction.Report.report", $data);
            } else {
                return view("Construction.Report.main", $data);
            }

        }
    }

    #[Get(path: '/construction/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';
        return ['results' => Criteria::byFilterLanguage("view_construction", ["name", "startswith", $name])->orderby("name")->all()];
    }

}

<?php

namespace App\Http\Controllers\C5;

use App\Data\C5\SearchData;
use App\Data\ComboBox\QData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Concept;
use App\Services\ReportC5Service;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Post(path: '/report/c5/grid')]
    public function grid(SearchData $search)
    {
        return view("C5.Report.grid", [
            'search' => $search,
        ]);
    }

    #[Get(path: '/report/c5/data')]
    public function data(SearchData $search)
    {
        $icons = [
            'inf' => view('components.icon.concept_inf')->render(),
            'sem' => view('components.icon.concept_sem')->render(),
            'str' => view('components.icon.concept_str')->render(),
            'cxn' => view('components.icon.concept_cxn')->render(),
            'def' => view('components.icon.concept_def')->render(),
            'frame' => view('components.icon.frame')->render(),
        ];
        $tree = [];
        if ($search->id == '') {
            if (($search->concept != '')) {
                $concepts = Concept::listTree($search->concept);
            } else {
                $types = Concept::listRoots();
                foreach ($types as $type) {
                    debug($type);
                    $icon = $icons[$type->type];
                    $n = [];
                    $n['id'] = 't' . $type->idTypeInstance;
                    $n['idTypeInstance'] = $type->idTypeInstance;
                    $n['type'] = 'type';
                    $n['text'] = $icon . $type->name;
                    $n['state'] = 'closed';
                    $n['children'] = [];
                    $tree[] = $n;
                }
                return $tree;
            }
        } else {
            if ($search->idTypeInstance != 0) {
                $concepts = Concept::listTypeChildren($search->idTypeInstance);
            } else {
                $concepts = Concept::listChildren($search->idConcept);
            }
        }
        foreach ($concepts as $concept) {
            $icon = $icons[$concept->type];
            $n = [];
            $n['id'] = 'c' . $concept->idEntity;
            $n['idConcept'] = $concept->idConcept;
            $n['type'] = 'concept';
            $n['text'] = $icon . $concept->name;
            $n['state'] = ($concept->n > 0) ? 'closed' : 'open';
            $n['children'] = [];
            $tree[] = $n;
        }
        return $tree;
    }

    #[Get(path: '/report/c5/content/{idConcept}/{lang?}')]
    public function reportContent(int|string $idConcept = '', string $lang = '')
    {
        $search = session('searchConcept') ?? SearchData::from();
        $data = ReportC5Service::report($idConcept, $lang);
        $data['search'] = $search;
        $data['idConcept'] = $idConcept;
        return view("C5.Report.report", $data);
    }

    #[Get(path: '/report/c5/{idConcept?}/{lang?}')]
    public function main(int|string $idConcept = '', string $lang = '')
    {
        $search = session('searchFrame') ?? SearchData::from();
        if ($idConcept == '') {
            return view("C5.Report.main", [
                'search' => $search,
                'idConcept' => null
            ]);
        } else {
            $concept = Concept::byId($idConcept);
            $search->concept = $concept->name;
            $data = ReportC5Service::report($idConcept, $lang);
            $data['search'] = $search;
            $data['idConcept'] = $idConcept;
            return view("C5.Report.main", $data);
        }
    }

    #[Get(path: '/concept/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';
        return ['results' => Criteria::byFilterLanguage("view_concept", ["name", "startswith", $name])->orderby("name")->all()];
    }


}

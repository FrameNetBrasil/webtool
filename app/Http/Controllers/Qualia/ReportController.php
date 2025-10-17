<?php

namespace App\Http\Controllers\Qualia;

use App\Data\Qualia\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Qualia;
use App\Services\ReportQualiaService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'web')]
class ReportController extends Controller
{

    #[Get(path: '/report/qualia')]
    public function main(int|string $idConcept = '', string $lang = '')
    {
        $search = session('searchQualia') ?? SearchData::from();
        $data = [];
        return view("Qualia.Report.main", [
            'search' => $search,
            'idQualia' => null,
            'data' => $data,
        ]);
    }

    #[Get(path: '/report/qualia/data')]
    public function data(SearchData $search)
    {
        $typeIcon = view('components.icon.type')->render();
        $qualiaIcon = view('components.icon.qualia')->render();
        $tree = [];
        if ($search->qualia != '') {
            $qualias = Qualia::listTree($search->qualia);
        } else {
            if ($search->id == '') {
                $types = Qualia::listTypes();
                foreach ($types as $row) {
                    $count = Criteria::table("qualia")
                        ->where("idTypeInstance", $row->idTypeInstance)
                        ->count();
                    $n = [];
                    $n['id'] = 't' . $row->idTypeInstance;
                    $n['idTypeInstance'] = $row->idTypeInstance;
                    $n['type'] = 'type';
                    $n['text'] = $typeIcon . $row->name;
                    $n['state'] = ($count > 0) ? 'closed' : 'open';
                    $n['children'] = [];
                    $tree[] = $n;
                }
                return $tree;
            }
            if ($search->idTypeInstance > 0) {
                $qualias = Qualia::listByType($search->idTypeInstance);
            } else if ($search->idQualia > 0) {
                $qualias = Qualia::listChildren($search->idQualia);
            }
        }
        foreach ($qualias as $qualia) {
            $frameName = '';
            if ($qualia->frameName != '') {
                $frameName = '<span class="color_frame"> ['. $qualia->frameName . '] </span>';
            }
            $n = [];
            $n['id'] = 't' . $qualia->idEntity;
            $n['idQualia'] = $qualia->idQualia;
            $n['type'] = 'qualia';
            $n['text'] = $qualiaIcon . $qualia->info . $frameName;
            $n['state'] = 'open';
            $n['children'] = [];
            $tree[] = $n;
        }
        return $tree;
    }

    #[Get(path: '/report/qualia/{idQualia?}/{lang?}')]
    public function report(int|string $idQualia = '', string $lang = '')
    {
        $search = session('searchQualia') ?? SearchData::from();
        $data = ReportQualiaService::report($idQualia, $lang);
        $data['search'] = $search;
        $data['idQualia'] = $idQualia;
        $data['data'] = $data;
        return view("Qualia.Report.report", $data);
    }

}

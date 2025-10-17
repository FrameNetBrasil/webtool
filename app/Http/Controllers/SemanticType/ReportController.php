<?php

namespace App\Http\Controllers\SemanticType;

use App\Data\ComboBox\QData;
use App\Data\SemanticType\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frame\BrowseController;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\ReportSTService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{

    #[Get(path: '/report/semanticType')]
    public function main(int|string $idConcept = '', string $lang = '')
    {
        $search = session('searchSemanticType') ?? SearchData::from();
        $data = [];
        return view("SemanticType.Report.main", [
            'search' => $search,
            'idSemanticType' => null,
            'data' => $data,
        ]);
    }

    #[Get(path: '/report/semanticType/data')]
    public function data(SearchData $search)
    {
        $domainIcon = view('components.icon.domain')->render();
        $stIcon = view('components.icon.semantictype')->render();
        $tree = [];
        if ($search->semanticType != '') {
            $semanticTypes = SemanticType::listTree($search->semanticType);
        } else {
            if ($search->id == '') {
                $domains = SemanticType::listDomains();
                foreach ($domains as $row) {
                    $count = Criteria::table("semantictype")
                        ->where("idDomain", $row->idDomain)
                        ->count();
                    $n = [];
                    $n['id'] = 'd' . $row->idDomain;
                    $n['idDomain'] = $row->idDomain;
                    $n['type'] = 'domain';
                    $n['text'] = $domainIcon . $row->name;
                    $n['state'] = ($count > 0) ? 'closed' : 'open';
                    $n['children'] = [];
                    $tree[] = $n;
                }
                return $tree;
            }
            if ($search->idDomain > 0) {
                $semanticTypes = SemanticType::listRootByDomain($search->idDomain);
            } else if ($search->idSemanticType > 0) {
                $semanticTypes = SemanticType::listChildren($search->idSemanticType);
            }
        }
        foreach ($semanticTypes as $semanticType) {
            $n = [];
            $n['id'] = 't' . $semanticType->idEntity;
            $n['idSemanticType'] = $semanticType->idSemanticType;
            $n['type'] = 'semanticType';
            $n['text'] = $stIcon . $semanticType->name;
            $n['state'] = ($semanticType->n > 0) ? 'closed' : 'open';
            $n['children'] = [];
            $tree[] = $n;
        }
        return $tree;
    }

    #[Get(path: '/report/semanticType/{idSemanticType?}/{lang?}')]
    public function report(int|string $idSemanticType = '', string $lang = '')
    {
        $search = session('searchSemanticType') ?? SearchData::from();
        $data = ReportSTService::report($idSemanticType, $lang);
        $data['search'] = $search;
        $data['idSemanticType'] = $idSemanticType;
        $data['data'] = $data;
        return view("SemanticType.Report.report", $data);
    }

}

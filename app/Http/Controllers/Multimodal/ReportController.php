<?php

namespace App\Http\Controllers\Multimodal;

use App\Data\ComboBox\QData;
use App\Data\Multimodal\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\ReportMultimodalService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Post(path: '/report/multimodal/grid')]
    public function grid(SearchData $search)
    {
        return view("Multimodal.Report.grid", [
            'search' => $search,
        ]);
    }

    #[Get(path: '/report/multimodal/data')]
    public function data(SearchData $search)
    {
        return ReportMultimodalService::browseCorpusDocumentBySearch($search);
    }

    #[Get(path: '/report/multimodal/{idDocument?}/{view?}')]
    public function report(int|string $idDocument = '', string $view = '')
    {
        $search = session('searchMM') ?? SearchData::from();
        if ($idDocument == '') {
            return view("Multimodal.Report.main", [
                'search' => $search,
                'idDocument' => null
            ]);
        } else {
            $data = ReportMultimodalService::report($idDocument);
            $data['search'] = $search;
            $data['idDocument'] = $idDocument;
            if ($view != '') {
                return view("Multimodal.Report.report", $data);
            } else {
                return view("Multimodal.Report.main", $data);
            }

        }
    }


}

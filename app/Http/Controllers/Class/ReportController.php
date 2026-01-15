<?php

namespace App\Http\Controllers\Class;

use App\Data\Class\SearchData;
use App\Data\ComboBox\QData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\Class\BrowseService;
use App\Services\Class\ReportService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Get(path: '/report/class')]
    public function browse(SearchData $search)
    {
        $classes = BrowseService::browseClassBySearch($search);

        return view('Class.Report.index', [
            'data' => $classes,
        ]);
    }

    #[Post(path: '/report/class/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseClassBySearch($search);

        return view('Class.Report.index', [
            'data' => $data,
        ])->fragment('search');
    }

    #[Get(path: '/report/class/{idFrame}/{lang?}')]
    public function report(int|string $idFrame = '', string $lang = '')
    {
        $data = ReportService::report($idFrame, $lang);
        $data['isHtmx'] = $this->isHtmx();

        if ($data['isHtmx']) {
            return view('Class.Report.reportPartial', $data);
        }

        return view('Class.Report.report', $data);
    }

    #[Get(path: '/class/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->class) > 2) ? $data->class : 'none';
        return ['results' => Criteria::byFilterLanguage('view_class', ['name', 'startswith', $name])->orderby('name')->all()];
    }
}

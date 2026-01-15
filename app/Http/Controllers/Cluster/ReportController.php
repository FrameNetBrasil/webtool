<?php

namespace App\Http\Controllers\Cluster;

use App\Data\ComboBox\QData;
use App\Data\Microframe\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use App\Services\Microframe\BrowseService;
use App\Services\Microframe\ReportService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Get(path: '/report/cluster/script/{file}')]
    public function scripts(string $file)
    {
        return response()
            ->view("Microframe.Report.{$file}")
            ->header('Content-type', 'text/javascript');
    }

    #[Get(path: '/report/cluster')]
    public function browse(SearchData $search)
    {
        $frames = BrowseService::browseMicroframeBySearch($search);

        return view('Cluster.Report.index', [
            'data' => $frames,
        ]);
    }

    #[Post(path: '/report/cluster/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseMicroframeBySearch($search);

        return view('Cluster.Report.index', [
            'data' => $data,
        ])->fragment('search');

    }

    #[Get(path: '/report/cluster/{idFrame}/{lang?}')]
    public function report(int|string $idFrame = '', string $lang = '')
    {
        $data = ReportService::report($idFrame, $lang);
        $data['isHtmx'] = $this->isHtmx();
        if ($data['isHtmx']) {
            return view('Cluster.Report.reportPartial', $data);
        }

        return view('Cluster.Report.report', $data);

    }

    #[Get(path: '/cluster/list/forSelect')]
    public function listForSelect(QData $data)
    {
        debug($data);
        $name = (strlen($data->microframe) > 2) ? $data->microframe : 'none';
        return ['results' => Criteria::byFilterLanguage('view_microframe', ['name', 'startswith', $name])->orderby('name')->all()];
    }

}

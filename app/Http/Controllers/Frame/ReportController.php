<?php

namespace App\Http\Controllers\Frame;

use App\Data\ComboBox\QData;
use App\Data\Frame\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use App\Services\Frame\BrowseService;
use App\Services\Frame\ReportService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Get(path: '/report/frame/script/{file}')]
    public function scripts(string $file)
    {
        return response()
            ->view("Frame.Report.{$file}")
            ->header('Content-type', 'text/javascript');
    }

    #[Get(path: '/report/frame')]
    public function index(SearchData $search)
    {
        $frames = BrowseService::browseFrameBySearch($search);

        return view('Frame.Report.index', [
            'frames' => $frames,
        ]);
    }

    #[Post(path: '/report/frame/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseFrameBySearch($search);

        return view('Frame.Report.index', [
            'frame' => $search->frame,
            'frames' => $data,
        ])->fragment('post');

    }

    #[Get(path: '/report/frame_lu/search')]
    public function indexFrameLU(SearchData $search)
    {
        return view('Frame.Report.indexFrameLU', [
            'frame' => '',
            'lu' => '',
            'frames' => [],
            'lus' => [],
        ]);
    }

    #[Post(path: '/report/frame_lu/search')]
    public function searchFrameLU(SearchData $search)
    {
        $search->lu = $search->frame;
        $frames = BrowseService::browseFrameBySearch($search);
        $lus = BrowseService::browseLUBySearch($search, true, contains:false);

        return view('Frame.Report.indexFrameLU', [
            'frame' => $search->frame,
            'lu' => $search->lu,
            'frames' => $frames,
            'lus' => $lus,
        ])->fragment("post");
    }

    #[Get(path: '/report/frame/{idFrame}/{lang?}')]
    public function report(int|string $idFrame = '', string $lang = '')
    {
        $data = ReportService::report($idFrame, $lang);
        $data['isHtmx'] = $this->isHtmx();
        if ($data['isHtmx']) {
            return view('Frame.Report.reportPartial', $data);
        }
        return view('Frame.Report.report', $data);

    }

    #[Get(path: '/frame/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->frame) > 2) ? $data->frame : 'none';
        return ['results' => Criteria::byFilterLanguage('view_frame', ['name', 'startswith', $name])->orderby('name')->all()];
    }

    #[Get(path: '/frame/listScenario/forSelect')]
    public function listScenarioForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';

        return ['results' => Criteria::table('view_relation as r')
            ->join('view_frame as f', 'r.idEntity1', '=', 'f.idEntity')
            ->join('semantictype as st', 'r.idEntity2', '=', 'st.idEntity')
            ->where('f.idLanguage', '=', AppService::getCurrentIdLanguage())
            ->where('st.entry', '=', 'sty_ft_scenario')
            ->where('f.name', 'startswith', $name)
            ->select('f.idFrame', 'f.idEntity', 'f.name')
            ->orderby('f.name')
            ->all()];
    }

    #[Get(path: '/report/frame/static/object/{idDocument}/{idImage}/{idFrame}')]
    public function getStaticObject(int $idDocument, int $idImage, int $idFrame)
    {
        $image = Criteria::table('image as i')
            ->select('i.idImage', 'i.name', 'i.width', 'i.height', 'i.currentURL')
            ->where('i.idImage', $idImage)
            ->first();
        $fesList = Criteria::table('view_annotation_static as a')
            ->join("view_annotation_static_fe as fe", "a.idStaticObject", "=", "fe.idStaticObject")
            ->select('a.idStaticObject', 'fe.name as fe', 'fe.bgColor as color')
            ->where('a.idDocument', $idDocument)
            ->where('a.idImage', $idImage)
            ->where('a.idLanguage', AppService::getCurrentIdLanguage())
            ->where('fe.idLanguage', AppService::getCurrentIdLanguage())
            ->where('fe.idFrame', $idFrame)
            ->all();
        $fes = collect($fesList)->groupBy('idStaticObject')->toArray();
        $idStaticObject = array_keys($fes);
        $bboxes = Criteria::table('view_staticobject_boundingbox as bb')
            ->distinct()
            ->select('bb.idStaticObject', 'bb.x', 'bb.y', 'bb.width', 'bb.height')
            ->whereIn('bb.idStaticObject', $idStaticObject)
            ->all();

        return view('Frame.Report.partials.image', [
            'image' => $image,
            'fes' => $fes,
            'bboxes' => $bboxes,
        ]);
    }

}

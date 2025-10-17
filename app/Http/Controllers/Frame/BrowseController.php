<?php

namespace App\Http\Controllers\Frame;

use App\Data\ComboBox\QData;
use App\Data\Frame\CxnData;
use App\Data\Frame\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\Frame\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Illuminate\Database\Query\JoinClause;

#[Middleware("master")]
class BrowseController extends Controller
{
    #[Get(path: '/frame')]
    public function browse(SearchData $search)
    {
        $frames = BrowseService::browseFrameBySearch($search);

        return view('Frame.browse', [
            'data' => $frames,
        ]);
    }

    #[Post(path: '/frame/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseFrameBySearch($search);

        return view('Frame.browse', [
            'data' => $data,
        ])->fragment('search');

    }
//    #[Get(path: '/frame')]
//    public function browse()
//    {
//        $search = session('searchFrame') ?? SearchData::from();
//        return view("Frame.browse", [
//            'search' => $search
//        ]);
//    }
//
//    #[Post(path: '/frame/grid')]
//    public function grid(SearchData $search)
//    {
//        debug($search);
//        $display = 'frameTableContainer';
//        if ($search->lu != '') {
//            $display = 'luTableContainer';
//            $lus = self::listLUSearch($search->lu);
//            return view("Frame.grids", [
//                'search' => $search,
//                'display' => $display,
//                'currentFrame' => $search->lu . '*',
//                'lus' => $lus,
//            ]);
//        } else {
//            $groups = self::listGroup($search->byGroup);
//            if ($search->frame == '') {
//                if ($search->byGroup == 'domain') {
//                    $search->idFramalDomain ??= array_key_first($groups);
//                    $currentGroup = $groups[$search->idFramalDomain]['name'];
//                    $group = 'Domains';
//                    $display = 'domainTableContainer';
//                }
//                if ($search->byGroup == 'type') {
//                    $search->idFramalType ??= array_key_first($groups);
//                    $currentGroup = $groups[$search->idFramalType]['name'];
//                    $group = 'Types';
//                    $display = 'domainTableContainer';
//                }
//                if ($search->byGroup == 'scenario') {
//                    $search->idFrameScenario ??= array_key_first($groups);
//                    $currentGroup = $groups[$search->idFrameScenario]['name'];
//                    $group = 'Scenarios';
//                    $display = 'domainTableContainer';
//                }
//            }
//            $frames = self::listFrame($search);
//            return view("Frame.grids", [
//                'search' => $search,
//                'display' => $display,
//                'currentGroup' => $currentGroup ?? '',
//                'currentFrame' => $frame?->name ?? '',
//                'groupName' => $group ?? '',
//                'groups' => $groups ?? [],
//                'frames' => $frames,
//            ]);
//        }
//    }
//
//    public static function listGroup(string $group)
//    {
//        $result = [];
//        if ($group == 'scenario') {
//            $scenarios = Criteria::table("view_relation as r")
//                ->join("view_frame as f","r.idEntity1","=","f.idEntity")
//                ->join("semantictype as st","r.idEntity2","=","st.idEntity")
//                ->where("f.idLanguage","=", AppService::getCurrentIdLanguage())
//                ->where("st.entry","=","sty_ft_scenario")
//                ->select("f.idFrame","f.idEntity","f.name")
//                ->orderby("f.name")
//                ->all();
//            foreach ($scenarios as $row) {
//                $result[$row->idFrame] = [
//                    'id' => $row->idFrame,
//                    'type' => 'scenario',
//                    'name' => $row->name,
//                    'iconCls' => 'material-icons-outlined wt-tree-icon wt-icon-domain'
//                ];
//            }
//        } else {
//            if ($group == 'domain') {
//                $groups = SemanticType::listFrameDomain();
//            } else {
//                $groups = SemanticType::listFrameType();
//            }
//            foreach ($groups as $row) {
//                $result[$row->idSemanticType] = [
//                    'id' => $row->idSemanticType,
//                    'idDomain' => $row->idSemanticType,
//                    'type' => $group,
//                    'name' => $row->name,
//                    'iconCls' => 'material-icons-outlined wt-tree-icon wt-icon-domain'
//                ];
//            }
//        }
//        return $result;
//    }
//
//    public static function listFrame(SearchData $search)
//    {
//        $result = [];
//        $subQuery = Criteria::table("view_frame_classification")
//            ->selectRaw("idFrame, group_concat(name) as domain")
//            ->where("relationType","rel_framal_domain")
//            ->where("idLanguage", AppService::getCurrentIdLanguage())
//            ->groupBy('idFrame');
//        if (!is_null($search->idFramalDomain)) {
//            $frames = Criteria::table("view_frame as f")
//                ->join("view_frame_classification as c", "f.idFrame", "=", "c.idFrame")
//                ->where("f.idLanguage", AppService::getCurrentIdLanguage())
//                ->where("c.idSemanticType", $search->idFramalDomain)
//                ->select("f.idFrame", "f.name", "f.description")
//                ->orderby("f.name")->all();
//        } else if (!is_null($search->idFramalType)) {
//            $frames = Criteria::table("view_frame as f")
//                ->join("view_frame_classification as c", "f.idFrame", "=", "c.idFrame")
//                ->where("f.idLanguage", AppService::getCurrentIdLanguage())
//                ->where("c.idSemanticType", $search->idFramalType)
//                ->select("f.idFrame", "f.name", "f.description")
//                ->orderby("f.name")->all();
//        } else if (!is_null($search->idFrameScenario)) {
//            $frames = Frame::listScenarioFrames($search->idFrameScenario);
//        } else {
//            $frames = Criteria::table("view_frame as f")
//                ->where('f.name', "startswith", $search->frame)
//                ->where("f.idLanguage", AppService::getCurrentIdLanguage())
//                ->joinSub($subQuery, 'domains', function (JoinClause $join) {
//                    $join->on('f.idFrame', '=', 'domains.idFrame');
//                })
//                ->orderBy('name')->all();
//        }
//
//        //$frames = ViewFrame::listByFilter($search)->all();
//        foreach ($frames as $row) {
//            $result[$row->idFrame] = [
//                'id' => 'f' . $row->idFrame,
//                'idFrame' => $row->idFrame,
//                'type' => 'frame',
//                'name' => [$row->name, $row->description],
//                'iconCls' => 'material-icons-outlined wt-icon wt-icon-frame',
//                'domain' => $row->domain,
//            ];
//        }
//        return $result;
//    }
//
//    public static function listFE(int $idFrame)
//    {
////        $icon = config('webtool.fe.icon');
////        $coreness = config('webtool.fe.coreness');
////        $fes = FrameElement::listByFrame($idFrame)->getResult();
////        $orderedFe = [];
////        foreach ($icon as $i => $j) {
////            foreach ($fes as $fe) {
////                if ($fe->coreType == $i) {
////                    $orderedFe[] = $fe;
////                }
////            }
////        }
//        $fes = Criteria::byFilterLanguage("view_frameelement", ['idFrame', "=", $idFrame])
//            ->orderBy('name')->all();
//        $result = [];
//        foreach ($fes as $fe) {
//            $result[$fe->idFrameElement] = [
//                'id' => 'e' . $fe->idFrameElement,
//                'idFrameElement' => $fe->idFrameElement,
//                'type' => 'fe',
//                'name' => [$fe->name, $fe->description],
//                'idColor' => $fe->idColor,
////                'iconCls' => $icon[$fe->coreType],
////                'coreness' => $coreness[$fe->coreType],
//            ];
//        }
//        return $result;
//    }
//
//    public static function listLU(int $idFrame)
//    {
//        $result = [];
////        $lus = ViewLU::listByFrame($idFrame, AppService::getCurrentIdLanguage())->all();
//        $lus = Criteria::byFilter("view_lu", ['idFrame', "=", $idFrame])
//            ->orderBy('name')->all();
//        foreach ($lus as $lu) {
//            $result[$lu->idLU] = [
//                'id' => 'l' . $lu->idLU,
//                'idLU' => $lu->idLU,
//                'type' => 'lu',
//                'name' => [$lu->name, $lu->senseDescription],
////                'iconCls' => 'material-icons-outlined wt-tree-icon wt-icon-lu',
//            ];
//        }
//        return $result;
//    }
//
//    public static function listLUSearch(string $lu)
//    {
//        $result = [];
//        $lus = Criteria::byFilterLanguage("view_lu", ['name', "startswith", $lu], 'idLanguage')
//            ->orderBy('name')->all();
////
////        $lus = ViewLU::listByFilter((object)[
////            'lu' => $lu,
////            'idLanguage' => AppService::getCurrentIdLanguage()
////        ])->all();
//        foreach ($lus as $lu) {
////            debug($lu);
//            $result[$lu->idLU] = [
//                'id' => 'l' . $lu->idLU,
//                'idLU' => $lu->idLU,
//                'type' => 'lu',
//                'name' => [$lu->name, $lu->senseDescription],
//                'frameName' => $lu->frameName,
////                'iconCls' => 'material-icons-outlined wt-icon wt-icon-lu',
//            ];
//        }
//        return $result;
//    }
//

}

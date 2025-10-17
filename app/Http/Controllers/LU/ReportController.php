<?php

namespace App\Http\Controllers\LU;

use App\Data\LU\SearchData;
use App\Data\LU\ReportData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\FrameElement;
use App\Repositories\LU;
use App\Repositories\User;
use App\Services\AppService;
use App\Services\Frame\BrowseService;
use App\Services\ReportLUService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ReportController extends Controller
{
    #[Get(path: '/report/lu/script/{file}')]
    public function scripts(string $file)
    {
        return response()
            ->view("LU.Report.{$file}")
            ->header('Content-type', 'text/javascript');
    }

    #[Post(path: '/report/lu/search')]
    public function search(SearchData $search)
    {
        $data = BrowseService::browseLUBySearch($search);

        return view('LU.Report.main', [
            'data' => $data,
        ])->fragment('search');

    }

//    #[Post(path: '/report/lu/grid')]
//    public function grid(SearchData $search)
//    {
//        debug($search);
//        $lus = [];
//        if ($search->lu != '') {
//            $lus = Criteria::byFilterLanguage('view_lu',
//                ['name', 'startswith', $search->lu])
//                ->select('idLU', 'name', 'frameName', 'senseDescription')
//                ->orderBy('name')
//                ->all();
//        }
//
//        return view('LU.Report.main', [
//            'search' => $search,
//            'lus' => $lus,
//        ])->fragment('search');
//    }

//    #[Get(path: '/report/lu/data')]
//    public function data(SearchData $search)
//    {
//        $lus = [];
//        if ($search->lu != '') {
//            $lus = Criteria::byFilterLanguage('view_lu',
//                ['name', 'startswith', $search->lu])
//                ->select('idLU', 'name', 'frameName')
//                ->orderBy('name')
//                ->all();
//        }
//
//        return $lus;
//    }

    //    #[Get(path: '/report/lu/{idLU?}')]
    //    public function reportContent(int|string $idLU)
    //    {
    //        debug("====");
    //        $idUser = AppService::getCurrentIdUser();
    //        $user = User::byId($idUser);
    //        $isMaster = User::isManager($user) || User::isMemberOf($user, 'MASTER');
    //        $lu = LU::byId($idLU);
    //        $data = [
    //            'lu' => $lu,
    //            'language' => Criteria::byId('language', 'idLanguage', $lu->idLanguage),
    //            'isMaster' => $isMaster,
    //        ];
    //        if (! is_null($lu->incorporatedFE)) {
    //            $data['incorporatedFE'] = FrameElement::byId($lu->incorporatedFE);
    //        }
    //
    //        return view('LU.Report.report', $data);
    //    }

    #[Get(path: '/report/lu/{idLU}/textual')]
    public function reportTextual(int|string $idLU)
    {
        $lu = LU::byId($idLU);
        $data = ReportLUService::FERealizations($idLU);
        $data['lu'] = $lu;
        $data['language'] = Criteria::byId('language', 'idLanguage', $lu->idLanguage);

        return view('LU.Report.partials.textual', $data);
    }

    #[Get(path: '/report/lu/{idLU}/static')]
    public function reportStatic(int|string $idLU)
    {
        $lu = LU::byId($idLU);
        $objects = Criteria::table('view_annotation_static as a')
            ->join('view_document as d', 'a.idDocument', '=', 'd.idDocument')
            ->distinct()
            ->select('d.name as documentName', 'd.idDocument')
            ->where('a.idLU', $idLU)
            ->where('a.idLanguage', AppService::getCurrentIdLanguage())
            ->where('d.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('d.name')
            ->orderBy('a.idStaticObject')
            ->all();
        $data = [
            'lu' => $lu,
            'language' => Criteria::byId('language', 'idLanguage', $lu->idLanguage),
            'objects' => $objects,
        ];

        return view('LU.Report.partials.static', $data);
    }

    #[Get(path: '/report/lu/static/object/{idDocument}/{idLU}')]
    public function reportStaticObject(int|string $idDocument, int|string $idLU)
    {
        $image = Criteria::table('image as i')
            ->join('document_image as di', 'di.idImage', '=', 'i.idImage')
            ->select('i.idImage', 'i.name', 'i.width', 'i.height', 'i.currentURL')
            ->where('di.idDocument', $idDocument)
            ->first();
        $bboxes = Criteria::table('view_annotation as a')
            ->join('lu', 'a.idEntity', '=', 'lu.idEntity')
            ->join('staticobject as so', 'a.idStaticObject', '=', 'so.idStaticObject')
            ->join('staticobject_boundingbox as sbb', 'so.idStaticObject', '=', 'sbb.idStaticObject')
            ->join('boundingbox as bb', 'sbb.idBoundingBox', '=', 'bb.idBoundingBox')
            ->select('bb.x', 'bb.y', 'bb.width', 'bb.height')
            ->where('a.idDocument', $idDocument)
            ->where('lu.idLU', $idLU)
            ->all();

        return view('LU.Report.image', [
            'image' => $image,
            'bboxes' => $bboxes,
        ]);
    }

    #[Get(path: '/report/lu/{idLU}/dynamic')]
    public function reportDynamic(int|string $idLU)
    {
        $lu = LU::byId($idLU);
        $documents = Criteria::table('view_annotation_dynamic as a')
            ->join('view_document as d', 'a.idDocument', '=', 'd.idDocument')
            ->distinct()
            ->select('d.name as documentName', 'd.idDocument')
            ->where('a.idLU', $idLU)
            ->where('a.idLanguage', AppService::getCurrentIdLanguage())
            ->where('d.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('d.name')
            ->all();
        $data = [
            'lu' => $lu,
            'language' => Criteria::byId('language', 'idLanguage', $lu->idLanguage),
            'documents' => $documents,
        ];

        return view('LU.Report.partials.dynamic', $data);
    }

    #[Get(path: '/report/lu/{idLU}/dynamic/objects/{idDocument}')]
    public function reportDynamicObjects(int|string $idLU, int|string $idDocument)
    {
        $lu = LU::byId($idLU);
        $objects = Criteria::table('view_annotation_dynamic as a')
            ->join('lu', 'a.idLU', '=', 'lu.idLU')
            ->where('a.idLanguage', 'LEFT', AppService::getCurrentIdLanguage())
            ->distinct()
            ->select('a.idDynamicObject')
            ->where('lu.idLU', $idLU)
            ->where('a.idDocument', $idDocument)
            ->orderBy('a.idDynamicObject')
            ->all();
        $data = [
            'lu' => $lu,
            'language' => Criteria::byId('language', 'idLanguage', $lu->idLanguage),
            'objects' => $objects,
        ];

        return view('LU.Report.partials.objects', $data);
    }

    #[Get(path: '/report/lu/dynamic/object/{idDynamicObject}')]
    public function reportDynamicObject(int|string $idDynamicObject)
    {
        $video = Criteria::table('view_annotation_dynamic as a')
            ->join('view_document_video as dv', 'a.idDocument', '=', 'dv.idDocument')
            ->join('video as v', 'dv.idVideo', '=', 'v.idVideo')
            ->select('v.idVideo', 'v.sha1Name', 'v.currentURL')
            ->where('a.idDynamicObject', $idDynamicObject)
            ->where('a.idLanguage', 'LEFT', AppService::getCurrentIdLanguage())
            ->first();
        $object = Criteria::byId('dynamicobject', 'idDynamicObject', $idDynamicObject);
        $bbox = Criteria::table('view_dynamicobject_boundingbox as bb')
            ->select('bb.x', 'bb.y', 'bb.width', 'bb.height')
            ->where('bb.idDynamicObject', $idDynamicObject)
            ->first();

        return view('LU.Report.video', [
            'video' => $video,
            'bbox' => $bbox,
            'object' => $object,
        ]);
    }

    #[Get(path: '/report/lu/{idLU?}')]
    public function report(int|string $idLU = '')
    {
        $search = session('searchLU') ?? SearchData::from();
        if (($idLU == 'list') || ($idLU == '')) {
            return view('LU.Report.main', [
                'search' => $search,
                'data' => [],
            ]);
        } else {
            $idUser = AppService::getCurrentIdUser();
            if ($idUser) {
                $user = User::byId($idUser);
                $isMaster = User::isManager($user) || User::isMemberOf($user, 'MASTER');
            } else {
                $isMaster = false;
            }
            $lu = LU::byId($idLU);
            $data = [
                'lu' => $lu,
                'language' => Criteria::byId('language', 'idLanguage', $lu->idLanguage),
                'isMaster' => $isMaster,
            ];
            if (! is_null($lu->incorporatedFE)) {
                $data['incorporatedFE'] = FrameElement::byId($lu->incorporatedFE);
            }

            $data['isHtmx'] = $this->isHtmx();
            if ($data['isHtmx']) {
                return view('LU.Report.reportPartial', $data);
            }
            return view('LU.Report.report', $data);
        }
    }

    public static function listLUSearch(string $lu)
    {
        $result = [];
        $lus = Criteria::byFilter('view_lu', [
            ['name', 'startswith', $lu],
            ['idLanguage', '=', AppService::getCurrentIdLanguage()],
        ])->all();
        foreach ($lus as $lu) {
            $result[$lu->idLU] = [
                'id' => 'l'.$lu->idLU,
                'idLU' => $lu->idLU,
                'type' => 'lu',
                'name' => [$lu->name, $lu->senseDescription],
                'frameName' => $lu->frameName,
            ];
        }

        return $result;
    }

    #[Post(path: '/report/lu/sentences')]
    public function sentences(ReportData $reportData)
    {
        return ReportLUService::getSentences($reportData);
    }

//    #[Post(path: '/report/lu/grid')]
//    public function grid(SearchData $search)
//    {
//        return view("LU.Report.grid", [
//            'search' => $search,
//        ]);
//    }
//
//    #[Get(path: '/report/lu/data')]
//    public function data(SearchData $search)
//    {
//        $lus = [];
//        if ($search->lu != '') {
//            $lus = Criteria::byFilterLanguage("view_lu",
//                ['name', "startswith", $search->lu])
//                ->select("idLU","name","frameName")
//                ->orderBy('name')
//                ->all();
//        }
//        return $lus;
//    }
//
//    #[Get(path: '/report/lu/content/{idLU?}')]
//    public function reportContent(int|string $idLU)
//    {
//        $idUser = AppService::getCurrentIdUser();
//        $user = User::byId($idUser);
//        $isMaster = User::isManager($user) || User::isMemberOf($user, 'MASTER');
//        $lu = LU::byId($idLU);
//        $data = [
//            'lu' => $lu,
//            'language' => Criteria::byId("language","idLanguage", $lu->idLanguage),
//            'isMaster' => $isMaster,
//        ];
//        if (!is_null($lu->incorporatedFE)) {
//            $data['incorporatedFE'] = FrameElement::byId($lu->incorporatedFE);
//        }
//        return view("LU.Report.report", $data);
//    }
//
//    #[Get(path: '/report/lu/{idLU}/textual')]
//    public function reportTextual(int|string $idLU)
//    {
//        $lu = LU::byId($idLU);
//        $data = ReportLUService::FERealizations($idLU);
//        $data['lu'] = $lu;
//        $data['language'] = Criteria::byId("language","idLanguage", $lu->idLanguage);
//        return view("LU.Report.textual", $data);
//    }
//
//    #[Get(path: '/report/lu/{idLU}/static')]
//    public function reportStatic(int|string $idLU)
//    {
//        $lu = LU::byId($idLU);
//        $objects = Criteria::table("view_annotation_static as a")
//            ->join("view_document as d", "a.idDocument", "=", "d.idDocument")
//            ->distinct()
//            ->select("d.name as documentName","d.idDocument")
//            ->where("a.idLU", $idLU)
//            ->where("a.idLanguage", AppService::getCurrentIdLanguage())
//            ->where("d.idLanguage", AppService::getCurrentIdLanguage())
//            ->orderBy("d.name")
//            ->orderBy("a.idStaticObject")
//            ->all();
//        $data = [
//            'lu' => $lu,
//            'language' => Criteria::byId("language","idLanguage", $lu->idLanguage),
//            'objects' => $objects
//        ];
//        return view("LU.Report.static", $data);
//    }
//
//    #[Get(path: '/report/lu/static/object/{idDocument}/{idLU}')]
//    public function reportStaticObject(int|string $idDocument,int|string $idLU)
//    {
//        $image = Criteria::table("image as i")
//            ->join("view_document_image as di", "di.idImage", "=", "i.idImage")
//            ->select("i.idImage","i.name","i.width","i.height","i.currentURL")
//            ->where("di.idDocument", $idDocument)
//            ->first();
//        $bboxes = Criteria::table("view_annotation as a")
//            ->join("lu", "a.idEntity", "=", "lu.idEntity")
//            ->join("staticobject as so", "a.idAnnotationObject", "=", "so.idAnnotationObject")
//            ->join("view_staticobject_boundingbox as bb","so.idStaticObject", "=", "bb.idStaticObject")
//            ->select("bb.x","bb.y","bb.width","bb.height")
//            ->where("a.idDocument", $idDocument)
//            ->where("lu.idLU", $idLU)
//            ->all();
//        return view("LU.Report.image", [
//            'image' => $image,
//            'bboxes' => $bboxes,
//        ]);
//    }
//
//    #[Get(path: '/report/lu/{idLU}/dynamic')]
//    public function reportDynamic(int|string $idLU)
//    {
//        $lu = LU::byId($idLU);
//        $documents = Criteria::table("view_annotation_dynamic as a")
//            ->join("view_document as d", "a.idDocument", "=", "d.idDocument")
//            ->distinct()
//            ->select("d.name as documentName","d.idDocument")
//            ->where("a.idLU", $idLU)
//            ->where("a.idLanguage", AppService::getCurrentIdLanguage())
//            ->where("d.idLanguage", AppService::getCurrentIdLanguage())
//            ->orderBy("d.name")
//            ->all();
//        $data = [
//            'lu' => $lu,
//            'language' => Criteria::byId("language","idLanguage", $lu->idLanguage),
//            'documents' => $documents
//        ];
//        return view("LU.Report.dynamic", $data);
//    }
//
//    #[Get(path: '/report/lu/{idLU}/dynamic/objects/{idDocument}')]
//    public function reportDynamicObjects(int|string $idLU, int|string $idDocument)
//    {
//        $lu = LU::byId($idLU);
//        $objects = Criteria::table("view_annotation as a")
//            ->join("lu", "a.idEntity", "=", "lu.idEntity")
//            ->join("dynamicobject as do", "a.idAnnotationObject", "=", "do.idAnnotationObject")
//            ->distinct()
//            ->select("do.idDynamicObject")
//            ->where("lu.idLU", $idLU)
//            ->where("a.idDocument", $idDocument)
//            ->orderBy("do.idDynamicObject")
//            ->all();
//        $data = [
//            'lu' => $lu,
//            'language' => Criteria::byId("language","idLanguage", $lu->idLanguage),
//            'objects' => $objects
//        ];
//        return view("LU.Report.objects", $data);
//    }
//
//    #[Get(path: '/report/lu/dynamic/object/{idDynamicObject}')]
//    public function reportDynamicObject(int|string $idDynamicObject)
//    {
//        $video = Criteria::table("view_annotation_dynamic as a")
//            ->join("view_document_video as dv", "a.idDocument", "=", "dv.idDocument")
//            ->join("video as v", "dv.idVideo", "=", "v.idVideo")
//            ->select("v.idVideo","v.sha1Name","v.currentURL")
//            ->where("a.idDynamicObject", $idDynamicObject)
//            ->where("a.idLanguage", "LEFT", AppService::getCurrentIdLanguage())
//            ->first();
//        $object = Criteria::byId("dynamicobject","idDynamicObject", $idDynamicObject);
//        $bbox = Criteria::table("view_dynamicobject_boundingbox as bb")
//            ->select("bb.x","bb.y","bb.width","bb.height")
//            ->where("bb.idDynamicObject", $idDynamicObject)
//            ->first();
//        return view("LU.Report.video", [
//            'video' => $video,
//            'bbox' => $bbox,
//            'object' => $object,
//        ]);
//    }
//
//    #[Get(path: '/report/lu/{idLU?}')]
//    public function report(int|string $idLU = '')
//    {
//        $search = session('searchLU') ?? SearchData::from();
//        if (($idLU == 'list') || ($idLU == '')) {
//            return view("LU.Report.main", [
//                'search' => $search
//            ]);
//        } else {
//            $lu = LU::byId($idLU);
//            $search->lu = $lu->name;
//            return view("LU.Report.main", [
//                'search' => $search,
//                'idLU' => $idLU
//            ]);
//        }
//    }
//
//
//
//    public static function listLUSearch(string $lu)
//    {
//        $result = [];
//        $lus = Criteria::byFilter("view_lu",[
//            ['name',"startswith",$lu],
//            ['idLanguage',"=",AppService::getCurrentIdLanguage()]
//        ])->all();
//        foreach ($lus as $lu) {
//            $result[$lu->idLU] = [
//                'id' => 'l' . $lu->idLU,
//                'idLU' => $lu->idLU,
//                'type' => 'lu',
//                'name' => [$lu->name, $lu->senseDescription],
//                'frameName' => $lu->frameName,
//            ];
//        }
//        return $result;
//    }
//
//    #[Post(path: '/report/lu/sentences')]
//    public function sentences(ReportData $reportData)
//    {
//        return ReportLUService::getSentences($reportData);
//    }

}

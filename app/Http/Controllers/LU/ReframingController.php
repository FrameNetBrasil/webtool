<?php

namespace App\Http\Controllers\LU;

use App\Data\LU\ReframingData;
use App\Data\LUCandidate\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Repositories\LU;
use App\Repositories\User;
use App\Services\AppService;
use App\Services\Frame\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class ReframingController extends Controller
{
    #[Get(path: '/reframing')]
    public function report(int|string $idLU = '')
    {
        $search = session('searchLU') ?? SearchData::from();
        if (($idLU == 'list') || ($idLU == '')) {
            return view("LU.Reframing.browse", [
                'data' => [],
                'search' => $search
            ]);
        } else {
            $lu = LU::byId($idLU);
            $search->lu = $lu->name;
            return view("LU.Reframing.main", [
                'search' => $search,
                'idLU' => $idLU
            ]);
        }
    }

    #[Post(path: '/reframing/search')]
    public function search(\App\Data\LU\SearchData $search)
    {
        $data = BrowseService::browseLUBySearch($search);

        return view('LU.Reframing.browse', [
            'data' => $data,
        ])->fragment('search');

    }

    #[Get(path: '/reframing/lu/{idLU}')]
    public function reframingLU(string $idLU)
    {
        $idUser = AppService::getCurrentIdUser();
        $user = User::byId($idUser);
        $isMaster = User::isManager($user) || User::isMemberOf($user, 'MASTER');
        $lu = LU::byId($idLU);
        $data = [
            'lu' => $lu,
            'language' => Criteria::byId("language", "idLanguage", $lu->idLanguage),
            'isMaster' => $isMaster,
        ];
        return view("LU.Reframing.reframing", $data);
    }

    #[Get(path: '/reframing/edit/{idLU}/{idNewFrame}')]
    public function reframingEdit(string $idLU, string $idNewFrame)
    {
        $lu = LU::byId($idLU);
        $alreadyExists = false;
        $exists = Criteria::table("lu")
            ->where("idLemma", $lu->idLemma)
            ->where("idFrame", $idNewFrame)
            ->first();
        if (!is_null($exists)) {
            $alreadyExists = true;
        }
        $data = [
            'lu' => $lu,
            'idNewFrame' => $idNewFrame,
            'alreadyExists' => $alreadyExists,
            'language' => Criteria::byId("language", "idLanguage", $lu->idLanguage),
        ];
        return view("LU.Reframing.edit", $data);
    }

    #[Get(path: '/reframing/fes/{idLU}/{idNewFrame}')]
    public function reframingFEs(string $idLU, string $idNewFrame)
    {
        $newFrame = Frame::byId($idNewFrame);
        $lu = LU::byId($idLU);
        $as = Criteria::table("view_annotationset as a")
            ->where("a.idLU", $idLU)
            ->select("a.idAnnotationSet")
            ->all();
        $idAS = collect($as)->pluck("idAnnotationSet")->toArray();
        $afe = Criteria::table("view_annotation_text_fe as afe")
            ->whereIN("afe.idAnnotationSet", $idAS)
            ->distinct()
            ->select("afe.idFrameElement")
            ->all();
        $idFE = collect($afe)->pluck("idFrameElement")->toArray();
        $fes = Criteria::table("view_frameelement as fe")
            ->where("fe.idLanguage", AppService::getCurrentIdLanguage())
            ->whereIN("fe.idFrameElement", $idFE)
            ->distinct()
            ->select("fe.idFrameElement", "fe.name", "fe.coreType", "fe.idColor", "fe.idEntity")
            ->orderBy("fe.name")
            ->all();
        $data = [
            'lu' => $lu,
            'idNewFrame' => $idNewFrame,
            'newFrame' => $newFrame,
            'fes' => $fes,
            'countAS' => count($as),
            'language' => Criteria::byId("language", "idLanguage", $lu->idLanguage),
        ];
//        debug($data);
        return view("LU.Reframing.fes", $data);
    }

    #[Put(path: '/reframing')]
    public function update(ReframingData $data)
    {
        try {
            LU::reframing($data);
            return $this->renderNotify("success", "Reframing done.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }

    }

}

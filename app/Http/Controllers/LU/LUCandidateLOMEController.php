<?php

namespace App\Http\Controllers\LU;

use App\Data\LUCandidate\CreateData;
use App\Data\LUCandidate\SearchData;
use App\Data\LUCandidate\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Lemma;
use App\Repositories\Lexicon;
use App\Repositories\LUCandidate;
use App\Repositories\User;
use App\Services\AppService;
use App\Services\MessageService;
use Carbon\Carbon;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class LUCandidateLOMEController extends Controller
{

    private function getData(SearchData $search): array
    {
        $luIcon = view('components.icon.lu')->render();
        $lus = Criteria::table("view_lucandidate")
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->where("name", "startswith", $search->lu)
            ->where("email", "startswith", $search->email)
            ->where("email", "=", "lome@frame.net.br")
            ->select('idLU', 'name', 'createdAt', 'frameName', 'origin', 'email')
//            ->selectRaw("IFNULL(frameName, frameCandidate) as frameName")
            ->orderBy($search->sort, $search->order)->all();
        $data = array_map(fn($item) => [
            'id' => $item->idLU,
            'name' => $luIcon . $item->name,
            'frameName' => $item->frameName,
            'createdAt' => $item->createdAt ? Carbon::parse($item->createdAt)->format("d/m/Y") : '-',
            'createdBy' => $item->email,
            'state' => 'open',
            'origin' => $item->origin,
            'type' => 'lu'
        ], $lus);
        return $data;
    }

    #[Get(path: '/luCandidateLome')]
    public function resource(SearchData $search)
    {
        $data = $this->getData($search);
        $creators = Criteria::table("view_lucandidate")
            ->distinct()
            ->select("email")
            ->orderby("email")
            ->all();
        return view("LUCandidateLOME.browse", [
            "data" => $data,
            "creators" => $creators,
        ]);
    }

    #[Post(path: '/luCandidateLome/search')]
    public function tree(SearchData $search)
    {
        debug($search);
        $data = $this->getData($search);
        return view('LUCandidateLOME.browse', [
            'data' => $data,
            "creators" => [],
        ])->fragment('search');
    }

    #[Get(path: '/luCandidateLome/new')]
    public function new()
    {
        return view("LUCandidate.formNew");
    }

    #[Post(path: '/luCandidateLome')]
    public function newLU(CreateData $data)
    {
        debug($data);
        try {
            if ((is_null($data->idLemma) || ($data->idLemma == 0))) {
                throw new \Exception("Lemma is required");
            } else {
                $lemma = Lemma::byId($data->idLemma);
                $data->name = strtolower($lemma->shortName);
                debug($data);
                Criteria::function('lu_create(?)', [$data->toJson()]);
//
//                Criteria::table("lucandidate")
//                    ->insert($data->toArray());
                $this->trigger('reload-gridLUCandidate');
                return $this->renderNotify("success", "LU Candidate created.");
            }
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/luCandidateLome/{id}')]
    public function edit(string $id)
    {
        $luCandidate = LUCandidate::byId($id);
        $idUser = AppService::getCurrentIdUser();
        $user = User::byId($idUser);
        $isManager = User::isManager($user);
        $asLOME = [];
        if ($luCandidate->email == 'lome@frame.net.br') {
            $asLOME = Criteria::table("view_annotationset")
                ->where("idUser", $luCandidate->idUser)
                ->where("idLU", $id)
                ->all();
        }
        debug($asLOME);
        return view("LUCandidateLOME.edit", [
            'luCandidate' => $luCandidate,
            'isManager' => $isManager,
            'asLOME' => $asLOME,
        ]);
    }

    #[Get(path: '/luCandidateLome/{id}/asLOME')]
    public function asLOME(string $id)
    {
        $luCandidate = LUCandidate::byId($id);
        $asLOME = [];
        if ($luCandidate->email == 'lome@frame.net.br') {
            $asLOME = Criteria::table("view_annotationset")
                ->where("idUser", $luCandidate->idUser)
                ->where("idLU", $id)
                ->all();
        }
        return view("LUCandidateLOME.modalASLOME", [
            'luCandidate' => $luCandidate,
            'asLOME' => $asLOME,
        ]);
    }



//    #[Get(path: '/luCandidateLome/{id}/formEdit')]
//    public function formEdit(string $id)
//    {
//        $idUser = AppService::getCurrentIdUser();
//        $user = User::byId($idUser);
//        $isManager = User::isManager($user);
//        return view("LUCandidate.formEdit", [
//            'luCandidate' => LUCandidate::byId($id),
//            'isManager' => $isManager,
//        ]);
//    }

    #[Get(path: '/luCandidateLome/fes/{idFrame}')]
    public function feCombobox(int $idFrame)
    {
        return view("LUCandidateLOME.fes", [
            'idFrame' => $idFrame
        ]);
    }

    #[Delete(path: '/luCandidateLome/{id}')]
    public function delete(string $id)
    {
        try {
            $luCandidate = LUCandidate::byId($id);
            MessageService::sendMessage((object)[
                'idUserFrom' => AppService::getCurrentIdUser(),
                'idUserTo' => $luCandidate->idUser,
                'class' => 'error',
                'text' => "LU candidate {$luCandidate->name} has been deleted.",
            ]);
            $idUser = AppService::getCurrentIdUser();
            Criteria::function('lu_delete(?,?)', [$id, $idUser]);
//            Criteria::deleteById("lu", "idLUCandidate", $id);
//            $this->trigger('reload-gridLUCandidate');
//            return $this->renderNotify("success", "LU candidate deleted.");
            return $this->redirect("/luCandidateLome");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/luCandidateLome')]
    public function update(UpdateData $data)
    {
        debug($data);
        Criteria::table("lu")
            ->where("idLU", $data->idLU)
            ->update($data->toArray());
        $luCandidate = LUCandidate::byId($data->idLU);
        MessageService::sendMessage((object)[
            'idUserFrom' => AppService::getCurrentIdUser(),
            'idUserTo' => $luCandidate->idUser,
            'class' => 'warning',
            'text' => "LU candidate {$luCandidate->name} has been updated.",
        ]);
        $this->trigger('reload-gridLUCandidate');
        return $this->renderNotify("success", "LU candidate updated.");
    }

    #[Post(path: '/luCandidateLome/createLU')]
    public function createLU(UpdateData $data)
    {
        try {
            $luCandidate = LUCandidate::byId($data->idLU);
            $link = '';
            if ($luCandidate->idDocumentSentence) {
                $link = "/annotation/fullText/sentence/{$luCandidate->idDocumentSentence}";
            }
            if ($luCandidate->idStaticObject) {
                $link = "/annotation/staticBBox/{$luCandidate->idStaticObject}";
            }
            if ($luCandidate->idDynamicObject) {
                $link = "/annotation/dynamicMode/{$luCandidate->idDynamicObject}";
            }
            if ($link != '') {
                $link = "<a href=\"{$link}\">Link to annotation.</a>.";
            }
            MessageService::sendMessage((object)[
                'idUserFrom' => AppService::getCurrentIdUser(),
                'idUserTo' => $luCandidate->idUser,
                'class' => 'success',
                'text' => "LU candidate {$luCandidate->name} has been created as LU.  {$link} ",
            ]);
            //Criteria::deleteById("lucandidate", "idLUCandidate", $data->idLUCandidate);
            $array = array_merge($data->toArray(), [
                'status' => 'CREATED',
                'updatedAt' => Carbon::now(),
            ]);
            debug($array);
            Criteria::table("lu")
                ->where("idLU", $luCandidate->idLU)
                ->update($array);
            return $this->renderNotify("success", "LU created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

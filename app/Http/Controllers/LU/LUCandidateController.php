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
class LUCandidateController extends Controller
{

    private function getData(SearchData $search, ?string $origin = null): array
    {
        $luIcon = view('components.icon.lu')->render();
        $query = Criteria::table("view_lucandidate")
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->where("name", "startswith", $search->lu)
            ->where("email", "startswith", $search->email)
            ->select('idLU', 'name', 'createdAt', 'frameName', 'origin', 'email');

        // Filter by origin if provided
        if ($origin !== null && $origin !== '') {
            $query->where("origin", $origin);
        }

        $lus = $query->orderBy($search->sort, $search->order)->all();
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

    #[Get(path: '/luCandidate')]
    public function resource(SearchData $search)
    {
        // Get data for each origin tab
        $dataWebTool = $this->getData($search, 'USER');
        $dataLome = $this->getData($search, 'LOME');
        $dataFnbk = $this->getData($search, 'FNBK');

        $creators = Criteria::table("view_lucandidate")
            ->distinct()
            ->select("email")
            ->orderby("email")
            ->all();

        // Determine default tab based on which has data
        $defaultTab = 'webtool';
        if (empty($dataWebTool) && !empty($dataLome)) {
            $defaultTab = 'lome';
        } elseif (empty($dataWebTool) && empty($dataLome) && !empty($dataFnbk)) {
            $defaultTab = 'fnbk';
        }

        return view("LUCandidate.browse", [
            "dataWebTool" => $dataWebTool,
            "dataLome" => $dataLome,
            "dataFnbk" => $dataFnbk,
            "creators" => $creators,
            "defaultTab" => $defaultTab,
        ]);
    }

    #[Post(path: '/luCandidate/search')]
    public function tree(SearchData $search)
    {
        debug($search);
        $data = $this->getData($search, $search->origin);
        return view('LUCandidate.tableBody', [
            'data' => $data,
        ]);
    }

//    #[Get(path: '/luCandidate/data')]
//    public function data(SearchData $search)
//    {
//        $luIcon = view('components.icon.lu')->render();
//        $lus = Criteria::byFilterLanguage("view_lucandidate", ["name", "startswith", $search->lu])
//            ->select('idLU', 'name', 'createdAt','frameName','origin')
////            ->selectRaw("IFNULL(frameName, frameCandidate) as frameName")
//            ->orderBy($search->sort, $search->order)->all();
//        $data = array_map(fn($item) => [
//            'id' => $item->idLU,
//            'name' => $luIcon . $item->name,
//            'frameName' => $item->frameName,
//            'createdAt' => $item->createdAt ? Carbon::parse($item->createdAt)->format("d/m/Y") : '-',
//            'state' => 'open',
//            'origin' => $item->origin,
//            'type' => 'lu'
//        ], $lus);
//        return $data;
//    }
//
//    #[Get(path: '/luCandidate/grid/{fragment?}')]
//    #[Post(path: '/luCandidate/grid/{fragment?}')]
//    public function grid(SearchData $search, ?string $fragment = null)
//    {
//        $view = view("LUCandidate.grid", [
//            'search' => $search,
//        ]);
//        return (is_null($fragment) ? $view : $view->fragment('search'));
//    }

    #[Get(path: '/luCandidate/new')]
    public function new()
    {
        return view("LUCandidate.formNew");
    }

    #[Post(path: '/luCandidate')]
    public function newLU(CreateData $data)
    {
        debug($data);
        try {
            if ((is_null($data->idLemma) || ($data->idLemma == 0))) {
                throw new \Exception("Lemma is required");
            } else {
                $lemma = Lemma::byId($data->idLemma);
                $data->name = strtolower($lemma->name);
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

    #[Get(path: '/luCandidate/{id}')]
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

        // Calculate Previous ID (alphabetically by name, same origin)
        $idPrevious = Criteria::table("view_lucandidate")
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->where("origin", $luCandidate->origin)
            ->where("name", "<", $luCandidate->name)
            ->orderBy("name", "desc")
            ->select("idLU")
            ->first()?->idLU;

        // Calculate Next ID (alphabetically by name, same origin)
        $idNext = Criteria::table("view_lucandidate")
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->where("origin", $luCandidate->origin)
            ->where("name", ">", $luCandidate->name)
            ->orderBy("name", "asc")
            ->select("idLU")
            ->first()?->idLU;

        debug($asLOME);
        return view("LUCandidate.edit", [
            'luCandidate' => $luCandidate,
            'isManager' => $isManager,
            'asLOME' => $asLOME,
            'idPrevious' => $idPrevious,
            'idNext' => $idNext,
        ]);
    }

    #[Get(path: '/luCandidate/{id}/asLOME')]
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
        return view("LUCandidate.modalASLOME", [
            'luCandidate' => $luCandidate,
            'asLOME' => $asLOME,
        ]);
    }



//    #[Get(path: '/luCandidate/{id}/formEdit')]
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

    #[Get(path: '/luCandidate/fes/{idFrame}')]
    public function feCombobox(int $idFrame)
    {
        return view("LUCandidate.fes", [
            'idFrame' => $idFrame
        ]);
    }

    #[Delete(path: '/luCandidate/{id}')]
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
            return $this->redirect("/luCandidate");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/luCandidate')]
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

    #[Post(path: '/luCandidate/createLU')]
    public function createLU(UpdateData $data)
    {
        try {
//            $exists = Criteria::table("lu")
//                ->where("idLexicon",$data->idLexicon)
//                ->where("idFrame",$data->idFrame)
//                ->first();
//            if (!is_null($exists)) {
//                throw new \Exception("LU already exists.");
//            }
//            Criteria::function('lu_create(?)', [$data->toJson()]);
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

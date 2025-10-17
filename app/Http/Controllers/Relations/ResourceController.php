<?php

namespace App\Http\Controllers\Relations;

use App\Data\Relations\CreateRelationGroupData;
use App\Data\Relations\CreateRelationTypeData;
use App\Data\Relations\SearchData;
use App\Data\Relations\UpdateRelationGroupData;
use App\Data\Relations\UpdateRelationTypeData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\RelationGroup;
use App\Repositories\RelationType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class ResourceController extends Controller
{

    #[Get(path: '/relations')]
    public function browse()
    {
        $search = session('searchRelations') ?? SearchData::from();
        return view("Relations.resource", [
            'search' => $search
        ]);
    }

    #[Get(path: '/relations/grid/{fragment?}')]
    #[Post(path: '/relations/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $view = view("Relations.grid", [
            'search' => $search,
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    /*------
      Relationgroup
      ------ */

    #[Get(path: '/relations/relationgroup/new')]
    public function formNewRelationGroup()
    {
        return view("Relations.formNewRelationGroup");
    }

    #[Get(path: '/relations/relationgroup/{idRelationGroup}/edit')]
    public function relationgroup(int $idRelationGroup)
    {
        $relationGroup = RelationGroup::byId($idRelationGroup);
        return view("Relations.editRelationGroup", [
            'relationGroup' => $relationGroup,
        ]);
    }

    #[Get(path: '/relations/relationgroup/{idRelationGroup}/formEdit')]
    public function formEditRelationGroup(int $idRelationGroup)
    {
        $relationGroup = RelationGroup::byId($idRelationGroup);
        return view("Relations.formEditRelationGroup", [
            'relationGroup' => $relationGroup,
        ]);
    }

    #[Post(path: '/relations/relationgroup/new')]
    public function newRelationGroup(CreateRelationGroupData $data)
    {
        try {
            $entry = "rgp_" . strtolower($data->nameEn);
            $exists = Criteria::table("relationgroup")
                ->whereRaw("entry = '{$entry}' collate 'utf8mb4_bin'")
                ->first();
            if (!is_null($exists)) {
                throw new \Exception("RelationGroup already exists.");
            }
            $idRelationGroup = Criteria::function('relationgroup_create(?)', [$data->toJson()]);
            $relationGroup = RelationGroup::byId($idRelationGroup);
            $this->trigger('reload-gridRelations');
            return $this->render("Relations.editRelationGroup", [
                'relationGroup' => $relationGroup,
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/relations/relationgroup')]
    public function updateRelationGroup(UpdateRelationGroupData $data)
    {
        try {
            Criteria::table("relationgroup")
                ->where("idRelationGroup", $data->idRelationGroup)
                ->update([
                    'name' => $data->name,
                ]);
            return $this->renderNotify("success", "RelationGroup updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/relations/relationgroup/{idRelationGroup}')]
    public function deleteRelationGroup(string $idRelationGroup)
    {
        try {
            Criteria::deleteById("relationgroup", "idRelationGroup", $idRelationGroup);
            $this->trigger('clear-editarea', ['target' => '#editarea']);
            $this->trigger("reload-gridRelations", ['target' => '#editarea']);
            return $this->renderNotify("success", "RelationGroup removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


    /*------
      RelationType
      ------ */

    #[Get(path: '/relations/relationtype/new')]
    public function formNewRelationType()
    {
        return view("Relations.formNewRelationType");
    }

    #[Get(path: '/relations/relationtype/{idRelationType}/edit')]
    public function relationtype(int $idRelationType)
    {
        $relationType = RelationType::byId($idRelationType);
        return view("Relations.editRelationType", [
            'relationType' => $relationType,
        ]);
    }

    #[Get(path: '/relations/relationtype/{idRelationType}/formEdit')]
    public function formEditRelationType(int $idRelationType)
    {
        $relationType = RelationType::byId($idRelationType);
        return view("Relations.formEditRelationType", [
            'relationType' => $relationType,
        ]);
    }

    #[Post(path: '/relations/relationtype/new')]
    public function newRelationType(CreateRelationTypeData $data)
    {
        try {
            $entry = "rel_" . strtolower($data->nameCanonical);
            $exists = Criteria::table("relationtype")
                ->whereRaw("entry = '{$data->nameCanonical}' collate 'utf8mb4_bin'")
                ->first();
            if (!is_null($exists)) {
                throw new \Exception("RelationType already exists.");
            }
            $idRelationType = Criteria::function("relationtype_create(?)", [$data->toJson()]);
            $relationType = RelationType::byId($idRelationType);
            $this->trigger('reload-gridRelations');
            return $this->render("Relations.editRelationType", [
                'relationType' => $relationType,
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/relations/relationtype')]
    public function updateRelationType(UpdateRelationTypeData $data)
    {
        try {
            Criteria::table("relationtype")
                ->where("idRelationType", $data->idRelationType)
                ->update($data->toArray());
            return $this->renderNotify("success", "RelationType updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/relations/relationtype/{idRelationType}')]
    public function deleteRelationType(string $idRelationType)
    {
        try {
            Criteria::function("relationtype_delete(?,?)", [$idRelationType, AppService::getCurrentIdUser()]);
            $this->trigger('clear-editarea', ['target' => '#editArea']);
            $this->trigger("reload-gridRelations", ['target' => '#editArea']);
            return $this->renderNotify("success", "RelationType removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

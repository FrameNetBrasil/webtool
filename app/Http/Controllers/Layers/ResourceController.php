<?php

namespace App\Http\Controllers\Layers;

use App\Data\Layers\CreateGenericLabelData;
use App\Data\Layers\CreateLayerGroupData;
use App\Data\Layers\CreateLayerTypeData;
use App\Data\Layers\SearchData;
use App\Data\Layers\UpdateGenericLabelData;
use App\Data\Layers\UpdateLayerGroupData;
use App\Data\Layers\UpdateLayerTypeData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\GenericLabel;
use App\Repositories\LayerGroup;
use App\Repositories\LayerType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class ResourceController extends Controller
{

    #[Get(path: '/layers')]
    public function browse()
    {
        $search = session('searchLayers') ?? SearchData::from();
        return view("Layers.resource", [
            'search' => $search
        ]);
    }

    #[Get(path: '/layers/grid/{fragment?}')]
    #[Post(path: '/layers/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $view = view("Layers.grid", [
            'search' => $search,
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    /*------
      Layergroup
      ------ */

    #[Get(path: '/layers/layergroup/new')]
    public function formNewLayerGroup()
    {
        return view("Layers.formNewLayerGroup");
    }

    #[Get(path: '/layers/layergroup/{idLayerGroup}/edit')]
    public function layergroup(int $idLayerGroup)
    {
        $layerGroup = LayerGroup::byId($idLayerGroup);
        return view("Layers.editLayerGroup", [
            'layerGroup' => $layerGroup,
        ]);
    }

    #[Get(path: '/layers/layergroup/{idLayerGroup}/formEdit')]
    public function formEditLayerGroup(int $idLayerGroup)
    {
        $layerGroup = LayerGroup::byId($idLayerGroup);
        return view("Layers.formEditLayerGroup", [
            'layerGroup' => $layerGroup,
        ]);
    }

    #[Post(path: '/layers/layergroup/new')]
    public function newLayerGroup(CreateLayerGroupData $data)
    {
        try {
            $exists = Criteria::table("layergroup")
                ->whereRaw("name = '{$data->name}' collate 'utf8mb4_bin'")
                ->first();
            if (!is_null($exists)) {
                throw new \Exception("LayerGroup already exists.");
            }
            $newLayerGroup = [
                'name' => $data->name,
                'type' => $data->type,
            ];
            $idLayerGroup = Criteria::create("layergroup", $newLayerGroup);
            $layerGroup = LayerGroup::byId($idLayerGroup);
            $this->trigger('reload-gridLayers');
            return $this->render("Layers.editLayerGroup", [
                'layerGroup' => $layerGroup,
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/layers/layergroup')]
    public function updateLayerGroup(UpdateLayerGroupData $data)
    {
        try {
            Criteria::table("layergroup")
                ->where("idLayerGroup", $data->idLayerGroup)
                ->update([
                    'name' => $data->name,
                ]);
            return $this->renderNotify("success", "LayerGroup updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/layers/layergroup/{idLayerGroup}')]
    public function deleteLayerGroup(string $idLayerGroup)
    {
        try {
            Criteria::deleteById("layergroup", "idLayerGroup", $idLayerGroup);
            $this->trigger('clear-editarea', ['target' => '#editarea']);
            $this->trigger("reload-gridLayers", ['target' => '#editarea']);
            return $this->renderNotify("success", "LayerGroup removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


    /*------
      LayerType
      ------ */

    #[Get(path: '/layers/layertype/new')]
    public function formNewLayerType()
    {
        return view("Layers.formNewLayerType");
    }

    #[Get(path: '/layers/layertype/{idLayerType}/edit')]
    public function layertype(int $idLayerType)
    {
        $layerType = LayerType::byId($idLayerType);
        return view("Layers.editLayerType", [
            'layerType' => $layerType,
        ]);
    }

    #[Get(path: '/layers/layertype/{idLayerType}/formEdit')]
    public function formEditLayerType(int $idLayerType)
    {
        $layerType = LayerType::byId($idLayerType);
        debug($layerType);
        return view("Layers.formEditLayerType", [
            'layerType' => $layerType,
        ]);
    }

    #[Post(path: '/layers/layertype/new')]
    public function newLayerType(CreateLayerTypeData $data)
    {
        try {
            $exists = Criteria::table("view_layertype")
                ->whereRaw("name = '{$data->nameEn}' collate 'utf8mb4_bin'")
                ->where("idLanguage",2)
                ->first();
            if (!is_null($exists)) {
                throw new \Exception("LayerType already exists.");
            }
            $json = json_encode((object)[
                'nameEn' => $data->nameEn,
                'allowsApositional' => $data->allowsApositional,
                'isAnnotation' => $data->isAnnotation,
                'layerOrder' => $data->layerOrder,
                'idLayerGroup' => $data->idLayerGroup
            ]);
            $idLayerType = Criteria::function("layertype_create(?)", [$json]);
            $layerType = LayerType::byId($idLayerType);
            $this->trigger('reload-gridLayers');
            return $this->render("Layers.editLayerType", [
                'layerType' => $layerType,
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/layers/layertype')]
    public function updateLayerType(UpdateLayerTypeData $data)
    {
        try {
            Criteria::table("layertype")
                ->where("idLayerType", $data->idLayerType)
                ->update($data->toArray());
            return $this->renderNotify("success", "LayerType updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/layers/layertype/{idLayerType}')]
    public function deleteLayerType(string $idLayerType)
    {
        try {
            Criteria::function("layertype_delete(?,?)", [$idLayerType, AppService::getCurrentIdUser()]);
            $this->trigger('clear-editarea', ['target' => '#editArea']);
            $this->trigger("reload-gridLayers", ['target' => '#editArea']);
            return $this->renderNotify("success", "LayerType removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    /*-----------
      GenericLabel
      ----------- */

    #[Get(path: '/layers/genericlabel/new')]
    public function formNewGenericLabel()
    {
        return view("Layers.formNewGenericLabel");
    }

    #[Get(path: '/layers/genericlabel/{idGenericLabel}/edit')]
    public function genericlabel(int $idGenericLabel)
    {
        $genericLabel = GenericLabel::byId($idGenericLabel);
        return view("Layers.editGenericLabel", [
            'genericLabel' => $genericLabel,
        ]);
    }

    #[Get(path: '/layers/genericlabel/{idGenericLabel}/formEdit')]
    public function formEditGenericLabel(int $idGenericLabel)
    {
        $genericLabel = GenericLabel::byId($idGenericLabel);
        return view("Layers.formEditGenericLabel", [
            'genericLabel' => $genericLabel,
        ]);
    }

    #[Post(path: '/layers/genericlabel/new')]
    public function newGenericLabel(CreateGenericLabelData $data)
    {
        try {
            $exists = Criteria::table("genericlabel")
                ->whereRaw("name = '{$data->name}' collate 'utf8mb4_bin'")
                ->where("idLanguage", $data->idLanguage)
                ->where("idLayerType", $data->idLayerType)
                ->first();
            if (!is_null($exists)) {
                throw new \Exception("GenericLabel already exists.");
            }
            $json = json_encode((object)[
                'name' => $data->name,
                'idColor' => $data->idColor,
                'idLanguage' => $data->idLanguage,
                'definition' => $data->definition,
                'example' => '',
                'idLayerType' => $data->idLayerType,
                'idUser' => $data->idUser
            ]);
            $idGenericLabel = Criteria::function("genericlabel_create(?)", [$json]);
            $genericLabel = GenericLabel::byId($idGenericLabel);
            return $this->render("Layers.editGenericLabel", [
                'genericLabel' => $genericLabel,
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Put(path: '/layers/genericlabel')]
    public function updateGenericLabel(UpdateGenericLabelData $data)
    {
        try {
            Criteria::table("genericlabel")
                ->where("idGenericLabel", $data->idGenericLabel)
                ->update($data->toArray());
            return $this->renderNotify("success", "GenericLabel updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/layers/genericlabel/{idGenericLabel}')]
    public function deleteGenericLabel(string $idGenericLabel)
    {
        try {
            Criteria::function("genericlabel_delete(?,?)", [$idGenericLabel, AppService::getCurrentIdUser()]);
            $this->trigger('clear-editarea', ['target' => '#editArea']);
            $this->trigger("reload-gridLayers", ['target' => '#editArea']);
            return $this->renderNotify("success", "GenericLabel removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

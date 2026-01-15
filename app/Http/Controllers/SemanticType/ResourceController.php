<?php

namespace App\Http\Controllers\SemanticType;

use App\Data\ComboBox\QData;
use App\Data\SemanticType\CreateData;
use App\Data\SemanticType\SearchData;
use App\Data\SemanticType\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\RelationService;
use App\Services\SemanticType\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/semanticType')]
    public function index(SearchData $search)
    {
        $data = BrowseService::browseSemanticTypeBySearch($search);
        return view('SemanticType.browse', [
            'data' => $data,
        ]);
    }

    #[Post(path: '/semanticType/browse/search')]
    public function search(SearchData $search)
    {
        $title = '';
        $data = BrowseService::browseSemanticTypeBySearch($search);
        return view('SemanticType.tree', [
            'title' => $title,
            'data' => $data,
        ]);
    }

    #[Get(path: '/semanticType/{id}/edit')]
    public function edit(string $id)
    {
        return view("SemanticType.edit",[
            'semanticType' => SemanticType::byId($id)
        ]);
    }

    #[Get(path: '/semanticType/{id}/formEdit')]
    public function formEdit(string $id)
    {
        $st = SemanticType::byId($id);
        return view("SemanticType.formEdit",[
            'semanticType' => $st
        ]);
    }

    #[Post(path: '/semanticType')]
    public function update(UpdateData $data)
    {
        try {
            debug($data);
            SemanticType::setParent($data->idSemanticType, $data->idSemanticTypeParent);
            return $this->renderNotify("success", "SemanticType updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/semanticType/new')]
    public function new()
    {
        return view("SemanticType.formNew");
    }

    #[Post(path: '/semanticType/new')]
    public function create(CreateData $data)
    {
        try {
            debug($data);
            $idSemanticType = Criteria::function('semantictype_create(?)', [$data->toJson()]);
            SemanticType::setParent($idSemanticType, $data->idSemanticTypeParent);
            return $this->renderNotify("success", "SemanticType created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/semanticType/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::function('semantictype_delete(?,?)', [$id, AppService::getCurrentIdUser()]);
            return $this->clientRedirect("/semanticType");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/semanticType/list/forSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->semanticType) > 2) ? $data->semanticType : 'none';
        return ['results' => Criteria::byFilterLanguage("view_semantictype",["name","startswith",$name])->orderby("name")->all()];
    }

    /***
     * Child
     */
    #[Get(path: '/semanticType/{idEntity}/childAdd/{root}')]
    public function childFormAdd(string $idEntity, string $root)
    {
        return view("SemanticType.childAdd", [
            'idEntity' => $idEntity,
            'root' => $root
        ]);
    }

    #[Get(path: '/semanticType/{idEntity}/childGrid')]
    public function childGrid(string $idEntity)
    {
        $relations = SemanticType::listRelations($idEntity);
        return view("SemanticType.childGrid", [
            'idEntity' => $idEntity,
            'relations' => $relations
        ]);
    }

    #[Post(path: '/semanticType/{idEntity}/add')]
    public function childAdd(CreateData $data)
    {
        try {
            $st = SemanticType::byId($data->idSemanticType);
            RelationService::create(
                'rel_hassemtype',
                $data->idEntity,
                $st->idEntity,
                null,
                null,
                $data->idUser
            );
            $this->trigger('reload-gridChildST');
            return $this->renderNotify("success", "Semantic Type added.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/semanticType/relation/{idEntityRelation}')]
    public function childDelete(int $idEntityRelation)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idEntityRelation)->delete();
            $this->trigger('reload-gridChildST');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/semanticType/{idEntity}/childSubTypeAdd/{root}')]
    public function childFormAddSubType(string $idEntity, string $root)
    {
        return view("SemanticType.childSubTypeAdd", [
            'idEntity' => $idEntity,
            'root' => $root
        ]);
    }

    #[Post(path: '/semanticType/{idEntity}/addSubType')]
    public function childAddSubType(CreateData $data)
    {
        try {
            $parent = SemanticType::byIdEntity($data->idEntity);
            $child = SemanticType::byId($data->idSemanticType);
            RelationService::create(
                'rel_subtypeof',
                $child->idEntity,
                $parent->idEntity,
                null,
                null
            );
            $this->trigger('reload-gridChildST');
            return $this->renderNotify("success", "Subtype added.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


    #[Get(path: '/semanticType/{idEntity}/childSubTypeGrid')]
    public function childGridSubType(string $idEntity)
    {
        $relations = SemanticType::listChildren($idEntity);
        debug($relations);
        return view("SemanticType.childSubTypeGrid", [
            'idEntity' => $idEntity,
            'relations' => $relations
        ]);
    }


}

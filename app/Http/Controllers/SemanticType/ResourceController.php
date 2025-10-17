<?php

namespace App\Http\Controllers\SemanticType;

use App\Data\Domain\SearchData as DomainSearchData;
use App\Data\SemanticType\CreateData;
use App\Data\SemanticType\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\RelationService;
use App\View\Components\Combobox\Domain;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class ResourceController extends Controller
{

    #[Get(path: '/semanticType')]
    public function resource(int|string $idConcept = '', string $lang = '')
    {
        $search = session('searchSemanticType') ?? SearchData::from();
        $data = [];
        return view("SemanticType.resource", [
            'search' => $search,
            'idSemanticType' => null,
            'data' => $data,
        ]);
    }

    #[Get(path: '/semanticType/grid')]
    public function grid()
    {
        $search = session('searchSemanticType') ?? SearchData::from();
        $data = [];
        return view("SemanticType.grid", [
            'search' => $search,
            'idSemanticType' => null,
            'data' => $data,
        ]);
    }

    /*
    #[Get(path: '/semanticType/grid/{fragment?}')]
    #[Post(path: '/semanticType/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $data = $this->listForTree($search);
        $view = view("SemanticType.grid",[
            'search' => $search,
            'data' => $data,
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }
    private function listForTree(SearchData $search)
    {
        $domainIcon = view('components.icon.domain')->render();
        $stIcon = view('components.icon.semantictype')->render();
        $tree = [];
        if ($search->semanticType != '') {
            $st = Criteria::table("view_semanticType")
                ->select("idSemanticType", "idEntity", "name")
                ->where("name","startswith",'@'.$search->semanticType)
                ->where('idLanguage', '=', AppService::getCurrentIdLanguage())
                ->orderBy("name")->all();
            foreach ($st as $row) {
                $node = [];
                $node['id'] = 't' . $row->idEntity;
                $node['idSemanticType'] = $row->idSemanticType;
                $node['type'] = 'semanticType';
                $node['text'] = $stIcon . $row->name;
                $node['state'] = 'closed';
                $node['children'] = $this->getChildren($row->idEntity);
                $tree[] = $node;
            }
        } else {
            $domains = SemanticType::listDomains();
            foreach ($domains as $row) {
                $node = [];
                $node['id'] = $row->idDomain;
                $node['idDomain'] = $row->idDomain;
                $node['type'] = 'domain';
                $node['text'] = $domainIcon . $row->name;
                $node['state'] = 'closed';
                $roots = SemanticType::listRootByDomain($row->idDomain);
                $children = [];
                foreach ($roots as $root) {
                    $n = [];
                    $n['id'] = $root->idEntity;
                    $n['idSemanticType'] = $root->idSemanticType;
                    $n['type'] = 'semanticType';
                    $n['text'] = $stIcon . $root->name;
                    $n['state'] = 'closed';
                    $n['children'] = $this->getChildren($root->idEntity);
                    $children[] = $n;
                }
                $node['children'] = $children;
                $tree[] = $node;
            }
        }
        return $tree;
    }

    private function getChildren(int $idEntity): array
    {
        $stIcon = view('components.icon.semantictype')->render();
        $children = [];
        $st = SemanticType::listChildren($idEntity);
        foreach ($st as $row) {
            $n = [];
            $n['id'] = $row->idEntity;
            $n['idSemanticType'] = $row->idSemanticType;
            $n['type'] = 'semanticType';
            $n['text'] = $stIcon . $row->name;
            $n['state'] = 'closed';
            $n['children'] = $this->getChildren($row->idEntity);;
            $children[] = $n;
        }
        return $children;
    }

    */
    #[Get(path: '/semanticType/{id}/subTypes')]
    public function semanticTypes(string $id)
    {
        $semanticType = SemanticType::byId($id);
        return view("SemanticType.childSubType", [
            'idEntity' => $semanticType->idEntity,
            'root' => $semanticType->name
        ]);
    }


    /***
     * Master
     */

    #[Get(path: '/semanticType/{id}/edit')]
    public function get(string $id)
    {
        return view("SemanticType.edit", [
            'semanticType' => SemanticType::byId($id),
        ]);
    }

    #[Delete(path: '/semanticType/{idSemanticType}')]
    public function masterDelete(int $idSemanticType)
    {
        try {
            Criteria::deleteById("semantictype", "idSemanticType", $idSemanticType);
            return $this->clientRedirect("/semanticType");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/semanticType/new')]
    public function formNew()
    {
        return view("SemanticType.formNew");
    }

    #[Post(path: '/semanticType/new')]
    public function new(CreateData $data)
    {
        try {
            $json = json_encode([
                'idDomain' => $data->idDomain,
                'nameEn' => $data->semanticTypeName,
                'idUser' => $data->idUser
            ]);
            $idSemanticType = Criteria::function('semantictype_create(?)', [$json]);
            return $this->renderNotify("success", "Semantic Type created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
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

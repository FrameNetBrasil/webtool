<?php

namespace App\Http\Controllers\CE;

use App\Data\CE\CreateData;
use App\Data\CE\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\ConstructionElement;
use App\Repositories\FrameElement;
use App\Repositories\ViewFrameElement;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class ResourceController extends Controller
{
    public static function listForTreeByFrame(int $idFrame)
    {
        return Criteria::byFilterLanguage("view_frameelement", ['idFrame', "=", $idFrame])
            ->all();
    }

    public static function listForGridByFrame(int $idFrame)
    {
        return Criteria::byFilterLanguage("view_frameelement", ['idFrame', "=", $idFrame])
            ->get()
            ->groupBy('coreType')
            ->toArray();
    }

    public static function listForTreeByName(string $name)
    {
        $result = [];
        $filter = (object)[
            'fe' => $name
        ];
        $icon = config('webtool.fe.icon.grid');
        $fes = ViewFrameElement::listByFilter($filter)->all();
        foreach ($fes as $row) {
            $node = [];
            $node['id'] = 'e' . $row->idFrameElement;
            $node['type'] = 'feFrame';
            $node['name'] = [$row->name, $row->description, $row->frameName];
            $node['idColor'] = $row->idColor;
            $node['state'] = 'closed';
//            $node['iconCls'] = $icon[$row->coreType];
            $node['coreType'] = $row->coreType;
            $node['children'] = [];
            $result[] = $node;
        }
        return $result;
    }

    #[Post(path: '/ce')]
    public function newCE(CreateData $data)
    {
        debug($data);
        try {
//            par_idConstruction INT,
//	par_name VARCHAR(255),
//    par_idColor INT,
//    par_optional INT,
//    par_head INT,
//    par_multiple INT,
//    par_idUser INT
            $idcxn = Criteria::function('ce_create(?)', [$data->toJson()]);
//            Criteria::function('ce_create(?, ?, ?, ?, ?, ?, ?)', [
//                $data->idConstruction,
//                $data->name,
//                $data->idColor,
//                $data->optional,
//                $data->head,
//                $data->multiple,
//                $data->idUser
//            ]);
            $this->trigger('reload-gridCE');
            return $this->renderNotify("success", "ConstructionElement created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/ce/{id}/edit')]
    public function edit(string $id)
    {
        return view("CE.edit", [
            'constructionElement' => ConstructionElement::byId($id)
        ]);
    }

    #[Get(path: '/ce/{id}/main')]
    public function main(string $id)
    {
        $this->data->_layout = 'main';
        return $this->edit($id);
    }


    #[Delete(path: '/ce/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::function('ce_delete(?, ?)', [
                $id,
                AppService::getCurrentUser()->idUser
            ]);
            $this->trigger('reload-gridCE');
            return $this->renderNotify("success", "ConstructionElement deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/ce/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("CE.formEdit", [
            'constructionElement' => ConstructionElement::byId($id)
        ]);
    }

    #[Put(path: '/ce/{id}')]
    public function update(string $id, UpdateData $data)
    {
        ConstructionElement::update($data);
        $this->trigger('reload-objectCE');
        return $this->renderNotify("success", "ConstructionElement updated.");
    }

}

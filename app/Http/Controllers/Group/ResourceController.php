<?php

namespace App\Http\Controllers\Group;

use App\Data\Group\CreateData;
use App\Data\Group\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Group;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{

    #[Get(path: '/group/listForSelect')]
    public function listForSelect()
    {
        return Group::listForSelect();
    }

    #[Get(path: '/group/new')]
    public function new()
    {
        return view("Group.formNew");
    }

    #[Get(path: '/group/{id}/edit')]
    public function edit(string $id)
    {
        debug($id);
        return view("Group.edit",[
            'group' => Group::byId($id)
        ]);
    }

    #[Get(path: '/group/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("Group.formEdit",[
            'group' => Group::byId($id)
        ]);
    }

    #[Post(path: '/group')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::table("group")
                ->where("idGroup",$data->idGroup)
                ->update($data->toArray());
            $this->trigger("reload-gridUser");
            return $this->renderNotify("success", "Group updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/group/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::create("group", $data->toArray());
            $this->trigger("reload-gridUser");
            return $this->renderNotify("success", "Group created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/group/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::deleteById("group", "idGroup", $id);
            return $this->clientRedirect("/user");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

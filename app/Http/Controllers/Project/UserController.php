<?php

namespace App\Http\Controllers\Project;

use App\Data\Project\ManagerData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class UserController extends Controller
{
    #[Get(path: '/project/{id}/users')]
    public function users(int $id)
    {
        return view("Project.users", [
            'idProject' => $id
        ]);
    }

    #[Get(path: '/project/{id}/users/formNew')]
    public function usersFormNew(int $id)
    {
        return view("Project.usersNew", [
            'idProject' => $id
        ]);
    }

    #[Get(path: '/project/{id}/users/grid')]
    public function usersGrid(int $id)
    {
        $managers = Criteria::table("project_manager")
            ->join("user", "project_manager.idUser", "=", "user.idUser")
            ->select("project_manager.*","user.name")
            ->where("project_manager.idProject", $id)
            ->all();
        return view("Project.usersGrid", [
            'idProject' => $id,
            'managers' => $managers
        ]);
    }

    #[Post(path: '/project/{id}/users/new')]
    public function projectsNew(ManagerData $data)
    {
        Criteria::table("project_manager")->insert($data->toArray());
        $this->trigger('reload-gridManagers');
        return $this->renderNotify("success", "Manager added to project.");
    }

    #[Delete(path: '/project/{id}/users/{idUser}')]
    public function delete(int $id, int $idUser)
    {
        try {
            Criteria::table("project_manager")
                ->where("idUser", $idUser)
                ->where("idProject", $id)
                ->delete();
            $this->trigger('reload-gridManagers');
            return $this->renderNotify("success", "Manager removed from project.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

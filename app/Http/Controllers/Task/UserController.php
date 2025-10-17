<?php

namespace App\Http\Controllers\Task;

use App\Data\Dataset\CreateData;
use App\Data\Dataset\ProjectData;
use App\Data\Dataset\SearchData;
use App\Data\Dataset\UpdateData;
use App\Data\Task\UserTaskData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Dataset;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class UserController extends Controller
{
    #[Get(path: '/task/{id}/users')]
    public function users(int $id)
    {
        return view("Task.users", [
            'idTask' => $id
        ]);
    }

    #[Get(path: '/task/{id}/users/formNew')]
    public function usersFormNew(int $id)
    {
        return view("Task.usersNew", [
            'idTask' => $id
        ]);
    }

    #[Get(path: '/task/{id}/users/grid')]
    public function usersGrid(int $id)
    {
        $usertasks = Criteria::table("usertask")
            ->join("user", "usertask.idUser", "=", "user.idUser")
            ->select("usertask.*","user.name")
            ->where("usertask.idTask", $id)
            ->orderBy("user.name")
            ->all();
        return view("Task.usersGrid", [
            'idTask' => $id,
            'usertasks' => $usertasks
        ]);
    }

    #[Post(path: '/task/{id}/users/new')]
    public function projectsNew(UserTaskData $data)
    {
        Criteria::table("usertask")->insert($data->toArray());
        $this->trigger('reload-gridTask');
        return $this->renderNotify("success", "UserTask added to task.");
    }

    #[Delete(path: '/task/{id}/users/{idUserTask}')]
    public function delete(int $id, int $idUserTask)
    {
        try {
            Criteria::table("usertask")
                ->where("idUserTask", $idUserTask)
                ->delete();
            $this->trigger('reload-gridTask');
            return $this->renderNotify("success", "User removed from task.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

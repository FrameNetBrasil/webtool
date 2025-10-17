<?php

namespace App\Http\Controllers\Task;

use App\Data\Task\CreateData;
use App\Data\Task\SearchData;
use App\Data\Task\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Project;
use App\Repositories\Task;
use App\Services\Task\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/task')]
    public function resource(SearchData $search)
    {
        $data = BrowseService::browseTaskUserBySearch($search);

        return view("Task.browser", [
            'title' => 'Task/User',
            'data' => $data,
        ]);
    }

    #[Post(path: '/task/search')]
    public function search(SearchData $search)
    {
        $title = "";
        $data = BrowseService::browseTaskUserBySearch($search);

        // Handle tree expansion - when expanding a task, show users without title
        if ($search->type === 'task' && $search->id != 0) {
            $title = ''; // No title for expansions
        }
        // Handle search filtering
        elseif (!empty($search->task)) {
            $title = 'Tasks';
        } elseif (!empty($search->user)) {
            $title = 'Users';
        } else {
            $title = 'Tasks';
        }

        return view('Task.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }

    #[Get(path: '/task/new')]
    public function new()
    {
        return view("Task.formNew");
    }

    #[Get(path: '/task/grid/{fragment?}')]
    #[Post(path: '/task/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $users = Task::listUsersToGrid($search->user ?? '');
        $tasks = Task::listToGrid($search?->task ?? '');
        $view = view("Task.grid",[
            'tasks' => $tasks,
            'users' => $users
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    #[Get(path: '/task/{id}/edit')]
    public function edit(string $id)
    {
        debug($id);
        return view("Task.edit",[
            'task' => Task::byId($id)
        ]);
    }

    #[Get(path: '/task/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("Task.formEdit",[
            'task' => Task::byId($id)
        ]);
    }

    #[Post(path: '/task')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::table("task")
                ->where("idTask",$data->idTask)
                ->update($data->toArray());
            $this->trigger("reload-gridTask");
            return $this->renderNotify("success", "Task updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/task/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::table("task")
                ->insert($data->toArray());
            $this->trigger("reload-gridTask");
            return $this->renderNotify("success", "Task created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/task/{id}')]
    public function delete(string $id)
    {
        try {
            //Criteria::function('user_delete(?)', [$id]);
            return $this->clientRedirect("/task");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

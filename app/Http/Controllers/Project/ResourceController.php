<?php

namespace App\Http\Controllers\Project;

use App\Data\Project\SearchData;
use App\Data\Project\CreateData;
use App\Data\Project\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Dataset;
use App\Repositories\Project;
use App\Services\Project\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/project')]
    public function resource(SearchData $search)
    {
        $data = BrowseService::browseProjectDatasetBySearch($search);

        return view("Project.browser", [
            'title' => 'Project/Dataset',
            'data' => $data,
        ]);
    }

    #[Post(path: '/project/search')]
    public function search(SearchData $search)
    {
        $title = "";
        $data = BrowseService::browseProjectDatasetBySearch($search);

        // Handle tree expansion - when expanding a project, show datasets without title
        if ($search->type === 'project' && $search->id != 0) {
            $title = ''; // No title for expansions
        }
        // Handle search filtering
        elseif (!empty($search->project)) {
            $title = 'Projects';
        } elseif (!empty($search->dataset)) {
            $title = 'Datasets';
        } else {
            $title = 'Projects';
        }

        return view('Project.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }


    #[Get(path: '/project/grid/{fragment?}')]
    #[Post(path: '/project/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $view = view("Project.grid", [
            'search' => $search
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    #[Get(path: '/project/data')]
    public function data(SearchData $search)
    {
        if ($search->id != 0) {
            $data = Criteria::table("project_dataset")
                ->join("dataset", "project_dataset.idDataset", "=", "dataset.idDataset")
                ->where("project_dataset.idProject", $search->id)
                ->select('dataset.idDataset', 'dataset.name')
                ->selectRaw("concat('d',dataset.idDataset) as id")
                ->selectRaw("'' as project")
                ->selectRaw("'open' as state")
                ->selectRaw("'dataset' as type")
                ->orderBy("dataset.name")->all();
        } else {
            if ($search->dataset == '') {
                $data = Criteria::table("project")
                    ->select("idProject as id", "idProject", "name")
                    ->selectRaw("'closed' as state")
                    ->selectRaw("'project' as type")
                    ->where("name", "startswith", $search->project)
                    ->orderBy("name")
                    ->all();
            } else {
                $data = Criteria::table("project_dataset")
                    ->join("dataset", "project_dataset.idDataset", "=", "dataset.idDataset")
                    ->join("project", "project_dataset.idProject", "=", "project.idProject")
                    ->where("dataset.name", "startswith", $search->dataset)
                    ->select('dataset.idDataset', 'dataset.name')
                    ->selectRaw("concat('d',dataset.idDataset) as id")
                    ->selectRaw("concat(' [',project.name,']') as project")
                    ->selectRaw("'open' as state")
                    ->selectRaw("'dataset' as type")
                    ->orderBy("dataset.name")->all();
            }
        }
        return $data;
    }

    #[Get(path: '/project/new')]
    public function new()
    {
        return view("Project.formNew");
    }

    #[Get(path: '/project/{id}/edit')]
    public function edit(string $id)
    {
        debug($id);
        return view("Project.edit", [
            'project' => Project::byId($id)
        ]);
    }

    #[Get(path: '/project/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("Project.formEdit", [
            'project' => Project::byId($id)
        ]);
    }

    #[Post(path: '/project')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::table("project")
                ->where("idProject", $data->idProject)
                ->update($data->toArray());
            $this->trigger("reload-gridProject");
            return $this->renderNotify("success", "Project updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/project/new')]
    public function create(CreateData $user)
    {
        try {
            Criteria::table("project")
                ->insert($user->toArray());
            $this->trigger("reload-gridProject");
            return $this->renderNotify("success", "Project created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/project/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::deleteById("project", "idProject", $id);
            return $this->clientRedirect("/project");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

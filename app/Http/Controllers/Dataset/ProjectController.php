<?php

namespace App\Http\Controllers\Dataset;

use App\Data\Dataset\CreateData;
use App\Data\Dataset\ProjectData;
use App\Data\Dataset\SearchData;
use App\Data\Dataset\UpdateData;
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
class ProjectController extends Controller
{
    #[Get(path: '/dataset/{id}/projects')]
    public function projects(int $id)
    {
        return view("Dataset.projects", [
            'idDataset' => $id
        ]);
    }

    #[Get(path: '/dataset/{id}/projects/formNew')]
    public function projectsFormNew(int $id)
    {
        return view("Dataset.projectsNew", [
            'idDataset' => $id
        ]);
    }

    #[Get(path: '/dataset/{id}/projects/grid')]
    public function projectsGrid(int $id)
    {
        $projects = Criteria::table("project")
            ->join("project_dataset", "project.idProject", "=", "project_dataset.idProject")
            ->where("project_dataset.idDataset", $id)
            ->all();
        return view("Dataset.projectsGrid", [
            'idDataset' => $id,
            'projects' => $projects
        ]);
    }

    #[Post(path: '/dataset/{id}/projects/new')]
    public function projectsNew(ProjectData $data)
    {
        Criteria::table("project_dataset")->insert($data->toArray());
        $this->trigger('reload-gridDatasetProjects');
        return $this->renderNotify("success", "Dataset added to project.");
    }

    #[Delete(path: '/dataset/{id}/projects/{idProject}')]
    public function delete(int $id, int $idProject)
    {
        try {
            Criteria::table("project_dataset")
                ->where("idProject", $idProject)
                ->where("idDataset", $id)
                ->delete();
            $this->trigger('reload-gridDatasetProjects');
            return $this->renderNotify("success", "Dataset removed from project.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

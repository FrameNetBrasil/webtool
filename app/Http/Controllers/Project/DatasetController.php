<?php

namespace App\Http\Controllers\Project;

use App\Data\Project\DatasetData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class DatasetController extends Controller
{
    #[Get(path: '/project/{id}/datasets')]
    public function datasets(int $id)
    {
        return view("Project.datasets", [
            'idProject' => $id
        ]);
    }

    #[Get(path: '/project/{id}/datasets/formNew')]
    public function datasetsFormNew(int $id)
    {
        return view("Project.datasetsNew", [
            'idProject' => $id
        ]);
    }

    #[Get(path: '/project/{id}/datasets/grid')]
    public function datasetsGrid(int $id)
    {
        $datasets = Criteria::table("project_dataset")
            ->join("dataset", "project_dataset.idDataset", "=", "dataset.idDataset")
            ->select("dataset.*", "project_dataset.idProject")
            ->where("project_dataset.idProject", $id)
            ->all();
        return view("Project.datasetsGrid", [
            'idProject' => $id,
            'datasets' => $datasets
        ]);
    }

    #[Post(path: '/project/datasets/new')]
    public function datasetsNew(DatasetData $data)
    {
        debug($data);
        $idDataset = $data->idDataset;
        if ($idDataset == 0) {
            $idDataset = Criteria::create("dataset", [
                "name" => $data->name,
                "description" => $data->description,
            ]);
        }
        Criteria::create("project_dataset", [
            "idDataset" => $idDataset,
            "idProject" => $data->idProject
        ]);
        $this->trigger('reload-gridDatasets');
        return $this->renderNotify("success", "Dataset added to project.");
    }

    #[Delete(path: '/project/{idProject}/datasets/{idDataset}')]
    public function datasetsDelete(int $idProject, int $idDataset)
    {
        Criteria::table("project_dataset")
            ->where("idProject", $idProject)
            ->where("idDataset", $idDataset)
            ->delete();
        $this->trigger('reload-gridDatasets');
        return $this->renderNotify("success", "Dataset removed from project.");
    }

}

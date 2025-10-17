<?php

namespace App\Http\Controllers\Dataset;

use App\Data\Dataset\CreateData;
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
class ResourceController extends Controller
{
    #[Get(path: '/dataset')]
    public function resource()
    {
        return view("Dataset.resource");
    }

    #[Get(path: '/dataset/new')]
    public function new()
    {
        return view("Dataset.formNew");
    }

    #[Get(path: '/dataset/grid/{fragment?}')]
    #[Post(path: '/dataset/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $datasets = Dataset::listToGrid($search);
        //debug($users);
        $projects = Dataset::listProjectForGrid($search?->project ?? '');
        $view = view("Dataset.grid",[
            'projects' => $projects,
            'datasets' => $datasets
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    #[Get(path: '/dataset/{id}/edit')]
    public function edit(string $id)
    {
        return view("Dataset.edit",[
            'dataset' => Dataset::byId($id)
        ]);
    }

    #[Get(path: '/dataset/{id}/formEdit')]
    public function formEdit(string $id)
    {
        debug(Dataset::byId($id));
        return view("Dataset.formEdit",[
            'dataset' => Dataset::byId($id)
        ]);
    }

    #[Post(path: '/dataset')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::function('dataset_update(?)', [$data->toJson()]);
            $this->trigger("reload-gridProject");
            return $this->renderNotify("success", "Dataset updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/dataset/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::function('dataset_create(?)', [$data->toJson()]);
            $this->trigger("reload-gridDatasets");
            return $this->renderNotify("success", "Dataset created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/dataset/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::function('dataset_delete(?, ?)', [
                $id,
                AppService::getCurrentIdUser()
            ]);
            return $this->clientRedirect("/project");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

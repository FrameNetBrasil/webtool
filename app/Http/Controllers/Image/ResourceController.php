<?php

namespace App\Http\Controllers\Image;

use App\Data\ComboBox\QData;
use App\Data\Image\CreateData;
use App\Data\Image\SearchData;
use App\Data\Image\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Image;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/image')]
    public function resource()
    {
        return view("Image.resource");
    }

    #[Get(path: '/image/grid/{fragment?}')]
    #[Post(path: '/image/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $view = view("Image.grid", [
            'search' => $search
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    #[Get(path: '/image/data')]
    public function data(SearchData $search)
    {
        if ($search->id != 0) {
            $data = Criteria::table("image")
                ->join("dataset_image as di", "image.idImage", "=", "di.idImage")
                ->join("dataset", "di.idDataset", "=", "dataset.idDataset")
                ->where("dataset.idDataset", $search->id)
                ->select('image.idImage', 'image.name')
                ->selectRaw("concat('i',image.idImage) as id")
                ->selectRaw("'' as dataset")
                ->selectRaw("'open' as state")
                ->selectRaw("'image' as type")
                ->limit(1000)
                ->orderBy("image.name")->all();
        } else {
            if ($search->image == '') {
                $data = Criteria::table("dataset")
                    ->select("idDataset as id", "idDataset", "name")
                    ->selectRaw("'closed' as state")
                    ->selectRaw("'dataset' as type")
                    ->where("name", "startswith", $search->dataset)
                    ->orderBy("name")
                    ->all();
            } else {
                $data = Criteria::table("image")
                    ->leftJoin("dataset_image as di", "image.idImage", "=", "di.idImage")
                    ->leftJoin("dataset", "di.idDataset", "=", "dataset.idDataset")
                    ->select('image.idImage', 'image.name')
                    ->selectRaw("concat('i',image.idImage) as id")
                    ->selectRaw("IFNULL(concat(' [',dataset.name,']'),' []') as dataset")
                    ->selectRaw("'open' as state")
                    ->selectRaw("'image' as type")
                    ->where("image.name", "startswith", $search->image)
                    ->limit(1000)
                    ->orderBy("image.name")->all();
            }
        }
        return $data;
    }

    #[Get(path: '/image/{id}/edit')]
    public function edit(string $id)
    {
        return view("Image.edit",[
            'image' => Image::byId($id)
        ]);
    }

    #[Get(path: '/image/{id}/editForm')]
    public function editForm(string $id)
    {
        return view("Image.editForm",[
            'image' => Image::byId($id)
        ]);
    }

    #[Post(path: '/image')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::table("image")
                ->where("idImage",$data->idImage)
                ->update([
                    "name" => $data->name,
                    "currentURL" => $data->currentURL,
                ]);
            $this->trigger("reload-gridImage");
            return $this->renderNotify("success", "Image updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/image/new')]
    public function new()
    {
        return view("Image.formNew");
    }

    #[Post(path: '/image/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::function('image_create(?)', [$data->toJson()]);
            $this->trigger("reload-gridImage");
            return $this->renderNotify("success", "Image created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/image/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::deleteById("image","idImage",$id);
            return $this->clientRedirect("/image");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/image/listForSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';
        return ['results' => Criteria::byFilter("image",["name","startswith",$name])->orderby("name")->all()];
    }


}

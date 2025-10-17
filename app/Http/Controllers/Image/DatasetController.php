<?php

namespace App\Http\Controllers\Image;

use App\Data\Image\DatasetData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Image;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class DatasetController extends Controller
{
    #[Get(path: '/image/{id}/dataset')]
    public function dataset(int $id)
    {
        return view("Image.dataset", [
            'image' => Image::byId($id)
        ]);
    }

    #[Get(path: '/image/{id}/dataset/formNew')]
    public function datasetFormNew(int $id)
    {
        return view("Image.datasetNew", [
            'idImage' => $id
        ]);
    }

    #[Get(path: '/image/{id}/dataset/grid')]
    public function datasetGrid(int $id)
    {
        $datasets = Criteria::table("dataset_image as di")
            ->join("dataset as d", "di.idDataset", "=", "d.idDataset")
            ->where("di.idImage", $id)
            ->all();
        return view("Image.datasetGrid", [
            'idImage' => $id,
            'datasets' => $datasets
        ]);
    }

    #[Post(path: '/image/{id}/dataset/new')]
    public function datasetNew(DatasetData $data)
    {
        Criteria::create("dataset_image",[
            "idDataset" => $data->idDataset,
            "idImage" => $data->idImage,
        ]);
        $this->trigger('reload-gridImageDataset');
        return $this->renderNotify("success", "Image associated with Dataset.");
    }

    #[Delete(path: '/image/{id}/dataset/{idDataset}')]
    public function delete(int $id, int $idDataset)
    {
        try {
            Criteria::table("dataset_image")
                ->where("idDataset", $idDataset)
                ->where("idImage", $id)
                ->delete();
            $this->trigger('reload-gridImageDataset');
            return $this->renderNotify("success", "Image removed from Dataset.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

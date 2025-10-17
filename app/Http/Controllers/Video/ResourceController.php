<?php

namespace App\Http\Controllers\Video;

use App\Data\ComboBox\QData;
use App\Data\Video\CreateData;
use App\Data\Video\SearchData;
use App\Data\Video\UpdateData;
use App\Data\Video\UploadData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Corpus;
use App\Repositories\Video;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/video')]
    public function resource()
    {
        return view("Video.resource");
    }

    #[Get(path: '/video/grid/{fragment?}')]
    #[Post(path: '/video/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        $view = view("Video.grid", [
            'search' => $search
        ]);
        return (is_null($fragment) ? $view : $view->fragment('search'));
    }

    #[Get(path: '/video/{id}/edit')]
    public function edit(string $id)
    {
        return view("Video.edit", [
            'video' => Video::byId($id)
        ]);
    }

    #[Get(path: '/video/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("Video.formEdit", [
            'video' => Video::byId($id)
        ]);
    }

    #[Post(path: '/video')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::table("video")
                ->where("idVideo", $data->idVideo)
                ->update([
                    "title" => $data->title,
                    "originalFile" => $data->originalFile,
                ]);
            $this->trigger("reload-gridVideo");
            return $this->renderNotify("success", "Video updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/video/{id}/formUpload')]
    public function formUpload(string $id)
    {
        return view("Video.formUpload", [
            'video' => Video::byId($id)
        ]);
    }

    #[Post(path: '/video/upload')]
    public function upload(UploadData $data)
    {
        try {
            Criteria::table("video")
                ->where("idVideo", $data->idVideo)
                ->update([
                    "currentURL" => $data->currentURL,
//                    "originalFile" => $data->originalFile,
//                    'sha1Name' => $data->sha1Name
                ]);
            return $this->renderNotify("success", "Video uploaded.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/video/new')]
    public function new()
    {
        return view("Video.formNew");
    }

    #[Post(path: '/video/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::function('video_create(?)', [$data->toJson()]);
            $this->trigger("reload-gridVideo");
            return $this->renderNotify("success", "Video created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/video/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::function('video_delete(?, ?)', [
                $id,
                AppService::getCurrentIdUser()
            ]);
            return $this->clientRedirect("/video");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/video/listForSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';
        return ['results' => Criteria::byFilter("video", ["title", "startswith", $name])->orderby("title")->all()];
    }


}

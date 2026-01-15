<?php

namespace App\Http\Controllers\Class;

use App\Data\Class\CreateData;
use App\Data\Class\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Class_;
use App\Services\AppService;
use App\Services\Class\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class ResourceController extends Controller
{
    #[Get(path: '/class')]
    public function index(SearchData $search)
    {
        $frames = BrowseService::browseClassBySearch($search);

        return view('Class.browse', [
            'data' => $frames,
        ]);
    }

    #[Post(path: '/class/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseClassBySearch($search);

        return view('Class.browse', [
            'data' => $data,
        ])->fragment('search');

    }

    #[Get(path: '/class/new')]
    public function new()
    {
        return view('Class.new');
    }

    #[Post(path: '/class')]
    public function store(CreateData $data)
    {
        try {
            $idFrame = Criteria::function('frame_create(?)', [$data->toJson()]);

            return $this->clientRedirect("/class/{$idFrame}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/class/{idFrame}')]
    public function delete(string $idFrame)
    {
        try {
            Criteria::function('frame_delete(?, ?)', [
                $idFrame,
                AppService::getCurrentIdUser(),
            ]);

            return $this->clientRedirect('/class');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/class/{id}')]
    public function get(string $id)
    {
        return view('Class.edit', [
            'frame' => Class_::byId($id),
        ]);
    }

    #[Get(path: '/class/nextFrom/{id}')]
    public function nextFrom(string $id)
    {
        $current = Class_::byId($id);
        $next = Criteria::table('view_class')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->where('name', '>', $current->name)
            ->orderBy('name')
            ->first();

        return $this->clientRedirect("/class/{$next->idFrame}");
    }
}

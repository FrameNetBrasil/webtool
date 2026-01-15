<?php

namespace App\Http\Controllers\Microframe;

use App\Data\Microframe\CreateData;
use App\Data\Microframe\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Repositories\Microframe;
use App\Repositories\SemanticType;
use App\Services\AppService;
use App\Services\Microframe\BrowseService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class ResourceController extends Controller
{
    #[Get(path: '/microframe')]
    public function index(SearchData $search)
    {
        $frames = BrowseService::browseMicroFrameBySearch($search);

        return view('Microframe.browse', [
            'data' => $frames,
        ]);
    }

    #[Post(path: '/microframe/search')]
    public function tree(SearchData $search)
    {
        $data = BrowseService::browseMicroFrameBySearch($search);

        return view('Microframe.browse', [
            'data' => $data,
        ])->fragment('search');

    }

    #[Get(path: '/microframe/new')]
    public function new()
    {
        return view('Microframe.new');
    }

    #[Post(path: '/microframe')]
    public function store(CreateData $data)
    {
        try {
            debug($data);
            $idFrame = Criteria::function('frame_create(?)', [$data->toJson()]);
            $microframe = Microframe::byId($idFrame);
            $semanticType = SemanticType::byId($data->idSemanticType);
            RelationService::createMicroframe("subsumption", $microframe->idEntity, $semanticType->idEntity);
            return $this->renderNotify("success", "Microframe created.");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/microframe/{idFrame}')]
    public function delete(string $idFrame)
    {
        try {
            Criteria::function('frame_delete(?, ?)', [
                $idFrame,
                AppService::getCurrentIdUser(),
            ]);

            return $this->clientRedirect('/microframe');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/microframe/{id}')]
    public function get(string $id)
    {
        return view('Microframe.edit', [
            'frame' => Microframe::byId($id),
        ]);
    }

    #[Get(path: '/microframe/nextFrom/{id}')]
    public function nextFrom(string $id)
    {
        $current = Microframe::byId($id);
        $next = Criteria::table('view_microframe')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->where('name', '>', $current->name)
            ->orderBy('name')
            ->first();

        return $this->clientRedirect("/microframe/{$next->idFrame}");
    }
}

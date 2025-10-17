<?php

namespace App\Http\Controllers\Frame;

use App\Data\CreateFrameData;
use App\Data\CreateRelationFEInternalData;
use App\Data\Frame\CreateData;
use App\Data\UpdateFrameClassificationData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\FEController;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Services\AppService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/frame/new')]
    public function new()
    {
        return view("Frame.new");
    }

    #[Post(path: '/frame')]
    public function store(CreateData $data)
    {
        try {
            $idFrame = Criteria::function('frame_create(?)', [$data->toJson()]);
            return $this->clientRedirect("/frame/{$idFrame}");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/frame/{idFrame}')]
    public function delete(string $idFrame)
    {
        try {
            Criteria::function('frame_delete(?, ?)', [
                $idFrame,
                AppService::getCurrentIdUser()
            ]);
            return $this->clientRedirect("/frame");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/frame/{id}')]
    public function get(string $id)
    {
        return view("Frame.edit",[
            'frame' => Frame::byId($id),
            'classification' => Frame::getClassificationLabels($id)
        ]);
    }

}

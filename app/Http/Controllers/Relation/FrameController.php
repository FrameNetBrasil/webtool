<?php

namespace App\Http\Controllers\Relation;

use App\Data\Relation\FrameData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class FrameController extends Controller
{
    #[Delete(path: '/relation/frame/{idEntityRelation}')]
    public function deleteFrameRelation(string $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation","idRelation", $idEntityRelation);
            Criteria::deleteById("entityrelation","idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridFrameRelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", "Deletion denied. Check for associated relations.");
        }
    }

    #[Post(path: '/relation/frame')]
    public function newFrameRelation(FrameData $data)
    {
        try {
            $frame = Frame::byId($data->idFrame);
            $frameRelated = Frame::byId($data->idFrameRelated);
            if ($data->direction == 'd') {
                RelationService::create($data->relationTypeEntry, $frame->idEntity, $frameRelated->idEntity);
            } else {
                RelationService::create($data->relationTypeEntry, $frameRelated->idEntity, $frame->idEntity);
            }
            $this->trigger('reload-gridFrameRelation');
            return $this->renderNotify("success", "Relation created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

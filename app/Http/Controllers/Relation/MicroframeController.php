<?php

namespace App\Http\Controllers\Relation;

use App\Data\Relation\ClassData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Class_;
use App\Repositories\Microframe;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class MicroframeController extends Controller
{
    #[Delete(path: '/relation/microframe/{idEntityRelation}')]
    public function deleteFrameRelation(string $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation","idRelation", $idEntityRelation);
            Criteria::deleteById("entityrelation","idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridMicroframeRelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", "Deletion denied. Check for associated relations.");
        }
    }

    #[Post(path: '/relation/microframe')]
    public function newFrameRelation(ClassData $data)
    {
        try {
            debug($data);
            $domain = Microframe::byId($data->idFrame);
            $range = Microframe::byId($data->idFrameRelated);
            $microframe = Microframe::byIdEntity($data->idEntityMicroframe);
            RelationService::createMicroframe($microframe->name, $domain->idEntity, $range->idEntity);
            $this->trigger('reload-gridMicroframeRelation');
            debug($data);
            return $this->renderNotify("success", "Relation created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

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
class ClassController extends Controller
{
    #[Delete(path: '/relation/class/{idEntityRelation}')]
    public function deleteFrameRelation(string $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation","idRelation", $idEntityRelation);
            Criteria::deleteById("entityrelation","idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridClassRelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", "Deletion denied. Check for associated relations.");
        }
    }

    #[Post(path: '/relation/class')]
    public function newFrameRelation(ClassData $data)
    {
        try {
            $domain = Class_::byId($data->idFrame);
            $range = Class_::byId($data->idFrameRelated);
            $microframe = Microframe::byIdEntity($data->idEntityMicroframe);
            RelationService::createMicroframe($microframe->name, $domain->idEntity, $range->idEntity);
            $this->trigger('reload-gridClassRelation');
            debug($data);
            return $this->renderNotify("success", "Relation created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

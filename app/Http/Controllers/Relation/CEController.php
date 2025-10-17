<?php

namespace App\Http\Controllers\Relation;

use App\Data\Relation\CEData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\ConstructionElement;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class CEController extends Controller
{
    #[Delete(path: '/relation/ce/{idEntityRelation}')]
    public function deleteFERelation(string $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation","idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridCERelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/relation/ce')]
    public function newCERelation(CEData $data)
    {
        try {
            $relation = Criteria::byId("view_relation","idEntityRelation", $data->idEntityRelation);
            $ce = ConstructionElement::byId($data->idConstructionElement);
            $ceRelated = ConstructionElement::byId($data->idConstructionElementRelated);
            RelationService::create($relation->relationType, $ce->idEntity, $ceRelated->idEntity, null, $data->idEntityRelation);
            $this->trigger('reload-gridCERelation');
            return $this->renderNotify("success", "Relation created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

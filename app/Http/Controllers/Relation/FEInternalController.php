<?php

namespace App\Http\Controllers\Relation;

use App\Data\Relation\FEInternalData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\FrameElement;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class FEInternalController extends Controller
{
    #[Delete(path: '/relation/feinternal/{idEntityRelation}')]
    public function deleteFERelation(int $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation", "idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridFEInternalRelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/relation/feinternal')]
    public function newFERelation(FEInternalData $data)
    {
        debug($data);
        try {
            $idFrameElementRelated = (array)$data->idFrameElementRelated;
            if (count($idFrameElementRelated)) {
                $idFirst = array_shift($idFrameElementRelated);
                $first = FrameElement::byId($idFirst);
                foreach ($idFrameElementRelated as $idNext) {
                    $next = FrameElement::byId($idNext);
                    RelationService::create($data->relationTypeEntry, $first->idEntity, $next->idEntity);
                }
            }
            $this->notify("success", "Relation created.");
            $this->trigger('reload-gridFEInternalRelation');
            return $this->render(
                "Relation.feInternalFormNew", [
                'idFrame' => $data->idFrame,
                'idFrameElementRelated' => $data->idFrameElementRelated,
                'relationType' => $data->relationTypeFEInternal
            ]);

        } catch (\Exception $e) {
            debug($e->getMessage());
            return $this->renderNotify("error", $e->getMessage());
        }
    }
}

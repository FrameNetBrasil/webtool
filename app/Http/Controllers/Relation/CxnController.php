<?php

namespace App\Http\Controllers\Relation;

use App\Data\Relation\CxnData;
use App\Data\Relation\FrameData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Construction;
use App\Repositories\Frame;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class CxnController extends Controller
{
    #[Delete(path: '/relation/cxn/{idEntityRelation}')]
    public function deleteFrameRelation(string $idEntityRelation)
    {
        try {
            Criteria::deleteById("entityrelation","idRelation", $idEntityRelation);
            Criteria::deleteById("entityrelation","idEntityRelation", $idEntityRelation);
            $this->trigger('reload-gridCxnRelation');
            return $this->renderNotify("success", "Relation deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", "Deletion denied. Check for associated relations.");
        }
    }

    #[Post(path: '/relation/cxn')]
    public function newFrameRelation(CxnData $data)
    {
        try {
            $cxn = Construction::byId($data->idConstruction);
            $cxnRelated = Construction::byId($data->idCxnRelated);
            if ($data->direction == 'd') {
                RelationService::create($data->relationTypeEntry, $cxn->idEntity, $cxnRelated->idEntity);
            } else {
                RelationService::create($data->relationTypeEntry, $cxnRelated->idEntity, $cxn->idEntity);
            }
            $this->trigger('reload-gridCxnRelation');
            return $this->renderNotify("success", "Relation created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

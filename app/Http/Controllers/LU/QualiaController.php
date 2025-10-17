<?php

namespace App\Http\Controllers\LU;

use App\Data\LU\ConstraintData as LUConstraintData;
use App\Data\LU\QualiaData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Constraint;
use App\Repositories\LU;
use App\Repositories\Qualia;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class QualiaController extends Controller
{
    #[Get(path: '/lu/{id}/qualia')]
    public function qualia(string $id)
    {
        return view("Qualia.LU.child",[
            'idLU' => $id
        ]);
    }

    #[Get(path: '/lu/{id}/qualia/formNew/{fragment?}')]
    public function qualiaFormNew(int $id, ?string $fragment = null)
    {
        $view = view("Qualia.LU.formNew", [
            'idLU' => $id,
            'lu' => LU::byId($id),
            'fragment' => $fragment ?? ''
        ]);
        return (is_null($fragment) ? $view : $view->fragment($fragment));
    }

    #[Get(path: '/lu/{id}/qualia/grid')]
    public function qualiaGrid(int $id)
    {
        $lu = LU::byId($id);
        return view("Qualia.LU.grid", [
            'idLU' => $id,
            'qualiaRelations' => Qualia::listByLU($lu->idEntity)
        ]);
    }

    #[Post(path: '/lu/qualia/{id}')]
    public function qualiaLU(QualiaData $data)
    {
        try {
            $lu = LU::byId($data->idLU);
            if ($data->idQualiaRelation > 0 ) {
                $qualia = Qualia::byIdRaw($data->idQualiaRelation);
                $luRelated = LU::byId($data->idLURelated);
                RelationService::create('rel_qualia', $lu->idEntity, $luRelated->idEntity, $qualia->idEntity);
            }
            $this->trigger('reload-gridQualiaLU');
            return $this->renderNotify("success", "Constraint created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }
    #[Delete(path: '/lu/qualia/{idEntityRelation}')]
    public function deleteQualiaRelation(int $idEntityRelation)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idEntityRelation)->delete();
            $this->trigger('reload-gridQualiaLU');
            return $this->renderNotify("success", "Qualia deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}

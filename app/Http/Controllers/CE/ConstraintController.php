<?php

namespace App\Http\Controllers\CE;

use App\Http\Controllers\Controller;
use App\Repositories\Constraint;
use App\Repositories\Construction;
use App\Repositories\ConstructionElement;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class ConstraintController extends Controller
{
    #[Get(path: '/ce/{id}/constraints')]
    public function constraints(string $id)
    {
        return view("Constraint.ceChild", [
            'idConstructionElement' => $id,
        ]);
    }

    #[Get(path: '/ce/{id}/constraints/formNew')]
    public function constraintsFormNew(int $id)
    {
        $ce = ConstructionElement::byId($id);
        $evokedFrame = Construction::getEvokedFrame($ce->idConstruction);
        $view = view("Constraint.ceFormNew", [
            'idConstructionElement' => $id,
            'constructionElement' => ConstructionElement::byId($id),
            'idEvokedFrame' => $evokedFrame?->idFrame ?? 0
        ]);
        return $view;
    }

    #[Get(path: '/ce/{id}/constraints/grid')]
    public function constraintsGrid(int $id)
    {
        $ce = ConstructionElement::byId($id);
        return view("Constraint.ceGrid", [
            'idConstructionElement' => $id,
            'constraints' => Constraint::listByIdConstrained($ce->idEntity)
        ]);
    }

}

<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Repositories\Constraint;
use App\Repositories\Construction;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class ConstraintController extends Controller
{
    #[Get(path: '/cxn/{id}/constraints')]
    public function constraints(string $id)
    {
        return view("Constraint.cxnChild", [
            'idConstruction' => $id
        ]);
    }

    #[Get(path: '/cxn/{id}/constraints/formNew')]
    public function constraintsFormNew(int $id)
    {
        $view = view("Constraint.cxnFormNew", [
            'idConstruction' => $id,
            'construction' => Construction::byId($id)
        ]);
        return $view;
    }

    #[Get(path: '/cxn/{id}/constraints/grid')]
    public function constraintsGrid(int $id)
    {
        $cxn = Construction::byId($id);
        return view("Constraint.cxnGrid", [
            'idConstruction' => $id,
            'constraints' => Constraint::listByIdConstrained($cxn->idEntity)
        ]);
    }

}

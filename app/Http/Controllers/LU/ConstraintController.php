<?php

namespace App\Http\Controllers\LU;

use App\Http\Controllers\Controller;
use App\Repositories\Constraint;
use App\Repositories\LU;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class ConstraintController extends Controller
{
    #[Get(path: '/lu/{id}/constraints')]
    public function constraints(string $id)
    {
        return view("Constraint.luChild",[
            'idLU' => $id
        ]);
    }

    #[Get(path: '/lu/{id}/constraints/formNew/{fragment?}')]
    public function constraintsFormNew(int $id, ?string $fragment = null)
    {
        $view = view("Constraint.luFormNew", [
            'idLU' => $id,
            'lu' => LU::byId($id),
            'fragment' => $fragment ?? ''
        ]);
        return (is_null($fragment) ? $view : $view->fragment($fragment));
    }

    #[Get(path: '/lu/{id}/constraints/grid')]
    public function constraintsGrid(int $id)
    {
        $lu = LU::byId($id);
        return view("Constraint.luGrid", [
            'idLU' => $id,
            'constraints' => Constraint::listByIdConstrained($lu->idEntity)
        ]);
    }


}

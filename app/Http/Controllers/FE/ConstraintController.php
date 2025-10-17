<?php

namespace App\Http\Controllers\FE;

use App\Data\CreateFEData;
use App\Data\FE\UpdateData;
use App\Http\Controllers\Controller;
use App\Repositories\Constraint;
use App\Repositories\EntityRelation;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\ViewConstraint;
use App\Repositories\ViewFrameElement;
use App\Services\AppService;
use App\Services\EntryService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class ConstraintController extends Controller
{
    #[Get(path: '/fe/{id}/constraints')]
    public function constraints(string $id)
    {
        return view("Constraint.feChild", [
            'idFrameElement' => $id
        ]);
    }

    #[Get(path: '/fe/{id}/constraints/formNew')]
    public function constraintsFormNew(int $id)
    {
        $view = view("Constraint.feFormNew", [
            'idFrameElement' => $id,
            'frameElement' => FrameElement::byId($id)
        ]);
        return $view;
    }

    #[Get(path: '/fe/{id}/constraints/grid')]
    public function constraintsGrid(int $id)
    {
        $fe = FrameElement::byId($id);
        return view("Constraint.feGrid", [
            'idFrameElement' => $id,
            'constraints' => Constraint::listByIdConstrained($fe->idEntity)
        ]);
    }

}

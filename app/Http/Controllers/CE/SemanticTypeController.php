<?php

namespace App\Http\Controllers\CE;

use App\Http\Controllers\Controller;
use App\Repositories\ConstructionElement;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class SemanticTypeController extends Controller
{
    #[Get(path: '/ce/{id}/semanticTypes')]
    public function semanticTypes(string $id)
    {
        $ce = ConstructionElement::byId($id);
        return view("SemanticType.child", [
            'idEntity' => $ce->idEntity,
            'root' => "@ontological_type"
        ]);
    }

}

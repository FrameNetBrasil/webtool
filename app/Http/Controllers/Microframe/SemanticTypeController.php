<?php

namespace App\Http\Controllers\Microframe;

use App\Http\Controllers\Controller;
use App\Repositories\Microframe;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class SemanticTypeController extends Controller
{
    #[Get(path: '/microframe/{id}/semanticTypes')]
    public function semanticTypes(string $id)
    {
        $frame = Microframe::byId($id);
        return view("SemanticType.child", [
            'idEntity' => $frame->idEntity,
            'root' => "microframe_type"
        ]);
    }

}

<?php

namespace App\Http\Controllers\FE;

use App\Data\CreateFrameData;
use App\Data\CreateRelationFEInternalData;
use App\Data\SearchFrameData;
use App\Data\UpdateFrameClassificationData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\FEController;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Services\AppService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class SemanticTypeController extends Controller
{
    #[Get(path: '/fe/{id}/semanticTypes')]
    public function semanticTypes(string $id)
    {
        $fe = FrameElement::byId($id);
        return view("SemanticType.child", [
            'idEntity' => $fe->idEntity,
            'root' => "@ontological_type"
        ]);
    }

}

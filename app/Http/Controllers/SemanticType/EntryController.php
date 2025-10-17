<?php

namespace App\Http\Controllers\SemanticType;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\SemanticType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/semanticType/{id}/entries')]
    public function entries(string $id)
    {
        $semanticType = SemanticType::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($semanticType->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

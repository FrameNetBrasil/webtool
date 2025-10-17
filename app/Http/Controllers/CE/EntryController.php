<?php

namespace App\Http\Controllers\CE;

use App\Http\Controllers\Controller;
use App\Repositories\ConstructionElement;
use App\Repositories\Entry;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/ce/{id}/entries')]
    public function entries(string $id)
    {
        $ce = ConstructionElement::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($ce->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

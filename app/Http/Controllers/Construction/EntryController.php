<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Repositories\Construction;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/cxn/{id}/entries')]
    public function entries(string $id)
    {
        $cxn = Construction::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($cxn->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

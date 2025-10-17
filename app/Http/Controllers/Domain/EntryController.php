<?php

namespace App\Http\Controllers\Domain;

use App\Http\Controllers\Controller;
use App\Repositories\Domain;
use App\Repositories\Entry;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/domain/{id}/entries')]
    public function entries(string $id)
    {
        $domain = Domain::byId($id);
        return view("Entry.edit", [
            'trigger' => 'reload-gridDomain',
            'entries' => Entry::listByIdEntity($domain->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

<?php

namespace App\Http\Controllers\Corpus;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\Corpus;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/corpus/{id}/entries')]
    public function entries(string $id)
    {
        $corpus = Corpus::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($corpus->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

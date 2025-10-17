<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Repositories\Document;
use App\Repositories\Entry;
use App\Repositories\Corpus;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/document/{id}/entries')]
    public function entries(string $id)
    {
        $document = Document::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($document->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

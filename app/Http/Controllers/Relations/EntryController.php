<?php

namespace App\Http\Controllers\Relations;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\Corpus;
use App\Repositories\LayerType;
use App\Repositories\RelationGroup;
use App\Repositories\RelationType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/relations/relationgroup/{id}/entries')]
    public function entriesRGP(string $id)
    {
        $relationGroup = RelationGroup::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($relationGroup->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }

    #[Get(path: '/relations/relationtype/{id}/entries')]
    public function entriesRTY(string $id)
    {
        $relationType = RelationType::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($relationType->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }

}

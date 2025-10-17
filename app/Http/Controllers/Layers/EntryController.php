<?php

namespace App\Http\Controllers\Layers;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\Corpus;
use App\Repositories\LayerType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/layers/layertype/{id}/entries')]
    public function entries(string $id)
    {
        $layerType = LayerType::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($layerType->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

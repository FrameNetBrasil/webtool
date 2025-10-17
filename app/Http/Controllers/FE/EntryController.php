<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\FrameElement;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/fe/{id}/entries')]
    public function entries(string $id)
    {
        $frame = FrameElement::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($frame->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

<?php

namespace App\Http\Controllers\Frame;

use App\Http\Controllers\Controller;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/frame/{id}/entries')]
    public function entries(string $id)
    {
        $frame = Frame::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($frame->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

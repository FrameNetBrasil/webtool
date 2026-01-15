<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Repositories\Class_;
use App\Repositories\Entry;
use App\Repositories\Microframe;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class EntryController extends Controller
{
    #[Get(path: '/class/{id}/entries')]
    public function entries(string $id)
    {
        $frame = Class_::byId($id);
        return view("Entry.edit", [
            'entries' => Entry::listByIdEntity($frame->idEntity),
            'languages' => AppService::availableLanguages()
        ]);
    }
}

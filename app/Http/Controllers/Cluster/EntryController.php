<?php

namespace App\Http\Controllers\Cluster;

use App\Http\Controllers\Controller;
use App\Repositories\Cluster;
use App\Repositories\Entry;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('master')]
class EntryController extends Controller
{
    #[Get(path: '/cluster/{id}/entries')]
    public function entries(string $id)
    {
        $frame = Cluster::byId($id);

        return view('Entry.edit', [
            'entries' => Entry::listByIdEntity($frame->idEntity),
            'languages' => AppService::availableLanguages(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\UD;

use App\Data\UD\ParseInputData;
use App\Http\Controllers\Controller;
use App\Services\UD\ParserService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'web')]
class ParserController extends Controller
{
    #[Get(path: '/ud/parser')]
    public function parser()
    {
        return view('UD.parser');
    }

    #[Post(path: '/ud/parser')]
    public function parse(ParseInputData $data)
    {
        debug($data);
        $graph = ParserService::parse($data);

        return view('UD.parserGraph', [
            'graph' => $graph,
            'sentence' => $data->sentence,
        ]);
    }
}

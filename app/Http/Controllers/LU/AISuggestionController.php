<?php

namespace App\Http\Controllers\LU;

use App\Data\LU\AISuggestionData;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Services\LU\AISuggestionService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class AISuggestionController extends Controller
{

    #[Get(path: '/lu/aiSuggestion')]
    public function aiSuggestion()
    {
        return view("LU.AISuggestion.main",[
            'data' => []
        ]);
    }

    #[Post(path: '/lu/aiSuggestion')]
    public function getAiSuggestions(AISuggestionData $data)
    {
        try {
            debug($data);
            $results = [];
            if ($data->idFrame) {
                $frame = Frame::byId($data->idFrame);
                $results = AISuggestionService::handle($data);

            }
            return view("LU.AISuggestion.main", [
                'frame' => $frame,
                'data' => $results
            ])->fragment("search");
        } catch (\Exception $e) {
            $this->renderNotify("error","Error accessing API.");
        }
    }

}

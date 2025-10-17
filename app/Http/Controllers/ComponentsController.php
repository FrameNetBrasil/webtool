<?php

namespace App\Http\Controllers;

use App\Data\Components\FrameFEData;
use App\Database\Criteria;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'web')]
class ComponentsController extends Controller
{
    #[Get(path: '/components/fesByFrame')]
    public function feCombobox(FrameFEData $frame)
    {
        $frameElement = Criteria::table("view_frameelement")
            ->where("idFrame", $frame->idFrame)
            ->where("coreType","cty_core")
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->orderBy("name")
            ->first();
        return view('components.fesByFrame', [
            'idFrame' => $frame->idFrame,
            'idFrameElement' => $frameElement->idFrameElement,
        ]);
    }
}

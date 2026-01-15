<?php

namespace App\Services\Microframe;

use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static function browseMicroframeBySearch(object $search): array
    {
        $result = [];
        $microframes = Criteria::table('view_microframe as m')
            ->where('m.name', 'startswith', $search->microframe)
            ->where('m.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('name')->all();
        foreach ($microframes as $frame) {
            $result[$frame->idFrame] = [
                'id' => $frame->idFrame,
                'type' => 'frame',
                'text' => view('Microframe.partials.frame', ['frame' => $frame])->render(),
                'leaf' => true,
            ];
        }

        return $result;
    }
}

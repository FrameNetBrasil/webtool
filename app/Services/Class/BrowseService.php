<?php

namespace App\Services\Class;

use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static function browseClassBySearch(object $search): array
    {
        $result = [];
        $classes = Criteria::table('view_class as c')
            ->where('c.name', 'startswith', $search->class)
            ->where('c.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('name')->all();
        foreach ($classes as $class) {
            $result[$class->idFrame] = [
                'id' => $class->idFrame,
                'type' => 'frame',
                'text' => view('Class.partials.frame', ['frame' => $class])->render(),
                'leaf' => true,
            ];
        }

        return $result;
    }
}

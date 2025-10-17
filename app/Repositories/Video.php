<?php

namespace App\Repositories;

use App\Database\Criteria;

class Video
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("video", ["idVideo","=", $id])->first();
    }
}

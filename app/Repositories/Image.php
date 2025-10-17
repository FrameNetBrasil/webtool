<?php

namespace App\Repositories;

use App\Database\Criteria;

class Image
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("image", ["idImage","=", $id])->first();
    }
}

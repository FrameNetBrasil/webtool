<?php

namespace App\Repositories;

use App\Database\Criteria;

class UserTask
{
    public static function byId(int $id): ?object
    {
        return Criteria::byFilter("view_usertask", ["idUserTask","=", $id])->first();
    }

}

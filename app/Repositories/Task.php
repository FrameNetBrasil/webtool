<?php

namespace App\Repositories;

use App\Database\Criteria;

class Task
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("task", ["idTask","=", $id])->first();
    }

    public static function listUsersToGrid(string $user): array
    {
        $criteria = Criteria::table("user")
            ->join("usertask","user.idUser","=","usertask.idUser")
            ->join("task","usertask.idTask","=","task.idTask")
            ->distinct()
            ->select('usertask.idTask', 'user.idUser','usertask.idUserTask','user.name','user.email')
//            ->selectRaw("concat('#', user.idUser,'  ', user.name, ' [', user.email,']') as name")
            ->orderBy('task.name')
            ->orderBy('user.name');
        $criteria->orWhere('user.name', 'startswith', $user);
        return $criteria->get()->groupBy('idTask')->toArray();
    }

    public static function listToGrid(string $name = ''): array
    {
        return Criteria::table("task")
            ->select('idTask','name')
            ->where('name', 'startswith', $name)
            ->orderBy('name')
            ->keyBy('idTask')
            ->all();
    }
}

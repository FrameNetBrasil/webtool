<?php

namespace App\Services\Task;

use App\Database\Criteria;
use App\Data\Task\SearchData;

class BrowseService
{
    static int $limit = 300;

    public static function browseAllTasks(): array
    {
        $result = [];
        $tasks = Criteria::table("task")
            ->select("idTask", "name", "description")
            ->orderBy("name")
            ->limit(self::$limit)
            ->all();

        foreach ($tasks as $task) {
            $result[$task->idTask] = [
                'id' => $task->idTask,
                'type' => 'task',
                'text' => $task->name,
                'leaf' => false, // Tasks can be expanded to show users
                'state' => 'closed'
            ];
        }
        return $result;
    }

    public static function browseTaskBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->task != '') {
            $tasks = Criteria::table("task")
                ->where("name", "startswith", $search->task)
                ->select("idTask", "name", "description")
                ->orderBy("name")
                ->limit(self::$limit)
                ->all();

            foreach ($tasks as $task) {
                $result[$task->idTask] = [
                    'id' => $task->idTask,
                    'type' => 'task',
                    'text' => $task->name,
                    'leaf' => $leaf,
                    'state' => 'closed'
                ];
            }
        }
        return $result;
    }

    public static function browseUserBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->user != '') {
            $users = Criteria::table("usertask")
                ->join("user", "usertask.idUser", "=", "user.idUser")
                ->join("task", "usertask.idTask", "=", "task.idTask")
                ->where("user.name", "startswith", $search->user)
                ->select('usertask.idUserTask', 'user.idUser', 'user.name', 'user.email', 'task.name as taskName')
                ->orderBy("user.name")
                ->limit(self::$limit)
                ->all();

            foreach ($users as $user) {
                $result[$user->idUserTask] = [
                    'id' => $user->idUserTask,
                    'type' => 'user',
                    'text' => $user->name . ' [' . $user->taskName . ']',
                    'leaf' => true,
                    'state' => 'open'
                ];
            }
        }
        return $result;
    }

    public static function browseUsersByTask(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $users = Criteria::table("usertask")
                ->join("user", "usertask.idUser", "=", "user.idUser")
                ->where("usertask.idTask", $search->id)
                ->select('usertask.idUserTask', 'user.idUser', 'user.name', 'user.email')
                ->orderBy("user.name")
                ->limit(self::$limit)
                ->all();

            foreach ($users as $user) {
                $result[$user->idUserTask] = [
                    'id' => $user->idUserTask,
                    'type' => 'user',
                    'text' => $user->name . ' [' . $user->email . ']',
                    'leaf' => true,
                    'state' => 'open'
                ];
            }
        }
        return $result;
    }

    public static function browseTaskUserBySearch(SearchData $search): array
    {
        $result = [];

        // Handle tree expansion: if type is 'task' and id is provided, return users for that task
        if ($search->type === 'task' && $search->id != 0) {
            $result = self::browseUsersByTask($search);
        }
        // If searching for specific task ID (legacy behavior), return its users
        elseif ($search->id != 0 && $search->type === '') {
            $result = self::browseUsersByTask($search);
        } else {
            // If searching by user name, return matching users
            if ($search->user != '') {
                $result = self::browseUserBySearch($search);
            } else {
                // If searching by task name, return filtered tasks
                if ($search->task != '') {
                    $result = self::browseTaskBySearch($search);
                } else {
                    // Show all tasks by default
                    $result = self::browseAllTasks();
                }
            }
        }

        return $result;
    }
}

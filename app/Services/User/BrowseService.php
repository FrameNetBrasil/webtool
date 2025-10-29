<?php

namespace App\Services\User;

use App\Data\User\SearchData;
use App\Database\Criteria;

class BrowseService
{
    public static int $limit = 300;

    public static function browseAllGroups(): array
    {
        $result = [];
        $groups = Criteria::table('group')
            ->select('idGroup', 'name', 'description')
            ->orderBy('name')
            ->limit(self::$limit)
            ->all();

        foreach ($groups as $group) {
            $result[$group->idGroup] = [
                'id' => $group->idGroup,
                'type' => 'group',
                'text' => $group->name,
                'leaf' => false, // Groups can be expanded to show users
                'state' => 'closed',
            ];
        }

        return $result;
    }

    public static function browseGroupBySearch(SearchData $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->group != '') {
            $groups = Criteria::table('group')
                ->where('name', 'startswith', $search->group)
                ->select('idGroup', 'name', 'description')
                ->orderBy('name')
                ->limit(self::$limit)
                ->all();

            foreach ($groups as $group) {
                $result[$group->idGroup] = [
                    'id' => $group->idGroup,
                    'type' => 'group',
                    'text' => $group->name,
                    'leaf' => $leaf,
                    'state' => 'closed',
                ];
            }
        }

        return $result;
    }

    public static function browseUserBySearch(SearchData $search): array
    {
        $result = [];
        if ($search->user != '') {
            $users = Criteria::table('user')
                ->where(function ($query) use ($search) {
                    $query->where('login', 'startswith', $search->user)
                        ->orWhere('email', 'startswith', $search->user)
                        ->orWhere('name', 'startswith', $search->user);
                })
                ->select('idUser', 'login', 'email', 'name', 'status')
                ->orderBy('login')
                ->limit(self::$limit)
                ->all();

            foreach ($users as $user) {
                $statusText = ($user->status != '1') ? ' [Not authorized]' : '';
                $result[$user->idUser] = [
                    'id' => $user->idUser,
                    'type' => 'user',
                    'text' => $user->login.' ['.$user->email.']'.$statusText,
                    'leaf' => true,
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseUsersByGroup(SearchData $search): array
    {
        $result = [];
        if ($search->id > 0) {
            $users = Criteria::table('user_group')
                ->join('user', 'user_group.idUser', '=', 'user.idUser')
                ->where('user_group.idGroup', $search->id)
                ->select('user.idUser', 'user.login', 'user.email', 'user.status')
                ->orderBy('user.login')
                ->limit(self::$limit)
                ->all();

            foreach ($users as $user) {
                $statusText = ($user->status != '1') ? ' [Not authorized]' : '';
                $result[$user->idUser] = [
                    'id' => $user->idUser,
                    'type' => 'user',
                    'text' => $user->login.' ['.$user->email.']'.$statusText,
                    'leaf' => true,
                    'state' => 'open',
                ];
            }
        }

        return $result;
    }

    public static function browseGroupUserBySearch(SearchData $search): array
    {
        $result = [];

        // Handle tree expansion: if type is 'group' and id is provided, return users for that group
        if ($search->type === 'group' && $search->id != 0) {
            $result = self::browseUsersByGroup($search);
        }
        // If searching for specific group ID (legacy behavior), return its users
        elseif ($search->id != 0 && $search->type === '') {
            $result = self::browseUsersByGroup($search);
        } else {
            // If searching by user name, return matching users
            if ($search->user != '') {
                $result = self::browseUserBySearch($search);
            } else {
                // If searching by group name, return filtered groups
                if ($search->group != '') {
                    $result = self::browseGroupBySearch($search);
                } else {
                    // Show all groups by default
                    $result = self::browseAllGroups();
                }
            }
        }

        return $result;
    }
}

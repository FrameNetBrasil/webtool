<?php

namespace App\Repositories;

use App\Data\User\SearchData;
use App\Database\Criteria;
use App\Services\AppService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class User
{
    public static function byId(int $id): object
    {
        $user = Criteria::byId("user", 'idUser', $id);
        $user->groups = Criteria::byFilter("view_group_user", ['idUser', '=', $user->idUser])->all();
        foreach ($user->groups as $group) {
            $g = $group->name;
            $user->memberOf[$g] = $g;
        }
        return $user;
    }

    public static function registerLogin(object $user): void
    {
        $user->lastLogin = Carbon::now();
        DB::table("user")
            ->where("idUser", $user->idUser)
            ->update(["lastLogin" => $user->lastLogin]);
    }

    public static function authorize(int $idUser): void
    {
        DB::table("user")
            ->where("idUser", $idUser)
            ->update(["status" => 1]);
    }
    public static function deauthorize(int $idUser): void
    {
        DB::table("user")
            ->where("idUser", $idUser)
            ->update(["status" => 0]);
    }
    public static function getUserLevel(object $user)
    {
        $userLevel = '';
        $levels = AppService::userLevel();
        foreach ($user->groups as $group) {
            foreach ($levels as $level) {
                if ($group->name == $level) {
                    $userLevel = $level;
                    break 2;
                }
            }
        }
        return $userLevel;
    }

    public static function isAdmin(object $user)
    {
        return in_array('ADMIN', $user->memberOf);
    }

    public static function isManager(object $user)
    {
        return in_array('ADMIN', $user->memberOf) || in_array('MANAGER', $user->memberOf);
    }
    public static function isMemberOf(object $user, string $group)
    {
        return in_array(strtoupper($group), $user->memberOf) || static::isAdmin($user);
    }

    public static function create(object $user): void
    {
        $user->idUser = Criteria::function('user_create(?, ?)', [$user->login, $user->passMD5 ?? md5($user->login)]);
        if (isset($user->auth0IdUser)) {
            DB::table("user")
                ->where("idUser", $user->idUser)
                ->update([
                    "lastLogin" => $user->lastLogin ?? Carbon::now(),
                    'auth0IdUser' => $user->auth0IdUser,
                    'email' => $user->email,
                    'auth0CreatedAt' => $user->auth0CreatedAt ?? Carbon::now(),
                    'name' => $user->name
                ]);
        }
    }

    public static function listToGrid(SearchData $search): array
    {
        $criteria = Criteria::table("user")
            ->join("user_group","user.idUser","=","user_group.idUser")
            ->join("group","user_group.idGroup","=","group.idGroup")
            ->select('group.idGroup', 'user.idUser', 'user.login', 'user.name')
            ->selectRaw("if(user.status = 1,'authorized','pending') as status")
            ->orderBy('group.idGroup')
            ->orderBy('user.login');
        $criteria->where('group.name', 'startswith', $search?->group);
        $criteria->where(function (Criteria $c) use ($search) {
            $c->where('user.name', 'startswith', $search?->user)
                ->orWhere('user.email', 'startswith', $search?->user)
                ->orWhere('user.login', 'startswith', $search?->user);
        });
        return $criteria->get()->groupBy('idGroup')->toArray();
    }

    public static function listGroupForGrid(string $name = ''): array
    {
        return Criteria::table("group")
            ->select('idGroup','name')
            ->where('name', 'startswith', $name)
            ->orderBy('idGroup')
            ->keyBy('idGroup')
            ->all();
    }

//    public static function from(object $data): object
//    {
//        $model = clone $data;
//        $model->login ??= $data->email;
//        $model->active ??= 1;
//        $model->status ??= '0';
//        $model->idLanguage ??= AppService::getCurrentIdLanguage();
//        $model->groups = $data->groups ?? [];
//        return $model;
//    }
//
//
////    public function delete()
////    {
////        $this->deleteAssociation('groups');
////        parent::delete();
////    }
//
//    public static function listByFilter(object $filter): Builder
//    {
//        $criteria = static::getCriteria("user")
//            ->select('*')
//            ->orderBy('login');
//        return self::filter([
//            ['idUser', '=', $filter?->idUser ?? null],
//            ['login', 'startswith', $filter?->login ?? null],
//            ['passMD5', '=', $filter?->passMD5 ?? null],
//            ['name', 'startswith', $filter?->name ?? null],
//            ['email', 'startswith', $filter?->email ?? null],
//            ['status', '=', $filter?->status ?? null],
//        ], $criteria);
//    }
//
//
//
//    public static function create(object $user): ?int
//    {
//        PersistenceManager::beginTransaction();
//        try {
//            $user = static::from($user);
//            $idUser = $user->idUser = static::registerLogin($user);
//            static::saveAssociation($user, 'groups', $user->groups);
//            PersistenceManager::commit();
//            return $idUser;
//        } catch (\Exception $e) {
//            PersistenceManager::rollback();
//            return null;
//        }
//    }
//
//    public static function save(object $user): int
//    {
//        return Repository::save("user", $user, "idUser");
//    }
//
//    public static function update(object $data): void
//    {
//        PersistenceManager::beginTransaction();
//        try {
//            $data->groups = [Group::getById($data->idGroup)];
//            static::saveAssociation($data, 'groups', $data->groups);
//            static::save($data);
//            PersistenceManager::commit();
//        } catch (\Exception $e) {
//            PersistenceManager::rollback();
//        }
//    }
//    public static function registerLogin(object $user): int
//    {
//        $user->lastLogin = Carbon::now();
//        return static::save($user);
//    }
//
//    public static function authorize(int $idUser): void
//    {
//        $user = self::getById($idUser);
//        $user->status = 1;
//        self::save($user);
//    }
//
//    public static function getUserLevel(object $user)
//    {
//        $userLevel = '';
//        $levels = AppService::userLevel();
//        foreach ($user->groups as $group) {
//            foreach ($levels as $level) {
//                if ($group->name == $level) {
//                    $userLevel = $level;
//                    break 2;
//                }
//            }
//        }
//        return $userLevel;
//    }
//
//    public static function isAdmin(object $user)
//    {
//        return in_array('ADMIN', $user->memberOf);
//    }
//
//    public static function isMemberOf(object $user, string $group)
//    {
//        return in_array(strtoupper($group), $user->memberOf) || static::isAdmin($user);
//    }
//
////    public static function registerLogin()
////    {
////        $this->lastLogin = Carbon::now();
////        $this->save();
////    }
//
//
//    /*
//    public function getArrayGroups()
//    {
//        $aGroups = array();
//        if (empty($this->groups)) {
//            $this->retrieveAssociation('groups');
//        }
//        foreach ($this->groups as $group) {
//            $g = $group->name;
//            $aGroups[$g] = $g;
//        }
//        return $aGroups;
//    }
//
//    public function getRights()
//    {
//        $query = $this->getCriteria()
//            ->select(['groups.access.transaction.name', 'max(groups.access.rights) as rights'])
//            ->where("login", "=", $this->login)
//            ->groupBy('groups.access.transaction.name')
//            ->asQuery();
//        return $query->chunkResult('name', 'rights', false);
//    }
//
//    public function weakPassword()
//    {
//        $weak = ($this->passMD5 == MD5('010101')) || ($this->passMD5 == MD5($this->login));
//        return $weak;
//    }
//
//    public function resetPassword()
//    {
//        $this->newPassword(config('webtool.defaultPassword'));
//    }
//
//    public function newPassword($password)
//    {
//        $this->passMD5 = md5($password);
//        $this->save();
//    }
//
//    public function validatePassword($password)
//    {
//        return ($this->passMD5 == md5($password));
//    }
//
//    public function validatePasswordMD5($challenge, $response)
//    {
//        $hash_pass = MD5(trim($this->login) . ':' . trim($this->passMD5) . ":" . $challenge);
//        return ($hash_pass == $response);
//    }
//
//    public function getByLogin(string $login)
//    {
//        $criteria = $this->getCriteria()
//            ->where("login", "=", $login);
//        $this->retrieveFromCriteria($criteria);
//    }
//
//    public function listGroups()
//    {
//        $criteria = $this->getCriteria()
//            ->select("groups.idGroup,groups.name")
//            ->orderBy("groups.name");
//        if ($this->idUser) {
//            $criteria->where("idUser", "=", $this->idUser);
//        }
//        return $criteria;
//    }
//
//    public function getConfigData($attr)
//    {
//        $config = $this->config;
//        if ($config == '') {
//            $config = new \StdClass();
//            $config->$attr = '';
//        } else {
//            $config = unserialize($config);
//        }
//        return $config->$attr ?? null;
//    }
//
//    public function setConfigData($attr, $value)
//    {
//        $config = $this->config;
//        if ($config == '') {
//            $config = (object)[
//                $attr => ''
//            ];
//        } else {
//            $config = unserialize($config);
//        }
//        $config->$attr = $value;
//        $this->config = serialize($config);
//        parent::save();
//    }
//
//
//
//    public function getAvaiableLevels()
//    {
//        $levels = [];
//        $criteria = $this->getCriteria()
//            ->select('idUser')
//            ->where("idUser", "=", $this->idUser);
//        $users = $criteria->asQuery()->getResult();
//        foreach ($users as $row) {
//            $idUser = $row['idUser'];
//            $tempUser = new User($idUser);
//            $level = $tempUser->getUserLevel();
//            $levels[$level] = $idUser;
//        }
//        return $levels;
//    }
//
//    public function setUserLevel($userLevel)
//    {
//        $currentLevel = $this->getUserLevel();
//        if ($currentLevel != $userLevel) {
//            $newGroups = [];
//            $g = new Group();
//            $g->getByName($userLevel);
//            $newGroups[] = $g;
//            $this->groups = $newGroups;
//            $this->saveAssociation('groups');
//        }
//    }
//
//    public function getUsersOfLevel($level)
//    {
//        $criteria = $this->getCriteria()->select("idUser, login")
//            ->where("groups.name = '{$level}'")
//            ->orderBy("login");
//        return $criteria->asQuery()->chunkResult('idUser', 'login');
//    }
//
//    public function getUserSupervisedByIdLU($idLU)
//    {
//        $criteria = $this->getCriteria()->select('idUser,config');
//        $rows = $criteria->asQuery()->getResult();
//        foreach ($rows as $row) {
//            $config = unserialize($row['config']);
//            $lus = $config->fnbrConstraintsLU;
//            if ($lus) {
//                foreach ($lus as $id) {
//                    if ($idLU == $id) {
//                        $userSupervised = new User($row['idUser']);
//                        return $userSupervised;
//                    }
//                }
//            }
//        }
//        return NULL;
//    }
//
//
//
//    public function isAdmin()
//    {
//        return in_array('ADMIN', $this->memberOf);
//    }
//
//    public function isMemberOf($group)
//    {
//        return in_array(strtoupper($group), $this->memberOf) || $this->isAdmin();
//    }
//
//    public function save(): ?int
//    {
//        if ($this->passMD5 == '') {
//            $this->passMD5 = md5(config('webtool.defaultPassword'));
//        }
//        return parent::save();
//    }
//
//    public function addToGroup(int $idGroup)
//    {
//        $this->groups[$idGroup] = new Group($idGroup);
//        $this->saveAssociation('groups');
//    }
//
//    public function deleteFromGroup(int $idGroup)
//    {
//        unset($this->groups[$idGroup]);
//        $this->saveAssociation('groups');
//    }
//*/

}

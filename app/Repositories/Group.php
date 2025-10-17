<?php

namespace App\Repositories;

//use Orkester\Persistence\Criteria\Criteria;
//use Orkester\Persistence\Enum\Key;
//use Orkester\Persistence\Map\ClassMap;
//use Orkester\Persistence\Repository;

use App\Database\Criteria;

class Group
{
    public static function byId(int $id): object
    {
        return Criteria::byId("group", 'idGroup', $id);
    }
    public static function listForSelect(): array
    {
        return Criteria::table("group")
            ->select(['idGroup','name'])
            ->orderBy('name')
            ->all();
    }

//    public static function map(ClassMap $classMap): void
//    {
//        $classMap->table('group')
//            ->attribute('idGroup', key: Key::PRIMARY)
//            ->attribute('name')
//            ->attribute('description')
//            ->associationMany('users', model: 'User', associativeTable: 'user_group')
//            ->associationMany('access', model: 'Access', keys: 'idGroup');
//    }
//
//
//    public static function listByFilter(?object $filter = null): Criteria
//    {
//        $criteria = static::getCriteria()
//            ->select('*')
//            ->orderBy('idGroup');
//        return self::filter([
//            ['idGroup','=',$filter?->idGroup ?? null],
//        ], $criteria);
//    }
//    public static function getByName(string $name)
//    {
//        return self::first([
//            ["upper(name)", "=", strtoupper($name)]
//        ]);
//    }
//
//
//    public static function listForGrid(string $name = ''): array
//    {
//        return self::getCriteria()
//            ->select(['idGroup','name'])
//            ->where('name', 'startswith', $name)
//            ->orderBy('idGroup')
//            ->get()
//            ->keyBy('idGroup')
//            ->all();
//    }
//
//    public function listUser()
//    {
//        $criteria = $this->getCriteria()->select('users.idUser, users.login')->orderBy('users.login');
//        if ($this->idGroup) {
//            $criteria->where("idGroup = {$this->idGroup}");
//        }
//        return $criteria;
//    }
}


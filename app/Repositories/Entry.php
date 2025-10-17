<?php

namespace App\Repositories;

use App\Data\Entry\UpdateSingleData;
use App\Database\Criteria;

class Entry
{
    public static function listByIdEntity(int $idEntity): array
    {
        return Criteria::byFilter("entry", ["idEntity", "=", $idEntity])
            ->get()->keyBy('idLanguage')->all();
    }

    public static function deleteByIdEntity(int $idEntity): void
    {
        static::getCriteria()
            ->where('idEntity', '=', $idEntity)
            ->delete();
    }

    public static function create($entry, $name, $idEntity)
    {
        $languages = Language::list();
        foreach ($languages as $language) {
            $data = (object)[
                'entry' => $entry,
                'name' => $name,
                'description' => $name,
                'nick' => $name,
                'idLanguage' => $language->idLanguage,
                'idEntity' => $idEntity
            ];
            self::save($data);
        }
        Timeline::addTimeline("entry", $idEntity, "S");
    }

    public static function update(UpdateSingleData $object)
    {
        Criteria::table("entry")
            ->where("idEntry", "=", $object->idEntry)
            ->update($object->toArray());
    }

}


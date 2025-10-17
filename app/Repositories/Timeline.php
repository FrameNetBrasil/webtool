<?php

namespace App\Repositories;

use App\Data\Timeline\CreateData;
use App\Database\Criteria;

class Timeline
{
    public static function addTimeline(string $tableName, int $idTable, string $operation = 'S')
    {

        $data = CreateData::from([
            'tableName' => $tableName,
            'id' => $idTable,
            'operation' => $operation
        ]);
        Criteria::table("timeline")
            ->insert($data->toArray());
    }

    /*
    public function listByFilter($filter)
    {
        $criteria = $this->getCriteria()->select('*')->orderBy('idTimeline');
        if ($filter->idTimeline) {
            $criteria->where("idTimeline = {$filter->idTimeline}");
        }
        return $criteria;
    }

    public function newTimeline($tl, $operation = 'S')
    {
        $timeline = 'tl_' . $tl;
        $result = $this->getCriteria()->select('max(numOrder) as max')->where("upper(timeline) = upper('{$timeline}')")->asQuery()->getResult();
        $max = $result[0]['max'];
        $this->setPersistent(false);
        $this->operation = $operation;
        $this->tlDateTime = Carbon::now();
        $this->idUser = Base::getCurrentUser()->getId();
        $this->author = MAuth::getLogin() ? MAuth::getLogin()->login : 'offline';
        $this->save();
        return $timeline;
    }

    public function updateTimeline($oldTl, $newTl)
    {
        $oldTl = 'tl_' . $oldTl;
        $newTl = 'tl_' . $newTl;
//        $criteria = $this->getUpdateCriteria();
//        $criteria->addColumnAttribute('timeline');
//        $criteria->where("timeline = '{$oldTl}'");
//        $criteria->update($newTl);
        return $newTl;
    }


    */


}


<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;

class Relation
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter("view_relation", ['idEntityRelation', '=', $id])->first();
    }

    /*
    public static function removeFromEntityByRelationType(int $idEntity, int $idRelationType)
    {
        self::getCriteria()
            ->whereRaw("(idEntity1 = {$idEntity}) and (idRelationType = {$idRelationType})")
            ->delete();
    }

    public static function listForFrameGraph(int $idEntity): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        return self::getCriteria()
            ->select(['idEntityRelation','idRelationType', 'entry', 'entity1Type', 'entity2Type', 'entity3Type', 'idEntity1', 'idEntity2', 'idEntity3',
                'frame1.name frame1Name',
                'frame2.name frame2Name',
            ])
            ->where('entity1Type', '=', 'FR')
            ->where('entity2Type', '=', 'FR')
            ->where('frame1.idLanguage', '=', $idLanguage)
            ->where('frame2.idLanguage', '=', $idLanguage)
            ->whereRaw("((idEntity1 = {$idEntity}) or (idEntity2 = {$idEntity}))")
            ->all();
    }
    */

}

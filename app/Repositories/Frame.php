<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\RelationService;
use Illuminate\Support\Facades\DB;

class Frame
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_frame", ['idFrame', '=', $id])->first();
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_frame", ['idEntity', '=', $idEntity])->first();
    }
    public static function listFECoreSet(int $idFrame): array
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $result = Criteria::table("view_fe_internal_relation")
            ->where("relationType", 'rel_coreset')
            ->where("fe1IdFrame", $idFrame)
            ->where("idLanguage", $idLanguage)
            ->all();
        $index = [];
        $i = 0;
        foreach ($result as $row) {
            if (!isset($index[$row->fe1Name]) && !isset($index[$row->fe2Name])) {
                $i++;
                $index[$row->fe1Name] = $i;
                $index[$row->fe2Name] = $i;
            } elseif (!isset($index[$row->fe1Name])) {
                $index[$row->fe1Name] = $index[$row->fe2Name];
            } else {
                $index[$row->fe2Name] = $index[$row->fe1Name];
            }
        }
        $feCoreSet = [];
        foreach ($index as $fe => $i) {
            $feCoreSet[$i][] = $fe;
        }
        return $feCoreSet;
    }

    public static function listScenarioFrames(int $idFrameScenario): array
    {
        $children = [];
        self::listScenarioChildren($idFrameScenario, $children);
        return $children;
    }

    private static function listScenarioChildren(int $idFrame, &$children = [])
    {
        $frames = RelationService::listFrameChildren($idFrame);
        foreach ($frames as $frame) {
            if (($frame->relationType == 'rel_inheritance') || ($frame->relationType == 'rel_subframe') || ($frame->relationType == 'rel_perspective_on')) {
                self::listScenarioChildren($frame->idFrame, $children);
                $children[$frame->name] = $frame;
            }
        }
    }


//    public static function getByIdEntity(int $idEntity): object
//    {
//        return (object)self::first([
//            ['idEntity', '=', $idEntity],
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()]
//        ]);
//    }
//
//    public static function getByName(string $name): object
//    {
//        return (object)self::first([
//            ['name', '=', $name],
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()]
//        ]);
//    }
//
//    public static function listForSelect($name = '')
//    {
//        $name = (strlen($name) > 1) ? $name : 'none';
//        $criteria = self::getCriteria()
//            ->select(['idFrame', 'entries.name'])
//            ->orderBy('entries.name');
//        return self::filter([
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()],
//            ["upper(entries.name)", "startswith", strtoupper($name)]
//        ], $criteria);
//    }
//
//
//    public static function listDirectRelations(int $idFrame)
//    {
//        $criteria = Relation::getCriteria()
//            ->select(['entry', 'frame2.name', 'frame2.idEntity', 'frame2.idFrame', 'idEntityRelation'])
//            ->where("entry", "IN", [
//                'rel_causative_of',
//                'rel_inchoative_of',
//                'rel_inheritance',
//                'rel_perspective_on',
//                'rel_precedes',
//                'rel_see_also',
//                'rel_subframe',
//                'rel_structure',
//                'rel_using'])
//            ->where("frame1.idFrame", "=", $idFrame)
//            ->where("frame2.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->orderBy('frame2.name');
//        return $criteria;
//    }
//
//    public static function listInverseRelations(int $idFrame)
//    {
//        $criteria = Relation::getCriteria()
//            ->select(['entry', 'frame1.name', 'frame1.idEntity', 'frame1.idFrame', 'idEntityRelation'])
//            ->where("entry", "IN", [
//                'rel_causative_of',
//                'rel_inchoative_of',
//                'rel_inheritance',
//                'rel_perspective_on',
//                'rel_precedes',
//                'rel_see_also',
//                'rel_subframe',
//                'rel_structure',
//                'rel_using'])
//            ->where("frame2.idFrame", "=", $idFrame)
//            ->where("frame1.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->orderBy('frame1.name');
//        return $criteria;
//    }
//
//    public static function listRelationsFE(int $idEntityRelationBase)
//    {
//        $criteria = Relation::getCriteria()
//            ->select([
//                'entry',
//                'frameElement1.name feName',
//                'frameElement1.coreType feCoreType',
//                'frameElement1.idColor feIdColor',
//                'frameElement2.name relatedFEName',
//                'frameElement2.coreType relatedFECoreType',
//                'frameElement2.idColor relatedFEIdColor',
//                'idEntityRelation'
//            ])
//            ->where("idRelation", "=", $idEntityRelationBase)
//            ->where("frameElement1.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->where("frameElement2.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->orderBy('frameElement1.name');
//        return $criteria;
//    }
//
//    public static function listFE(int $idFrame): Criteria
//    {
//        $criteria = FrameElement::getCriteria()
//            ->select(['idFrameElement', 'entry', 'entries.name', 'entries.description', 'coreType', 'color.rgbFg', 'color.rgbBg',
//                'typeInstance.idTypeInstance as idCoreType', 'color.idColor', 'idEntity'])
//            ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->where("idFrame", "=", $idFrame)
//            ->orderBy('typeInstance.idTypeInstance')
//            ->orderBy('entries.name');
//        return $criteria;
//    }
//
//    public static function listByFrameDomain(int $idSemanticTypeEntity)
//    {
//        $criteria = Relation::getCriteria()
//            ->select(['idEntity1 as idEntity', 'frame1.name'])
//            ->where("entry", "=", "rel_framal_domain")
//            ->where("idEntity2", "=", $idSemanticTypeEntity)
//            ->where("frame1.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->orderBy('frame1.name');
//        return $criteria;
//    }
//
//
//    public static function delete(int|string $id): int
//    {
//        Repository::beginTransaction();
//        try {
//            $frame = self::find($id);
//            // remove entry
//            Entry::deleteByIdEntity($frame->idEntity);
//            // remove frame-relations
//            RelationService::deleteAll($frame->idEntity);
//            // remove FEs
//            FrameElement::deleteByFrame($frame->idFrame);
//            // remove this frame
//            Timeline::addTimeline("frame", $frame->idFrame, "D");
//            parent::delete($id);
//            // remove entity
//            Entity::delete($frame->idEntity);
//            Repository::commit();
//            return $id;
//        } catch (\Exception $e) {
//            Repository::rollback();
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//    public static function create(CreateData $data): int|null
//    {
//        Repository::beginTransaction();
//        try {
//            $baseEntry = strtolower('frm_' . $data->nameEn);
//            $idEntity = Entity::create('FR', $baseEntry);
//            Entry::create($baseEntry, $data->nameEn, $idEntity);
//            $idFrame = self::save((object)[
//                'entry' => $baseEntry,
//                'active' => 1,
//                'defaultName' => $data->nameEn,
//                'idEntity' => $idEntity
//            ]);
//            Timeline::addTimeline("frame", $idFrame, "C");
//            Repository::commit();
//            return $idFrame;
//        } catch (\Exception $e) {
//            Repository::rollback();
//            return null;
//        }
//    }
//
//
//    /*
//    public function getByName(string $name): void
//    {
//        $criteria = $this->getCriteria()
//            ->where('name', '=', $name)
//            ->where('idLanguage', '=', AppService::getCurrentIdLanguage());
//        $this->retrieveFromCriteria($criteria);
//    }
//
//    public function getDescription()
//    {
//        return $this->getEntry();
//    }
//
//    public function getByIdEntity($idEntity)
//    {
//        $criteria = $this->getCriteria()->select('*');
//        $criteria->where("frame.idEntity = {$idEntity}");
//        Base::entryLanguage($criteria);
//        $this->retrieveFromCriteria($criteria);
//    }
//
//    public function getByEntry($entry)
//    {
//        $criteria = $this->getCriteria()->select('*');
//        $criteria->where("entry = '{$entry}'");
//        $this->retrieveFromCriteria($criteria);
//    }
//
//    public function getEntryObject()
//    {
//        $criteria = $this->getCriteria()->select('entries.name, entries.description, entries.nick');
//        $criteria->where("idFrame = {$this->getId()}");
//        Base::entryLanguage($criteria);
//        return $criteria->asQuery()->asObjectArray()[0];
//    }
//
//
//
//    public function listByFilter($filter)
//    {
//        $criteria = $this->getCriteria()->select('idFrame, entry, active, idEntity, entries.name as name')->orderBy('entries.name');
//        Base::entryLanguage($criteria);
//        if ($filter->idFrame) {
//            $criteria->where("idFrame = {$filter->idFrame}");
//        }
//        if ($filter->lu) {
//            $criteria->distinct(true);
//            Base::relation($criteria, 'LU lu', 'Frame', 'rel_evokes');
//            $criteria->where("lu.name LIKE '{$filter->lu}%'");
//        }
//        if ($filter->fe) {
//            $criteriaFE = FrameElement::getCriteria();
//            $criteriaFE->select('frame.idFrame, entries.name as name');
//            $criteriaFE->where("entries.name LIKE '{$filter->fe}%'");
//            Base::entryLanguage($criteriaFE);
//            Base::relation($criteriaFE, 'FrameElement', 'Frame', 'rel_elementof');
//            $criteria->distinct(true);
//            $criteria->tableCriteria($criteriaFE, 'fe');
//            $criteria->where("idFrame = fe.idFrame");
//        }
//        if ($filter->frame) {
//            $criteria->where("entries.name LIKE '{$filter->frame}%'");
//        }
//        if ($filter->idLU) {
//            Base::relation($criteria, 'LU lu', 'Frame', 'rel_evokes');
//            if (is_array($filter->idLU)) {
//                $criteria->where("lu.idLU", "IN", $filter->idLU);
//            } else {
//                $criteria->where("lu.idLU = {$filter->idLU}");
//            }
//        }
//        return $criteria;
//    }
//
//    public function listForExport($idFrames)
//    {
//        $criteria = $this->getCriteria()->select('idFrame, entry, active, idEntity')->orderBy('entry');
//        $criteria->where("idFrame", "in", $idFrames);
//        return $criteria;
//    }
//
//
//
//
//
//    public function listLU()
//    {
//        $lu = new LU();
//        $criteria = $lu->getCriteria()
//            ->select(['idLU', 'name', 'senseDescription'])
//            ->where("idFrame", "=", $this->idFrame)
//            ->where("lemma.idLanguage", "=", AppService::getCurrentIdLanguage())
//            ->orderBy('name');
//        return $criteria;
//    }
//
//
//
//
////    public function listAll($idLanguage)
////    {
////        $criteria = $this->getCriteria()->select('*, entries.name as name')->orderBy('entries.name');
////        Base::entryLanguage($criteria);
////        return $criteria;
////    }
//
//    public function registerTemplate($idTemplate)
//    {
//        $template = new Template($idTemplate);
//        $fes = $template->listFEforNewFrame()->asQuery()->getResult();
//        Base::createEntityRelation($this->getIdEntity(), 'rel_hastemplate', $template->getIdEntity());
//        $frameElement = new FrameElement();
//        foreach ($fes as $fe) {
//            $newFE = new \StdClass();
//            $newFE->entry = $this->getEntry() . '_' . $fe['entry'] . '_' . $template->getEntry();
//            $newFE->idCoreType = $fe['idCoreType'];
//            $newFE->idColor = $fe['idColor'];
//            $newFE->idEntity = $fe['idEntity'];
//            $newFE->idFrame = $this->getId();
//            $frameElement->setPersistent(false);
//            $frameElement->setData($newFE);
//            $frameElement->save($newFE);
//            Base::createEntityRelation($frameElement->getIdEntity(), 'rel_hastemplate', $newFE->idEntity);
//        }
//    }
//
////    public function save(): ?int
////    {
////        $transaction = $this->beginTransaction();
////        try {
////            $entity = new Entity();
////            $entity->setAlias($this->getEntry());
////            $entity->setType('FR');
////            $entity->save();
////            $entry = new Entry();
////            $entry->newEntry($this->getEntry(), $entity->getId());
////            $this->setIdEntity($entity->getId());
////            $this->setActive(true);
////            //Base::entityTimelineSave($this->getIdEntity());
////            $idFrame = parent::save();
////            Timeline::addTimeline("frame", $this->getId(), "S");
//////            if ($data->idTemplate) {
//////                $this->registerTemplate($data->idTemplate);
//////            }
////            $transaction->commit();
////            return $idFrame;
////        } catch (\Exception $e) {
////            $transaction->rollback();
////            throw new \Exception($e->getMessage());
////        }
////    }
//
//
//    public function updateEntry($newEntry)
//    {
//        $transaction = $this->beginTransaction();
//        try {
//            Base::updateTimeLine($this->getEntry(), $newEntry);
//            $entity = new Entity($this->getIdEntity());
//            $entity->setAlias($newEntry);
//            $entity->save();
//            $entry = new Entry();
//            $entry->updateEntry($this->getEntry(), $newEntry);
//            $this->setEntry($newEntry);
//            parent::save();
//            $transaction->commit();
//        } catch (\Exception $e) {
//            $transaction->rollback();
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//    public function getRelations($empty = false)
//    {
//        $relations = ['direct' => [], 'inverse' => []];
//        if (!$empty) {
//            $relations['direct'] = $this->listDirectRelations();
//            $relations['inverse'] = $this->listInverseRelations();
//        }
//        return $relations;
//    }
//
//    public function createNew($data, $inheritsFromBase)
//    {
//        $relations = $this->getRelations(true);
//        $transaction = $this->beginTransaction();
//        try {
//            $this->save($data);
//            Timeline::addTimeline("frame", $this->getId(), "S");
////            if ($data->idTemplate) {
////                if ($inheritsFromBase) {
////                    $template = new Template($data->idTemplate);
////                    $base = $template->getBaseFrame()->asQuery()->getResult();
////                    if (count($base)) {
////                        $idFrameBase = $base[0]['idFrame'];
////                        $frameBase = new Frame($idFrameBase);
////                        $relations = $frameBase->getRelations();
////                        Base::createEntityRelation($frameBase->getIdEntity(), 'rel_inheritance', $this->getIdEntity());
////                    }
////                }
////            }
//            $transaction->commit();
//            return $relations;
//        } catch (\Exception $e) {
//            $transaction->rollback();
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//    public function createFromData($frame)
//    {
//        $this->setPersistent(false);
//        $this->setEntry($frame->entry);
//        $this->setActive($frame->active);
//        $this->setIdEntity($frame->idEntity);
//        parent::save();
//        Timeline::addTimeline("frame", $this->getId(), "S");
//    }
//    */
//
    public static function getClassification(int $idFrame): array
    {
        return Criteria::byFilterLanguage("view_frame_classification", ['idFrame', '=', $idFrame])
            ->treeResult('relationType')->all();
    }

    public static function getClassificationLabels(int $idFrame): array
    {
        $classification = [];
        $result = self::getClassification($idFrame);
        foreach ($result as $framal => $values) {
            foreach ($values as $row) {
                $classification[$framal][] = $row->name;
            }
        }
        $classification['id'][] = "#" . $idFrame;
        $frame = Criteria::byFilterLanguage("view_frame", ['idFrame', '=', $idFrame], 'idLanguage', 2)->first();
        $classification['en'][] = $frame->name . " [en]";
        return $classification;
    }

}

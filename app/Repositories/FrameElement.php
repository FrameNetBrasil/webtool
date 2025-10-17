<?php

namespace App\Repositories;

use App\Data\Entry\UpdateSingleData;
use App\Data\FE\CreateData;
use App\Data\FE\UpdateData;
use App\Database\Criteria;

//use App\Services\AppService;
//use Illuminate\Support\Facades\DB;
//use Orkester\Persistence\Enum\Key;
//use Orkester\Persistence\Enum\Type;
//use Orkester\Persistence\Map\ClassMap;
//use Orkester\Persistence\Repository;

class FrameElement
{
    public static function byId(int $id): object
    {
        $fe = Criteria::byFilterLanguage("view_frameelement", ['idFrameElement', '=', $id])->first();
        $fe->frame = Frame::byId($fe->idFrame);
        return $fe;
    }

    public static function update(UpdateData $object)
    {
        Criteria::table("frameelement")
            ->where("idFrameElement", "=", $object->idFrameElement)
            ->update([
                'coreType' => $object->coreType,
                'idColor' => $object->idColor
            ]);
    }

//    public static function listByFrame(int $idFrame)
//    {
//        $criteria = self::getCriteria()
//            ->select(['idFrameElement', 'entry', 'entries.name', 'entries.description', 'coreType', 'color.rgbFg', 'color.rgbBg',
//            'typeInstance.idTypeInstance as idCoreType', 'color.idColor']);
//        $criteria->orderBy('typeInstance.idTypeInstance, entries.name');
//        return self::filter([
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()],
//            ['idFrame','=', $idFrame]
//        ], $criteria);
//    }
//
//    public static function getById(int $id): object
//    {
//        $fe = (object)self::first([
//            ['idFrameElement', '=', $id],
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()]
//        ]);
//        $en = (object)self::first([
//            ['idFrameElement', '=', $id],
//            ['entries.language.language', '=', 'en']
//        ]);
//        $fe->frame = Frame::getById($fe->idFrame);
//        $fe->nameEn = $en->name;
//        return $fe;
//    }
//
//    public static function listInternalRelations(int $idFrame)
//    {
//        $idLanguage = AppService::getCurrentIdLanguage();
//        $result = Relation::getCriteria()
//            ->where('frameElement1.idFrame','=', $idFrame)
//            ->where('relationType.entry','IN', ['rel_coreset','rel_excludes','rel_requires'])
//            ->where('relationType.idLanguage','=', $idLanguage)
//            ->where('frameElement1.idLanguage','=', $idLanguage)
//            ->where('frameElement2.idLanguage','=', $idLanguage)
//            ->get(['idEntityRelation','relationType.entry','relationType.entries.name',
//                'frameElement1.idFrameElement feIdFrameElement','frameElement1.name feName','frameElement1.coreType feCoreType','frameElement1.idColor feIdColor',
//                'frameElement2.idFrameElement relatedIdFrameElement','frameElement2.name relatedFEName','frameElement2.coreType relatedFECoreType','frameElement2.idColor relatedFEIdColor'
//            ])
//            ->all();
//        return $result;
//    }
//
//    public static function deleteByFrame(int $idFrame) {
//        self::getCriteria()
//            ->where('idFrame', '=', $idFrame)
//            ->delete();
//    }
//
//    public static function create(CreateData $data): int|null
//    {
//
//        DB::transaction(function () use ($data) {
//            $idFrameElement = Criteria::function('fe_create(?, ?, ?, ?, ?)', [
//                $data->idFrame,
//                $data->entry,
//                $data->coreType,
//                $data->idColor,
//                $data->idUser
//            ]);
//
//        });
//
//
//        Repository::beginTransaction();
//
//        Repository::beginTransaction();
//        try {
//            $baseEntry = strtolower('fe_' . $data->nameEn);
//            $idEntity = Entity::create('FE', $baseEntry);
//            Entry::create($baseEntry, $data->nameEn, $idEntity);
//            $idFrameElement = self::save((object)[
//                'entry' => $baseEntry,
//                'idFrame' => $data->idFrame,
//                'coreType' => $data->coreType,
//                'idColor' => $data->idColor,
//                'idEntity' => $idEntity
//            ]);
//            Timeline::addTimeline("frameelement", $idFrameElement, "C");
//            Repository::commit();
//            return $idFrameElement;
//        } catch (\Exception $e) {
//            dump($e->getMessage());
//            Repository::rollback();
//            return null;
//        }
////        Repository::beginTransaction();
////        try {
////            $baseEntry = strtolower('fe_' . $data->nameEn);
////            $idEntity = Entity::create('FE', $baseEntry);
////            Entry::create($baseEntry, $data->nameEn, $idEntity);
////            $idFrameElement = self::save((object)[
////                'entry' => $baseEntry,
////                'idFrame' => $data->idFrame,
////                'coreType' => $data->coreType,
////                'idColor' => $data->idColor,
////                'idEntity' => $idEntity
////            ]);
////            Timeline::addTimeline("frameelement", $idFrameElement, "C");
////            Repository::commit();
////            return $idFrameElement;
////        } catch (\Exception $e) {
////            dump($e->getMessage());
////            Repository::rollback();
////            return null;
////        }
//    }
//
//    /*
//    public function getById(int $id): void
//    {
//        $criteria = $this->getCriteria()
//            ->where('idFrameElement', '=', $id)
//            ->where('idLanguage','=', AppService::getCurrentIdLanguage());
//        $this->retrieveFromCriteria($criteria);
//    }
//
//
//    public function delete()
//    {
//        $this->beginTransaction();;
//        try {
//            $id = $this->getId();
//            $entry = new Entry();
//            $entry->deleteByIdEntity($this->idEntity);
//            parent::delete();
//            $entity = new Entity($this->idEntity);
//            $entity->delete();
//            Timeline::addTimeline("frameelement", $id, "D");
//            $this->commit();
//        } catch (\Exception $e) {
//            $this->rollback();
//            ddump($e->getMessage());
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//    public function update($data)
//    {
//        $this->beginTransaction();
//        try {
//            $this->saveData($data);
//            Timeline::addTimeline("frameelement", $this->getId(), "U");
//            $this->commit();
//        } catch (\Exception $e) {
//            $this->rollback();
//            ddump($e->getMessage());
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//
//    public function listEntries(int $idLanguage = null) {
//        $entry = new Entry();
//        return $entry->listByIdEntity($this->idEntity);
//    }
//
//
//    public function listCoreSet(int $idFrameElement)
//    {
//        return $this->listFE2FERelation($idFrameElement, 'rel_coreset');
//    }
//
//    public function listExcludes(int $idFrameElement)
//    {
//        return $this->listFE2FERelation($idFrameElement, 'rel_excludes');
//    }
//
//    public function listRequires(int $idFrameElement)
//    {
//        return $this->listFE2FERelation($idFrameElement, 'rel_requires');
//    }
//    public function listFE2FERelation(int $idFrameElement, string $relationType)
//    {
//        $criteria = RelationModel::getCriteria()
//            ->where('frameElement1.idFrameElement','=', $idFrameElement)
//            ->where('entry','=', $relationType)
//            ->get(['idEntityRelation','entry','entries.name','frameElement2.name related']);
//        return $criteria;
//    }
//
//
//    /*
//        private $idCoreType;
//
//        public static function config()
//        {
//            return array(
//                'log' => array(),
//                'validators' => array(
//                    'entry' => array('notnull'),
//                    'active' => array('notnull'),
//                    'idEntity' => array('notnull'),
//                    'idColor' => array('notnull'),
//                ),
//                'converters' => array()
//            );
//        }
//
//        public function getIdCoreType()
//        {
//            return $this->idCoreType;
//        }
//
//        public function setIdCoreType($value)
//        {
//            $this->idCoreType = (int) $value;
//        }
//
//        public function getIdFrame()
//        {
//            return $this->idFrame;
//        }
//
//        public function setIdFrame($value)
//        {
//            $this->idFrame = (int) $value;
//        }
//
//        public function getDescription()
//        {
//            return $this->getIdFrameElement();
//        }
//
//        public function getData()
//        {
//            $data = parent::getData();
//            $data->idCoreType = $this->idCoreType;
//            $data->idFrame = $this->idFrame;
//            return $data;
//        }
//
//        public function setData($data)
//        {
//            parent::setData($data);
//            $this->idCoreType = $data->idCoreType;
//            $this->idFrame = $data->idFrame;
//        }
//
//        public function getName()
//        {
//            $criteria = $this->getCriteria()->select('entries.name as name');
//            $criteria->where("idFrameElement = {$this->getId()}");
//            Base::entryLanguage($criteria);
//            return $criteria->asQuery()->getResult()[0]['name'];
//        }
//
//        public function getFrame() {
//            $vf = new ViewFrame();
//            $criteria = $vf->getCriteria()->select('idFrame')->where("fes.idFrameElement = {$this->getId()}");
//            return Frame::create($criteria->asQuery()->getResult()[0]['idFrame']);
//        }
//
//        public function getById($id)
//        {
//            parent::getById($id);
//            $coreType = new TypeInstance();
//            $criteria = $coreType->getCriteria()->select('idTypeInstance');
//            Base::relation($criteria, 'FrameElement', 'TypeInstance', 'rel_hastype');
//            $criteria->where("frameelement.idFrameElement = '{$id}'");
//            $result = $criteria->asQuery()->getResult();
//            $this->setIdCoreType($result[0]['idTypeInstance']);
//            //$criteria = $this->getCriteria()->select('frame.idFrame');
//            //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//            //$criteria->where("idFrameElement = '{$id}'");
//            //$result = $criteria->asQuery()->getResult();
//            //$this->setIdFrame($result[0]['idFrame']);
//        }
//
//        public function getByIdEntity($idEntity)
//        {
//            $criteria = $this->getCriteria()->select('*');
//            $criteria->where("idEntity = {$idEntity}");
//            $this->retrieveFromCriteria($criteria);
//        }
//
//        public function getByEntry($entry)
//        {
//            $criteria = $this->getCriteria()->select('*');
//            $criteria->where("entry = '{$entry}'");
//            $this->retrieveFromCriteria($criteria);
//        }
//
//
//        public function listByFilter($filter)
//        {
//            $criteria = $this->getCriteria()->select('*')->orderBy('idFrameElement');
//            if ($filter->idFrameElement) {
//                $criteria->where("idFrameElement LIKE '{$filter->idFrameElement}%'");
//            }
//            if ($filter->idFrame) {
//                //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//                $criteria->where("frame.idFrame = {$filter->idFrame}");
//            }
//            return $criteria;
//        }
//
//        public function listForLookup($idFrame = '')
//        {
//            $criteria = $this->getCriteria()->select('idFrameElement,entries.name as name')->orderBy('entries.name');
//            Base::entryLanguage($criteria);
//            if ($idFrame) {
//                //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//                $criteria->where("frame.idFrame = {$idFrame}");
//            }
//            return $criteria;
//        }
//
//        public function listForLookupDecorated($idFrame = '')
//        {
//            $criteria = $this->getCriteria()->select('idFrameElement,entries.name as name, color.rgbFg, color.rgbBg')->orderBy('entries.name');
//            Base::entryLanguage($criteria);
//            if ($idFrame) {
//                //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//                $criteria->where("frame.idFrame = {$idFrame}");
//            }
//            return $criteria;
//        }
//
//        public function listForReport($idFrame = '')
//        {
//            $criteria = $this->getCriteria()->select('idFrameElement,entry,entries.name as name, entries.description as description, entries.nick as nick, coreType')->orderBy('entries.name');
//            Base::entryLanguage($criteria);
//            Base::relation($criteria, 'FrameElement', 'TypeInstance', 'rel_hastype');
//            if ($idFrame) {
//                //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//                $criteria->where("frame.idFrame = {$idFrame}");
//            }
//            return $criteria;
//        }
//
//        public function listForEditor($idEntityFrame)
//        {
//            $criteria = $this->getCriteria()->select('idEntity,entries.name as name')->orderBy('entries.name');
//            Base::entryLanguage($criteria);
//            //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//            $criteria->where("frame.idEntity = {$idEntityFrame}");
//            return $criteria;
//        }
//
//        public function listCoreForEditor($idEntityFrame)
//        {
//            $criteria = $this->getCriteria()->select('idEntity,entries.name as name')->orderBy('entries.name');
//            Base::entryLanguage($criteria);
//            Base::relation($criteria, 'FrameElement', 'TypeInstance', 'rel_hastype');
//            //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//            $criteria->where("typeinstance.entry = 'cty_core'");
//            $criteria->where("frame.idEntity = {$idEntityFrame}");
//            return $criteria;
//        }
//
//        public function listFE2SemanticType($idFrameElement = '')
//        {
//            $idLanguage = \Manager::getSession()->idLanguage;
//            $id = ($idFrameElement ?: $this->getId());
//            $criteria = $this->getCriteria()->select('SemanticType.idEntity, SemanticType.entry, Entry.name');
//            Base::relation($criteria, 'FrameElement fe', 'SemanticType', 'rel_hassemtype');
//            $criteria->join("SemanticType","Entry","SemanticType.entry = Entry.entry");
//            $criteria->where("Entry.idLanguage = {$idLanguage}");
//            $criteria->where("fe.idFrameElement = {$id}");
//            return $criteria;
//        }
//
//
//        public function listConstraints()
//        {
//            $constraints = [];
//            $constraint = new ViewConstraint();
//            $frameConstraints = $constraint->getByIdConstrained($this->getIdEntity());
//            foreach ($frameConstraints as $frameConstraint) {
//                $constraints[] = $frameConstraint;
//            }
//            $metonymyConstraints = $constraint->listFEMetonymyConstraints($this->getIdEntity());
//            foreach ($metonymyConstraints as $metonymyConstraint) {
//                $constraints[] = $metonymyConstraint;
//            }
//            return $constraints;
//        }
//
//        public function listForExport($idFrame)
//        {
//            $criteria = $this->getCriteria()->select('idFrameElement, entry, active, idEntity, idColor, coreType')->orderBy('entry');
//            //Base::relation($criteria, 'FrameElement', 'Frame', 'rel_elementof');
//            Base::relation($criteria, 'FrameElement', 'TypeInstance', 'rel_hastype');
//            $criteria->where("frame.idFrame = {$idFrame}");
//            return $criteria;
//        }
//
//        public function save($data)
//        {
//            $transaction = $this->beginTransaction();
//            try {
//                if ($this->isPersistent()) {
//                    $coreType = new TypeInstance($this->getIdCoreType());
//                    $this->setCoreType($coreType->getEntry());
//                    Base::updateEntityRelation($this->getIdEntity(), 'rel_hastype', $coreType->getIdEntity());
//                    $this->setActive(true);
//                    $criteria = $this->getCriteria()->select('fe1.idFrameElement');
//                    Base::relation($criteria, 'FrameElement fe1', 'FrameElement fe2', 'rel_hastemplate');
//                    $criteria->where("fe2.idEntity = {$this->getIdEntity()}");
//                    $fes = $criteria->asQuery()->getResult();
//                    foreach($fes as $fe) {
//                        $feTemplated = new FrameElement($fe['idFrameElement']);
//                        $feTemplated->setIdColor($this->getIdColor());
//                        $feTemplated->save();
//                    }
//                } else {
//                    if ($data->idFrame) {
//                        $schema = new Frame($data->idFrame);
//                        $this->setIdFrame($data->idFrame);
//                    } else if ($data->idTemplate) {
//                        $schema = new Template($data->idTemplate);
//                    }
//                    $entity = new Entity();
//                    $entity->setAlias($this->getEntry());
//                    $entity->setType('FE');
//                    $entity->save();
//                    $entry = new Entry();
//                    $entry->newEntry($this->getEntry(),$entity->getId());
//                    Base::createEntityRelation($entity->getId(), 'rel_elementof', $schema->getIdEntity());
//                    $coreType = new TypeInstance($data->idCoreType);
//                    Base::createEntityRelation($entity->getId(), 'rel_hastype', $coreType->getIdEntity());
//                    $this->setCoreType($coreType->getEntry());
//                    $this->setIdEntity($entity->getId());
//                    $this->setActive(true);
//                }
//                //Base::entityTimelineSave($this->getIdEntity());
//                parent::save();
//                Timeline::addTimeline("frameelement",$this->getId(),"S");
//                $transaction->commit();
//            } catch (\Exception $e) {
//                $transaction->rollback();
//                throw new \Exception($e->getMessage());
//            }
//        }
//
//        public function saveModel(){
//            parent::save();
//        }
//
//        public function safeDelete() {
//            $fe = new ViewFrameElement();
//            $count = count($fe->relations($this->getId(), '', 'rgp_frame_relations')->asQuery()->getResult());
//            if ($count > 0) {
//                throw new \Exception("This FrameElement has Relations! Removal canceled.");
//            } else {
//                if ($fe->hasAnnotations($this->getId())) {
//                    throw new \Exception("This FrameElement has Annotations! Removal canceled.");
//                } else {
//                    Timeline::addTimeline("frameelement",$this->getId(),"D");
//                    $this->delete();
//                }
//            }
//        }
//
//        public function delete() {
//            $transaction = $this->beginTransaction();
//            try {
//                $idEntity = $this->getIdEntity();
//                // remove entry
//                $entry = new Entry();
//                $entry->deleteEntry($this->getEntry());
//                // remove fe-relations
//                Base::deleteAllEntityRelation($idEntity);
//                // remove labels
//                $label = new Label();
//                $label->deleteByIdLabelType($idEntity);
//                Base::entityTimelineDelete($this->getIdEntity());
//                // remove this fe
//                Timeline::addTimeline("frameelement",$this->getId(),"D");
//                parent::delete();
//                // remove entity
//                $entity = new Entity($idEntity);
//                $entity->delete();
//                $transaction->commit();
//            } catch (\Exception $e) {
//                $transaction->rollback();
//                throw new \Exception($e->getMessage());
//            }
//        }
//
//        public function updateEntry($newEntry)
//        {
//            $transaction = $this->beginTransaction();
//            try {
//    //            Base::updateTimeLine($this->getEntry(), $newEntry);
//                Timeline::addTimeline("frameelement",$this->getId(),"S");
//                $entity = new Entity($this->getIdEntity());
//                $entity->setAlias($newEntry);
//                $entity->save();
//                $entry = new Entry();
//                $entry->updateEntry($this->getEntry(), $newEntry);
//                $this->setEntry($newEntry);
//                parent::save();
//                $transaction->commit();
//            } catch (\Exception $e) {
//                $transaction->rollback();
//                throw new \Exception($e->getMessage());
//            }
//        }
//
//        public function createFromData($fe)
//        {
//            $this->setPersistent(false);
//            $this->setEntry($fe->entry);
//            $this->setActive($fe->active);
//            $this->setIdEntity($fe->idEntity);
//            $this->setIdColor($fe->idColor);
//            $coreType = new TypeInstance();
//            $idCoreType = $coreType->getIdCoreTypeByEntry($fe->coreType);
//            $coreType->getById($idCoreType);
//            Base::createEntityRelation($fe->idEntity, 'rel_hastype', $coreType->getIdEntity());
//            parent::save();
//            Timeline::addTimeline("frameelement",$this->getId(),"S");
//        }
//
//        public function createRelationsFromData($fe)
//        {
//            if ($fe->idFrame) {
//                $frame = new Frame($fe->idFrame);
//                Base::createEntityRelation($this->getIdEntity(), 'rel_elementof', $frame->getIdEntity());
//            }
//            $feRelated = new FrameElement();
//            if ($fe->coreset) {
//                foreach($fe->coreset as $coreset) {
//                    $feRelated->getByEntry($coreset->entry);
//                    Base::createEntityRelation($this->getIdEntity(), 'rel_coreset', $feRelated->getIdEntity());
//                }
//            }
//            if ($fe->excludes) {
//                foreach($fe->excludes as $excludes) {
//                    $feRelated->getByEntry($excludes->entry);
//                    Base::createEntityRelation($this->getIdEntity(), 'rel_excludes', $feRelated->getIdEntity());
//                }
//            }
//            if ($fe->requires) {
//                foreach($fe->requires as $requires) {
//                    $feRelated->getByEntry($requires->entry);
//                    Base::createEntityRelation($this->getIdEntity(), 'rel_requires', $feRelated->getIdEntity());
//                }
//            }
//        }
//    */
}


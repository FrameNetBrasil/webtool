<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Support\Facades\DB;

class Concept
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_concept", ['idConcept', '=', $id])->first();
    }

    public static function byIdEntity(int $idEntity): object
    {
        return Criteria::byFilterLanguage("view_concept", ['idEntity', '=', $idEntity])->first();
    }

    public static function listRelations(int $idEntity)
    {
        return Criteria::table("view_relation")
            ->join("view_semantictype", "view_relation.idEntity2", "=", "view_semantictype.idEntity")
            ->filter([
                ["view_relation.idEntity1", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_hassemtype"],
                ["view_semantictype.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->orderBy("view_semantictype.name")->all();
    }

    public static function listTree(string $concept)
    {
        $rows = Criteria::table("view_concept")
            ->filter([
                ["view_concept.name", "startswith", $concept],
                ["view_concept.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_concept.idConcept", "view_concept.idEntity", "view_concept.name","view_concept.type")
            ->orderBy("view_concept.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listChildren(int $idEntity)
    {
        $components = ['rel_constituentof','rel_roleof','rel_attributeof'];
        $criteriaConstituent = Criteria::table("view_relation")
            ->select('idEntity1')
            ->whereIn("relationType", $components);
        $rows = Criteria::table("view_relation")
            ->join("view_concept", "view_relation.idEntity1", "=", "view_concept.idEntity")
            ->filter([
                ["view_relation.idEntity2", "=", $idEntity],
                ["view_relation.relationType", "=", "rel_subtypeof"],
                ["view_concept.idLanguage", "=", AppService::getCurrentIdLanguage()]
            ])->select("view_concept.idConcept", "view_concept.idEntity", "view_concept.name","view_concept.type")
            ->where("view_concept.idEntity", "NOT IN", $criteriaConstituent)
            ->whereNotNull("view_concept.type")
            ->orderBy("view_concept.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listTypeChildren(int $idTypeInstance): array
    {
        $criteriaER = Criteria::table("view_relation")
            ->select('idEntity1')
            ->where("relationType", "=", 'rel_subtypeof');
        $components = ['rel_constituentof','rel_roleof','rel_attributeof'];
        $criteriaConstituent = Criteria::table("view_relation")
            ->select('idEntity1')
            ->whereIn("relationType", $components);
        $rows = Criteria::table("view_concept")
            ->where("idEntity", "NOT IN", $criteriaER)
            ->where("idEntity", "NOT IN", $criteriaConstituent)
            ->where("idLanguage", AppService::getCurrentIdLanguage())
            ->where("idTypeInstance", $idTypeInstance)
            ->where("status","<>","deleted")
            ->whereNotNull("keyword")
            ->whereNotNull("view_concept.type")
            ->select("view_concept.idConcept", "view_concept.idEntity", "view_concept.name","view_concept.type")
            ->orderBy("view_concept.name")->all();
        foreach ($rows as $row) {
            $row->n = Criteria::table("view_relation")
                ->where("view_relation.idEntity2", "=", $row->idEntity)
                ->where("view_relation.relationType", "=", "rel_subtypeof")
                ->count();
        }
        return $rows;
    }

    public static function listRoots(): array
    {
//        select distinct ti.idTypeInstance, ti.name, c.type
//from view_concept c
//join view_typeinstance ti on (c.idTypeInstance = ti.idTypeInstance)
//where c.type is not null;
        $rows = Criteria::table("view_typeinstance as ti")
            ->join("view_concept as c", "ti.idTypeInstance", "=", "c.idTypeInstance")
            ->where("ti.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->select("ti.idTypeInstance", "ti.name","c.type")
            ->distinct()
            ->whereNotNull("c.type")
            ->orderBy("ti.name")
            ->all();
        return $rows;
    }

//    public static function getById(int $id): object
//    {
//        return (object)self::first([
//            ['idSemanticType', '=', $id],
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()]
//        ]);
//    }
//    public static function listRelations(int $idEntity) {
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name','inverseRelations.idEntityRelation'])
//            ->where("inverseRelations.idEntity1","=",$idEntity)
//            ->where('idLanguage', '=', AppService::getCurrentIdLanguage())
//            ->orderBy('name');
//        return $criteria;
//    }
//
//    public static function getByName(string $name):object
//    {
//        $st = (object)self::first([
//            ['name', '=', $name],
//        ]);
//        return $st;
//    }
//
//    public static function listForComboGrid(string $root = '')
//    {
//        $st = self::getByName($root);
//        return self::listChildren($st->idSemanticType, (object)[])->all();
////        $result = [];
////        foreach ($list as $row) {
////            $node = (array)$row;
//////            $node['state'] = 'open';
//////            $node['iconCls'] = 'material-icons-outlined wt-tree-icon wt-icon-semantictype';
////            $children = self::listForComboGrid($row->name);
////            $node['children'] = !empty($children) ? $children : null;
////            $result[] = $node;
////        }
////        return $result;
//    }
//
//    public static function listForTree(SearchData $search)
//    {
//        if ($search->idSemanticType != 0) {
//            $list = self::listChildren($search->idSemanticType, (object)[])->all();
//        } else {
//            $list = self::listRoot((object)['name' => $search->semanticType, 'idDomain' => $search->idDomain])->all();
//        }
//        return $list;
//    }
//
//    public static function listRoot(object $filter)
//    {
//        $criteriaER = Relation::getCriteria()
//            ->select('idEntity1')
//            ->where("relationType.entry","=",'rel_subtypeof');
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name'])
//            ->where("idEntity","NOT IN", $criteriaER)
//            ->orderBy('name');
//        debug($filter);
//        return self::filter([
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()],
//            ['idDomain', '=', $filter->idDomain],
//            ["upper(entries.name)", "startswith", strtoupper($filter->name ?? '')]
//        ], $criteria);
//    }
//    public static function listChildren(int $idSuperType, object $filter)
//    {
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name'])
//            ->orderBy('name');
//        $superType = SemanticType::getById($idSuperType);
//        $criteriaER = Relation::getCriteria()
//            ->select('idEntity1')
//            ->where("relationType.entry","=",'rel_subtypeof')
//            ->where("idEntity2","=",$superType->idEntity);
//        return self::filter([
//            ['idLanguage', '=', AppService::getCurrentIdLanguage()],
//            ["idSemanticType", "=", $filter->idSemanticType ?? null],
//            ["idDomain", "=", $filter->idDomain ?? null],
//            ["upper(entries.name)", "startswith", strtoupper($filter->type ?? null)],
//            ["idEntity", "IN", $criteriaER]
//        ], $criteria);
//    }
//
//    public static function add(CreateData $data): void
//    {
//        self::beginTransaction();
//        try {
//            $st = self::getById($data->idSemanticType);
//            $idEntityRelation = RelationService::create('rel_hasType', $data->idEntity, $st->idEntity);
//            Timeline::addTimeline("entityrelation", $idEntityRelation, "S");
//            self::commit();
//        } catch (\Exception $e) {
//            self::rollback();
//            throw new \Exception($e->getMessage());
//        }
//    }
//
//    /*
//    public function getById(int $id): void
//    {
//        $criteria = $this->getCriteria()
//            ->where('idSemanticType', '=', $id)
//            ->where('idLanguage', '=', AppService::getCurrentIdLanguage());
//        $this->retrieveFromCriteria($criteria);
//    }
//    public function getDescription()
//    {
//        return $this->getEntry();
//    }
//
//    public function getByIdEntity($idEntity)
//    {
//        $criteria = $this->getCriteria()->select('idSemanticType, entry, idEntity, idDomain, entries.name, entries.description, entries.nick');
//        $criteria->where("idEntity = {$idEntity}");
//        Base::entryLanguage($criteria);
//        return $criteria->asQuery()->asObjectArray()[0];
//    }
//
//    public function retrieveFromName(string $name)
//    {
//        $criteria = $this->getCriteria()
//            ->select('*')
//            ->where("name","=",$name);
//        Base::entryLanguage($criteria);
//        $this->retrieveFromCriteria($criteria);
//    }
//    public function getEntryObject()
//    {
//        $criteria = $this->getCriteria()->select('entries.name, entries.description, entries.nick');
//        $criteria->where("idSemanticType = {$this->getId()}");
//        Base::entryLanguage($criteria);
//        return $criteria->asQuery()->asObjectArray()[0];
//    }
//
//    public function getName()
//    {
//        $criteria = $this->getCriteria()->select('entries.name as name');
//        $criteria->where("idSemanticType = {$this->getId()}");
//        Base::entryLanguage($criteria);
//        return $criteria->asQuery()->fields('name');
//    }
//
//    public function listByFilter(object $filter)
//    {
//        $criteria = $this->getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name'])
//            ->orderBy('entries.name');
//        Base::entryLanguage($criteria);
//        if ($filter->idSemanticType) {
//            $criteria->where("idSemanticType = {$filter->idSemanticType}");
//        }
//        if ($filter->idDomain) {
//            $criteria->where("idDomain = {$filter->idDomain}");
//        }
//        if ($filter->type) {
//            $criteria->where("upper(entries.name) LIKE upper('{$filter->type}%')");
//        }
//        return $criteria;
//    }
//
//    public function listRelations(int $idEntity) {
//        $criteria = $this->getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name','inverseRelations.idEntityRelation'])
//            ->where("inverseRelations.idEntity1","=",$idEntity)
//            ->orderBy('name');
//        Base::entryLanguage($criteria);
//        return $criteria;
//    }
//
//    public function listRoot(object $filter)
//    {
//        $criteria = $this->getCriteria()
//            ->select(['idSemanticType','entry','idEntity','idDomain','name'])
//            ->orderBy('name');
//        Base::entryLanguage($criteria);
//        if ($filter->idSemanticType ?? false) {
//            $criteria->where("idSemanticType = {$filter->idSemanticType}");
//        }
//        if ($filter->idDomain ?? false) {
//            $criteria->where("idDomain = {$filter->idDomain}");
//        }
//        if ($filter->type ?? false) {
//            $criteria->where("upper(entries.name) LIKE upper('{$filter->type}%')");
//        }
//        $entityRelation = new EntityRelation();
//        $criteriaER = $entityRelation->getCriteria()
//            ->select('idEntity1')
//            ->where("relationtype.entry","=",'rel_subtypeof');
//        $criteria->where("idEntity", "NOT IN", $criteriaER);
//        return $criteria;
//    }
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
//
//    public function listForLookup($filter)
//    {
//        $criteria = $this->getCriteria()->select("idSemanticType,concat(entries.name, '.',  dEntries.name) as name")->orderBy('concat(entries.name, dEntries.name)');
//        if ($filter->idDomain) {
//            $criteria->where("idDomain = {$filter->idDomain}");
//        }
//        if ($filter->name) {
//            $criteria->where("entries.name LIKE '@{$filter->name}%'");
//        }
//        $criteria->associationAlias("domain.entries", "dEntries");
//        Base::entryLanguage($criteria, "dEntries.");
//        Base::entryLanguage($criteria);
//        return $criteria;
//    }
//*/
//    public static function listFrameDomain()
//    {
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','name'])
//            ->orderBy('name');
//        return self::filter([
//            ['idLanguage','=',AppService::getCurrentIdLanguage()],
//            ['entries.entry','startswith','sty\_fd%'],
//        ], $criteria);
//    }
//
//    public static function listFrameType()
//    {
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','name'])
//            ->orderBy('name');
//        return self::filter([
//            ['idLanguage','=',AppService::getCurrentIdLanguage()],
//            ['entries.entry','startswith','sty\_ft%'],
//        ], $criteria);
//    }
//
//    public static function listFrameCluster()
//    {
//        $criteria = self::getCriteria()
//            ->select(['idSemanticType','name'])
//            ->orderBy('name');
//        return self::filter([
//            ['idLanguage','=',AppService::getCurrentIdLanguage()],
//            ['entries.entry','startswith','sty\_fc%'],
//        ], $criteria);
//    }
    /*
        public function listSTLUforConstraint()
        {
            $criteria = $this->getCriteria()->select("idEntity, entries.name");
            $criteria->where("entry", "IN", ['sty_positive_judgment_1', 'sty_negative_judgment_1']);
            Base::entryLanguage($criteria);
            return $criteria->asQuery();
        }

        public function listForLookupLU()
        {
            $idLanguage = \Manager::getSession()->idLanguage;

            $cmd = <<<HERE

    SELECT idSemanticType, concat(type, if (subtype <> '', '.',''), subtype) as name
    from (
      SELECT s3.idSemanticType, e2.name type, e3.name subtype
      FROM semantictype s1
      join view_relation r1 on (s1.identity = r1.identity2)
      join semantictype s2 on (r1.identity1 = s2.idEntity)
      join entry e2 on (s2.entry = e2.entry)
      left join view_relation r2 on (s2.idEntity = r2.idEntity2)
      left join semantictype s3 on (r2.identity1 = s3.idEntity)
      left join entry e3 on (s3.entry = e3.entry)
      where r1.relationType='rel_subtypeof'
      and ((r2.relationType='rel_subtypeof') or (r2.relationType is null))
      and s1.entry = 'sty_lexical_type_1'
      and e2.idLanguage = 1
      and ((e3.idLanguage = 1) or (e3.idLanguage is null))
    UNION
      SELECT s2.idSemanticType, e2.name type, '' as subtype
      FROM semantictype s1
      join view_relation r1 on (s1.identity = r1.identity2)
      join semantictype s2 on (r1.identity1 = s2.idEntity)
      join entry e2 on (s2.entry = e2.entry)
      where r1.relationType='rel_subtypeof'
      and s1.entry = 'sty_lexical_type_1'
      and e2.idLanguage = 1
    ) semtype
    order by type, subtype

    HERE;

            $query = $this->getDb()->getQueryCommand($cmd);
            return $query;
        }

        public function listTypesByEntity($idEntity)
        {
            $idLanguage = \Manager::getSession()->idLanguage;
            $domain = new Domain();
            $domainCriteria = $domain->getCriteria()
                ->select('idDomain, entries.name as domainName')
                ->setAlias('d');
            Base::entryLanguage($domainCriteria);
            $entry = new Entry();
            $entryCriteria = $entry->getCriteria()
                ->select('entry, name')
                ->where("idLanguage = {$idLanguage}")
                ->setAlias('e');
            $criteria = Base::relationCriteria('entity', 'semantictype', 'rel_hassemtype',
                'semantictype.idSemanticType,e.name,semantictype.idEntity, d.domainName')
                ->orderBy('e.name');
            $criteria->joinCriteria($entryCriteria, "(e.entry = semantictype.entry)");
            $criteria->joinCriteria($domainCriteria, "(d.idDomain = semantictype.idDomain)");
            $criteria->where('entity.idEntity', '=', $idEntity);
            return $criteria;
        }

        public function saveData($data): ?int
        {
            $transaction = $this->beginTransaction();
            try {
                if (!$this->isPersistent()) {
                    $entity = new Entity();
                    $entity->setAlias($this->getEntry());
                    $entity->setType('ST');
                    $entity->save();
                    $entry = new Entry();
                    $entry->newEntry($this->getEntry(), $entity->getId());
                    $this->setIdEntity($entity->getId());
                    if ($data->idSuperType) {
                        $superType = new SemanticType($data->idSuperType);
                        $this->setIdDomain($superType->getIdDomain());
                        Base::createEntityRelation($entity->getId(), 'rel_subtypeof', $superType->getIdEntity());
                    }
                }
                Timeline::addTimeline("semantictype", $this->getId(), "S");
                parent::save();
                $transaction->commit();
                return $this->getId();
            } catch (\Exception $e) {
                $transaction->rollback();
                throw new \Exception($e->getMessage());
            }
        }

        public function delete()
        {
            $transaction = $this->beginTransaction();
            try {
                $hasChildren = (count($this->listChildren($this->getId(), (object)[])->asQuery()->getResult()) > 0);
                if ($hasChildren) {
                    throw new \Exception("Type has subtypes; it can't be removed.");
                } else {
                    $entry = new Entry();
                    $entry->deleteEntry($this->getEntry());
                    Timeline::addTimeline("semantictype", $this->getId(), "D");
                    Base::deleteAllEntityRelation($this->getIdEntity());
                    parent::delete();
                    $entity = new Entity($this->getIdEntity());
                    $entity->delete();
                    $entry = new Entry();
                    $entry->deleteEntry($this->getEntry());
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                $transaction->rollback();
                throw new \Exception($e->getMessage());
            }
        }


        public function updateEntry($newEntry)
        {
            $transaction = $this->beginTransaction();
            try {
                $entity = new Entity($this->getIdEntity());
                $entity->setAlias($newEntry);
                $entity->save();
                $entry = new Entry();
                $entry->updateEntry($this->getEntry(), $newEntry);
                $this->setEntry($newEntry);
                parent::save();
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollback();
                throw new \Exception($e->getMessage());
            }
        }

        public function addEntity($idEntity)
        {
            Base::createEntityRelation($idEntity, 'rel_hassemtype', $this->getIdEntity());
        }

        public function delSemanticTypeFromEntity($idEntity, $idSemanticTypeEntity = [])
        {
            $rt = new RelationType();
            $c = $rt->getCriteria()->select('idRelationType')->where("entry = 'rel_hassemtype'");
            $er = new EntityRelation();
            $transaction = $er->beginTransaction();
            $criteria = $er->getDeleteCriteria();
            $criteria->where("idEntity1 = {$idEntity}");
            $criteria->where("idEntity2", "IN", $idSemanticTypeEntity);
            $criteria->where("idRelationType", "=", $c);
            $criteria->delete();
            $transaction->commit();
        }
    */
}


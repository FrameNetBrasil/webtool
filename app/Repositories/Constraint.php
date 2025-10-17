<?php

namespace App\Repositories;

use App\Database\Criteria;

class Constraint
{
    public static function listByIdConstrained(int $idConstrained)
    {
        $constraints = Criteria::byFilterLanguage("view_constrainedby", ["idConstrained", "=", $idConstrained])->all();
        $result = [];
        foreach ($constraints as $constraint) {
            $result[] = (object)[
                'type' => $constraint->constrainedByType,
                'constraintName' => $constraint->conName,
                'idConstraint' => $constraint->idConstraint,
                'idConstrained' => $constraint->idConstrained,
                'idConstrainedBy' => $constraint->idConstrainedBy,
                'idConstrainedByName' => $constraint->name,
                'idConstraintInstance' => $constraint->idConstraintInstance
            ];
        }
        return $result;
    }

////        $idLanguage = AppService::getCurrentIdLanguage();
////        $cmd = <<<HERE
////SELECT constraints.*, er.name relationName
////FROM (
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2fe.name, e2fe.entry as cxEntry, e2fe.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_frame e2f ON (c.idConstrainedBy = e2f.idEntity)
////JOIN view_entrylanguage e2fe ON (e2f.entry = e2fe.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e2fe.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2ce.name, e2ce.entry as cxEntry, e2ce.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_construction e2c ON (c.idConstrainedBy = e2c.idEntity)
////JOIN view_entrylanguage e2ce ON (e2c.entry = e2ce.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e2ce.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2se.name, e2se.entry as cxEntry, e2se.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_semantictype e2s ON (c.idConstrainedBy = e2s.idEntity)
////JOIN view_entrylanguage e2se ON (e2s.entry = e2se.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e2se.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2ce2.name, e2ce2.entry as cxEntry, e2ce2.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_constructionelement e2cel ON (c.idConstrainedBy = e2cel.idEntity)
////JOIN view_entrylanguage e2ce2 ON (e2cel.entry = e2ce2.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e2ce2.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2cne.name, e2cne.entry as cxEntry, e2cne.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_constraint e2cn ON (c.idConstrainedBy = e2cn.idConstraint)
////JOIN view_entrylanguage e2cne ON (e2cn.entry = e2cne.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e2cne.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e3ce.name, e3ce.entry as cxEntry, e3ce.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN view_constraint e3cn ON (c.idConstrainedBy = e3cn.idConstraint)
////JOIN view_construction e3c ON (e3cn.idConstrainedBy = e3c.idEntity)
////JOIN view_entrylanguage e3ce ON (e3c.entry = e3ce.entry)
////WHERE (c.idConstrained = {$idConstrained})
////AND (e3ce.idLanguage = {$idLanguage})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lex.name, e2lex.name  as cxEntry, e2lex.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN lexeme e2lex ON (c.idConstrainedBy = e2lex.idEntity)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lem.name, e2lem.name  as cxEntry, e2lem.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN lemma e2lem ON (c.idConstrainedBy = e2lem.idEntity)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lun.name, e2lun.name  as cxEntry, e2lun.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN lu e2lun ON (c.idConstrainedBy = e2lun.idEntity)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, concat(e2udfti.info,':',e2udf.info) as info, e2udf.info  as cxEntry, e2udf.info as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN udfeature e2udf ON (c.idConstrainedBy = e2udf.idEntity)
////JOIN typeinstance e2udfti ON (e2udf.idTypeInstance = e2udfti.idTypeInstance)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2udr.info, e2udr.info  as cxEntry, e2udr.info as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN udrelation e2udr ON (c.idConstrainedBy = e2udr.idEntity)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, lower(e2udp.POS), e2udp.entry  as cxEntry, e2udp.entry as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
////FROM view_constraint c
////JOIN udpos e2udp ON (c.idConstrainedBy = e2udp.idEntity)
////WHERE (c.idConstrained = {$idConstrained})
////UNION
////SELECT er.idEntity3, er.idEntity1, er.idEntity2, e2qla.entry, e2fe.name, e2fe.name as cxEntry, e2fe.entry as nick, '', '', er.idEntityRelation
////FROM entityrelation er
////JOIN qualia e2qla ON (er.idEntity3 = e2qla.idEntity)
////JOIN view_frameelement fe ON (er.idEntity2 = fe.idEntity)
////JOIN entry e2fe ON (fe.idEntity = e2fe.idEntity)
////WHERE (er.idEntity1 = {$idConstrained})
////AND (e2fe.idLanguage = {$idLanguage})
////) constraints
////JOIN entry er on (constraints.entry = er.entry)
////WHERE (er.idLanguage = {$idLanguage})
////UNION
////SELECT er.idEntityRelation, er.idEntity1, er.idEntity2, rt.entry, e2fe.name, e2fe.name as cxEntry, e2fe.entry as nick, '', '', er.idEntityRelation,rt.entry
////FROM entityrelation er
////JOIN relationtype rt on (er.idRelationType = rt.idRelationType)
////JOIN view_frameelement fe ON (er.idEntity2 = fe.idEntity)
////JOIN entry e2fe ON (fe.idEntity = e2fe.idEntity)
////WHERE (er.idEntity1 = {$idConstrained})
////AND (rt.entry = 'rel_festandsforfe')
////AND (e2fe.idLanguage = {$idLanguage})
////UNION
////SELECT er.idEntityRelation, er.idEntity1, er.idEntity2, lu.name, lu.name, lu.name as cxEntry, lu.name as nick, '', '', er.idEntityRelation,rt.entry
////FROM entityrelation er
////JOIN relationtype rt on (er.idRelationType = rt.idRelationType)
////JOIN lu ON (er.idEntity2 = lu.idEntity)
////WHERE (er.idEntity1 = {$idConstrained})
////AND (rt.entry = 'rel_festandsforlu')
////UNION
////SELECT er.idEntityRelation, er.idEntity1, er.idEntity2, lu.name, lu.name, lu.name as cxEntry, lu.name as nick, '', '', er.idEntityRelation,rt.entry
////FROM entityrelation er
////JOIN relationtype rt on (er.idRelationType = rt.idRelationType)
////JOIN lu ON (er.idEntity2 = lu.idEntity)
////WHERE (er.idEntity1 = {$idConstrained})
////AND (rt.entry = 'rel_lustandsforlu')
////UNION
////SELECT er.idEntityRelation, er.idEntity1, er.idEntity2, lu.name, lu.name, lu.name as cxEntry, lu.name as nick, '', '', er.idEntityRelation,rt.entry
////FROM entityrelation er
////JOIN relationtype rt on (er.idRelationType = rt.idRelationType)
////JOIN lu ON (er.idEntity2 = lu.idEntity)
////WHERE (er.idEntity1 = {$idConstrained})
////AND (rt.entry = 'rel_luequivalence')
////HERE;
////        //$query = $this->getDb()->getQueryCommand($cmd);
////        $result = [];
////        $constraints = self::query($cmd);
//        foreach ($constraints as $i => $constraint) {
//            $result[$i] = [];
//            $result[$i]['name'] = $constraint->name;
//            $result[$i]['prefix'] = $constraint->prefix . '_' . $constraint->name;
//            $result[$i]['type'] = $constraint->constrainedByType;
//            $result[$i]['entry'] = $constraint->cxEntry;
//            $result[$i]['constraintType'] = $constraint->entry;
//            $result[$i]['constraintName'] = $constraint->relationName;
//            $result[$i]['idConstraint'] = $constraint->idConstraint;
//            $result[$i]['idConstraintInstance'] = $constraint->idConstraintInstance;
//        }
//        return $result;


//    public function getByIdConstrainedSet($idConstrainedSet)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//        $idConstrainedSetString = implode(',' , $idConstrainedSet);
//        $cmd = <<<HERE
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2fe.name, e2fe.entry as cxEntry, e2fe.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN view_frame e2f ON (c.idConstrainedBy = e2f.idEntity)
//JOIN view_entrylanguage e2fe ON (e2f.entry = e2fe.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e2fe.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2ce.name, e2ce.entry as cxEntry, e2ce.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN View_Construction e2c ON (c.idConstrainedBy = e2c.idEntity)
//JOIN view_entrylanguage e2ce ON (e2c.entry = e2ce.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e2ce.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2se.name, e2se.entry as cxEntry, e2se.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN View_SemanticType e2s ON (c.idConstrainedBy = e2s.idEntity)
//JOIN view_entrylanguage e2se ON (e2s.entry = e2se.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e2se.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2ce2.name, e2ce2.entry as cxEntry, e2ce2.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN View_ConstructionElement e2cel ON (c.idConstrainedBy = e2cel.idEntity)
//JOIN view_entrylanguage e2ce2 ON (e2cel.entry = e2ce2.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e2ce2.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2cne.name, e2cne.entry as cxEntry, e2cne.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN view_constraint e2cn ON (c.idConstrainedBy = e2cn.idConstraint)
//JOIN view_entrylanguage e2cne ON (e2cn.entry = e2cne.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e2cne.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e3ce.name, e3ce.entry as cxEntry, e3ce.nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN view_constraint e3cn ON (c.idConstrainedBy = e3cn.idConstraint)
//JOIN View_Construction e3c ON (e3cn.idConstrainedBy = e3c.idEntity)
//JOIN view_entrylanguage e3ce ON (e3c.entry = e3ce.entry)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//AND (e3ce.idLanguage = {$idLanguage})
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lex.name, e2lex.name  as cxEntry, e2lex.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN Lexeme e2lex ON (c.idConstrainedBy = e2lex.idEntity)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lem.name, e2lem.name  as cxEntry, e2lem.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN Lemma e2lem ON (c.idConstrainedBy = e2lem.idEntity)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2lun.name, e2lun.name  as cxEntry, e2lun.name as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN LU e2lun ON (c.idConstrainedBy = e2lun.idEntity)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, concat(e2udfti.info,':',e2udf.info) as info, e2udf.info  as cxEntry, e2udf.info as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN UDFeature e2udf ON (c.idConstrainedBy = e2udf.idEntity)
//JOIN TypeInstance e2udfti ON (e2udf.idTypeInstance = e2udfti.idTypeInstance)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, e2udr.info, e2udr.info  as cxEntry, e2udr.info as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN UDRelation e2udr ON (c.idConstrainedBy = e2udr.idEntity)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//UNION
//SELECT c.idConstraint, c.idConstrained, c.idConstrainedBy, c.entry, lower(e2udp.POS), e2udp.entry  as cxEntry, e2udp.entry as nick, c.prefix, c.constrainedByType, c.idConstraintInstance
//FROM view_constraint c
//JOIN UDPOS e2udp ON (c.idConstrainedBy = e2udp.idEntity)
//WHERE (c.idConstrained IN ({$idConstrainedSetString}))
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'];
//            $constraints[$i]['type'] = $constraint['constrainedByType'];
//            $constraints[$i]['entry'] = $constraint['cxEntry'];
//            $constraints[$i]['relationType'] = $constraint['entry'];
//            $constraints[$i]['idConstraint'] = $constraint['idConstraint'];
//            $constraints[$i]['idConstraintInstance'] = $constraint['idConstraintInstance'];
//        }
//        return $constraints;
//    }
//
//    public function getChainByIdConstrained($idConstrained, $idConstrainedBase, &$chain)
//    {
//        $constraints = $this->getByIdConstrained($idConstrained);
//        foreach($constraints as $constraint) {
//            $chain[] = [
//                'idConstrained' => $idConstrainedBase,//$constraint['idConstrained'],
//                'idConstrainedBy' => $constraint['idConstrainedBy'],
//                'idConstraint' => $constraint['idConstraint'],
//                'name' => $constraint['name'],
//                'entry' => $constraint['entry'],
//                'nick' => $constraint['nick'],
//                'type' => $constraint['type'],
//                'relationType' => $constraint['relationType']
//            ];
//            $this->getChainByIdConstrained($constraint['idConstraint'], $constraint['idConstrainedBy'], $chain);
//        }
//    }
//
//    public function hasChild($idConstraint)
//    {
//        $cmd = <<<HERE
//        SELECT c.idConstraint
//        FROM view_constraint c
//        WHERE (c.idConstrained = {$idConstraint})
//HERE;
//        return count($this->getDb()->getQueryCommand($cmd)->getResult()) > 0;
//    }
//
//    public function hasInstanceChild($idConstraintInstance)
//    {
//        $cmd = <<<HERE
//        SELECT c.idConstraint
//        FROM view_constraint c
//        WHERE (c.idConstraintInstance = {$idConstraintInstance})
//HERE;
//        $idConstraint = $this->getDb()->getQueryCommand($cmd)->getResult()[0]['idConstraint'];
//        $cmd = <<<HERE
//        SELECT c.idConstraint
//        FROM view_constraint c
//        WHERE (c.idConstrained = {$idConstraint})
//HERE;
//        return count($this->getDb()->getQueryCommand($cmd)->getResult()) > 0;
//    }
//
//    public function getConstraintData($idConstraint)
//    {
//        $cmd = <<<HERE
//        SELECT *
//        FROM view_constraint
//        WHERE (idConstraint = {$idConstraint})
//HERE;
//        return (object)$this->getDb()->getQueryCommand($cmd)->getResult()[0];
//    }
//
//    public function listLUSTConstraints($idEntityLU)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntity2 as idConstraint,
//            e.name  AS name, r.prefix, r.entity2Type type
//        FROM View_Relation r
//        JOIN SemanticType st ON (r.idEntity2 = st.idEntity)
//        JOIN Entry e ON (st.entry = e.entry)
//        WHERE (r.idEntity1 = {$idEntityLU})
//            AND (r.relationType = 'rel_hassemtype')
//            AND (e.idLanguage = {$idLanguage})
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'];
//            $constraints[$i]['type'] = $constraint['type'];
//        }
//        return $constraints;
//    }
//
//    public function listLUQualiaConstraints($idEntityLU)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntityRelation as idConstraint,
//            relatedLU.name  AS name, r.prefix,
//            r.relationtype  AS qualia,
//            IFNULL(q.info,'-') AS qualiarelation
//        FROM View_Relation r
//        JOIN LU relatedLU ON (r.idEntity2 = relatedLU.idEntity)
//        LEFT JOIN Qualia q on (r.idEntity3 = q.idEntity)
//        WHERE (r.idEntity1 = {$idEntityLU})
//            AND (r.relationGroup = 'rgp_qualia')
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'];
//            $constraints[$i]['type'] = $constraint['qualia'];
//            $constraints[$i]['relation'] = $constraint['qualiarelation'];
//        }
//        return $constraints;
//    }
//
//    public function listLUEquivalenceConstraints($idEntityLU)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntity2 as idConstraint,
//            relatedLU.name  AS name, r.prefix, r.entity2Type type, Language.language,
//            r.relationtype  AS equivalence
//        FROM View_Relation r
//        JOIN View_LU relatedLU ON (r.idEntity2 = relatedLU.idEntity)
//        JOIN Language on (relatedLU.idLanguage = Language.idLanguage)
//        WHERE (r.idEntity1 = {$idEntityLU})
//            AND (r.relationType = 'rel_luequivalence')
//        UNION
//        SELECT r.idEntity1 as idConstraint,
//            relatedLU.name  AS name, r.prefix, r.entity2Type type, Language.language,
//            r.relationtype  AS equivalence
//        FROM View_Relation r
//        JOIN View_LU relatedLU ON (r.idEntity1 = relatedLU.idEntity)
//        JOIN Language on (relatedLU.idLanguage = Language.idLanguage)
//        WHERE (r.idEntity2 = {$idEntityLU})
//            AND (r.relationType = 'rel_luequivalence')
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'] . ' [' . $constraints[$i]['language'] . ']';
//            $constraints[$i]['type'] = $constraint['type'];
//        }
//        return $constraints;
//    }
//
//    public function listLUMetonymyConstraints($idEntityLU)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntity2 as idConstraint,
//            relatedLU.name  AS name, r.prefix, r.entity2Type type,
//            r.relationtype  AS metonymy
//        FROM View_Relation r
//        JOIN LU relatedLU ON (r.idEntity2 = relatedLU.idEntity)
//        WHERE (r.idEntity1 = {$idEntityLU})
//            AND (r.relationType = 'rel_lustandsforlu')
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'];
//            $constraints[$i]['type'] = $constraint['type'];
//        }
//        return $constraints;
//    }
//
//    public function listLUDomainConstraints($idEntityLU)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntity2 as idConstraint,
//            relatedLU.name  AS name, r.prefix, r.entity2Type type,
//            r.relationtype  AS domain
//        FROM View_Relation r
//        JOIN LU relatedLU ON (r.idEntity2 = relatedLU.idEntity)
//        WHERE (r.idEntity1 = {$idEntityLU})
//            AND (r.relationType = 'rel_hasdomain')
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $constraints[$i]['name'];
//            $constraints[$i]['type'] = $constraint['type'];
//        }
//        return $constraints;
//    }
//
//    public function listConstraintsCNCE($idConstraint)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT c2.idConstrained, c2.idConstrainedBy,
//            e2ce1.name ce1Name,
//            e2ce2.name ce2Name
//        FROM view_constraint c1
//        JOIN view_constraint c2 ON (c1.idConstrainedBy = c2.idConstraint)
//        LEFT JOIN View_ConstructionElement ce1 ON (c2.idConstrained = ce1.idEntity)
//        LEFT JOIN view_entrylanguage e2ce1 ON (ce1.entry = e2ce1.entry)
//        LEFT JOIN View_ConstructionElement ce2 ON (c2.idConstrainedBy = ce2.idEntity)
//        LEFT JOIN view_entrylanguage e2ce2 ON (ce2.entry = e2ce2.entry)
//        WHERE (c1.idConstraint = {$idConstraint})
//            AND ((e2ce1.idLanguage = {$idLanguage}) or (e2ce1.idLanguage is null))
//            AND ((e2ce2.idLanguage = {$idLanguage}) or (e2ce2.idLanguage is null))
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraint = $query->getResult()[0];
//        $constraints[0]['idConstraint'] = $constraint['idConstrained'];
//        $constraints[0]['name'] = $constraint['ce1Name'];
//        $constraints[1]['idConstraint'] = $constraint['idConstrainedBy'];
//        $constraints[1]['name'] = $constraint['ce2Name'];
//        return $constraints;
//    }
//
//    public function listConstraintsCNCN($idConstraint)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//select idConstraint, name
//from (
// SELECT cn2.idConstraint, concat(ce1entries.name,'.',ce2entries.name) name
//  FROM View_ConstructionElement ce1 join view_constraint cn1 on (ce1.idEntity = cn1.idConstrained)
// JOIN view_constraint cn2 on (cn1.idConstraint = cn2.idConstrained)
// JOIN View_ConstructionElement ce2 on (cn2.idConstrainedBy = ce2.idEntity)
//  JOIN entry ce1entries on (ce1.entry = ce1entries.entry)
//  JOIN entry ce2entries on (ce2.entry = ce2entries.entry)
//  WHERE (cn1.entry = 'con_cxn')
//  AND (cn2.entry = 'con_element')
//  AND (ce1entries.idLanguage = {$idLanguage})
//  AND (ce2entries.idLanguage = {$idLanguage})
//UNION
//SELECT cn4.idConstraint, concat(ce1entries.name,'.',ce2entries.name,'.',ce3entries.name) name
//  FROM View_ConstructionElement ce1
//  JOIN view_constraint cn1 on (ce1.idEntity = cn1.idConstrained)
//  JOIN view_constraint cn2 on (cn1.idConstraint = cn2.idConstrained)
//  JOIN View_ConstructionElement ce2 on (cn2.idConstrainedBy = ce2.idEntity)
//  JOIN view_constraint cn3 on (cn2.idConstraint = cn3.idConstrained)
//  JOIN view_constraint cn4 on (cn3.idConstraint = cn4.idConstrained)
//  JOIN View_ConstructionElement ce3 on (cn4.idConstrainedBy = ce3.idEntity)
//  JOIN entry ce1entries on (ce1.entry = ce1entries.entry)
//  JOIN entry ce2entries on (ce2.entry = ce2entries.entry)
//  JOIN entry ce3entries on (ce3.entry = ce3entries.entry)
//  WHERE (cn1.entry = 'con_cxn')
//  AND (cn2.entry = 'con_element')
//  AND (cn3.entry = 'con_cxn')
//  AND (cn4.entry = 'con_element')
//  AND (ce1entries.idLanguage = {$idLanguage})
//  AND (ce2entries.idLanguage = {$idLanguage})
//  AND (ce3entries.idLanguage = {$idLanguage})
//) cn
//where idConstraint in (
//select idConstrained from view_constraint
//where (idConstraint = (select idConstrainedBy from view_constraint where idConstraint = {$idConstraint}))
//UNION
//select idConstrainedBy from view_constraint
//where (idConstraint = (select idConstrainedBy from view_constraint where idConstraint = {$idConstraint}))
//)
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        return $constraints;
//    }
//
//    public function listFEMetonymyConstraints($idEntityFE)
//    {
//        $idLanguage = \Manager::getSession()->idLanguage;
//
//        $cmd = <<<HERE
//        SELECT r.idEntity2 as idConstraint,  r.prefix, r.entity2Type type,
//            e.name  AS feName,
//            relatedLU.name as luName,
//            r.relationtype  AS metonymy
//        FROM View_Relation r
//        LEFT JOIN FrameElement relatedFE ON (r.idEntity2 = relatedFE.idEntity)
//        LEFT JOIN Entry e on (relatedFE.entry = e.entry)
//        LEFT JOIN LU relatedLU ON (r.idEntity2 = relatedLU.idEntity)
//        WHERE (r.idEntity1 = {$idEntityFE})
//            AND ((r.relationType = 'rel_festandsforfe') or (r.relationType = 'rel_festandsforlu'))
//            AND ((e.idLanguage = {$idLanguage}) or (e.idLanguage is null))
//
//HERE;
//        $query = $this->getDb()->getQueryCommand($cmd);
//        $constraints = $query->getResult();
//        foreach ($constraints as $i => $constraint) {
//            $name = ($constraint['luName'] != '') ? $constraint['luName'] : $constraint['feName'];
//            $constraints[$i]['name'] = $constraint['prefix'] . '_' . $name;
//            $constraints[$i]['type'] = $constraint['type'];
//        }
//        return $constraints;
//    }
//
//    public function getChainForExport($idConstrained, &$chain)
//    {
//        $constraints = $this->getByIdConstrained($idConstrained);
//        foreach($constraints as $constraint) {
//            $chain[] = [
//                'idConstrained' => $idConstrained,//$constraint['idConstrained'],
//                'idConstrainedBy' => $constraint['idConstrainedBy'],
//                'idConstraint' => $constraint['idConstraint'],
//                'name' => $constraint['name'],
//                'entry' => $constraint['entry'],
//                'nick' => $constraint['nick'],
//                'type' => $constraint['type'],
//                'relationType' => $constraint['relationType']
//            ];
//            $this->getChainForExport($constraint['idConstraint'], $chain);
//        }
//    }
//
//    public function getChainForExportByIdConstrained($idConstrained)
//    {
//        $chain = [];
//        $this->getChainForExport($idConstrained, $chain);
//        ddump($chain);
//        return $chain;
//    }

}

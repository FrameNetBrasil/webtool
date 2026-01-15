<?php

namespace App\Repositories;

use App\Database\Criteria;
use App\Enum\Status;
use App\Services\AppService;
use Illuminate\Support\Facades\DB;

class WordForm
{
//    public static function listByFilter($filter)
//    {
//        $idLanguage = AppService::getCurrentIdLanguage();
//        $criteria = self::getCriteria()
//            ->select(['idWordform','form','md5','idEntity', 'idLexeme'])
//            ->distinct()
//            ->orderBy('form');
//        return self::filter([
//            ['lexeme.idLanguage','=',$idLanguage],
//            ['form','startswith',$filter?->form ?? null],
//            ['idLexeme','=',$filter?->idLexeme ?? null],
//            ['lexeme.name','startswith',$filter?->lexeme ?? null],
//        ], $criteria);
//    }

    /*
    public function listByFilter($filter)
    {
        $criteria = $this->getCriteria()->select('*')->orderBy('idWordForm');
        if ($filter->idWordForm) {
            $criteria->where("idWordForm LIKE '{$filter->idWordForm}%'");
        }
        return $criteria;
    }

    public function listLUByWordForm($wordform)
    {
        $criteria = $this->getCriteria();
        $criteria->select('lexeme.lexemeentries.lemma.lus.idLU');
        //$criteria->where("upper(form) = upper('{$wordform}')");
        $criteria->where("form = lower('{$wordform}') OR (form LIKE lower('{$wordform}-%')) OR (form LIKE lower('%-{$wordform}'))");
        $lus = $criteria->asQuery()->chunkResult('idLU', 'idLU');
        if (count($lus) > 0) {
            $lu = new LU();
            //$criteria = $lu->getCriteria()->select("idLU, concat(frame.entries.name,'.',name) as fullName, locate(' ', name) as mwe");
            $criteria = $lu->getCriteria()->select("idLU, concat(frame.entries.name,'.',name) as fullName, count(lemma.lexemeentries.idLexemeEntry)-1 as mwe");
            //Base::relation($criteria, 'LU', 'Frame frame', 'rel_evokes');
            Base::entryLanguage($criteria, 'frame');
            $criteria->where("idLU", "IN", $lus);
            $criteria->where("lemma.idLanguage", "=", "frame.entries.idLanguage");
            $criteria->groupBy("idLU, concat(entry.name,'.',lu.name)");
            //return $criteria->asQuery()->chunkResult('idLU', 'fullName');
            return $criteria->asQuery()->asObjectArray();
        } else {
            return [];
        }
    }

    public function lookFor($words)
    {
        $criteria = $this->getCriteria()->select('form as i, form');
        $criteria->where("form", "in", $words);
        return $criteria->asQuery()->chunkResult('i', 'form');
    }

    public function listForLookup($wordform = '')
    {
        $idLanguage = \Manager::getSession()->idLanguage;
        $form = trim($wordform);
        $form = (strlen($wordform) == strlen($form)) ? $form . '%' : $form;
        $criteria = $this->getCriteria()->select("idWordForm, concat(form, '  [', lexeme.name, '  ', lexeme.pos.entries.name,']','  [',lexeme.language.language,']') as fullname")->orderBy('form');
        $criteria->where("lexeme.idLanguage = {$idLanguage}");
        $criteria->where("lexeme.pos.entries.idLanguage = {$idLanguage}");
        $criteria->where("form LIKE '{$form}'");
        return $criteria;
    }

    public function listLexemes($words)
    {
        $idLanguage = \Manager::getSession()->idLanguage;
        $criteria = $this->getCriteria()->select('form, lexeme.name as lexeme, lexeme.pos.POS as POSLexeme');
        $criteria->where("form", "in", $words);
        $criteria->where("lexeme.idLanguage", "=", $idLanguage);
        return $criteria->asQuery();
    }

    public function save(): ?int
    {
        parent::save();
        Timeline::addTimeline("wordform", $this->getId(), "S");
        return $this->getId();
    }

    public function saveOffline()
    {
        parent::save();
        Timeline::addTimeline("wordform", $this->getId(), "S");
    }
    */

    public static function wordHasLU(string $wordform): bool
    {
        if (trim($wordform) == '') {
            return false;
        }
        $idLanguage = AppService::getCurrentIdLanguage();
//        $wf1 = md5(mb_strtolower($wordform));
//        debug(strtolower($wordform),md5(strtolower(utf8_encode($wordform))),mb_strtolower($wordform),$wf1);
//        $r = DB::select("
//                    select l.form,count(l.idLU) as n
//                    from view_lexicon l
//                    where (l.md5 = '{$wf1}')
//                    and (idLanguageLM = {$idLanguage})
//                    group by l.form
//                    having count(l.idLU) > 0
//                ");
        $wf1 = mb_strtolower($wordform);
        if ($wf1 == "'") {
            $wf1 = "\'";
        }
        $status = Status::CREATED->value;
        $r = DB::select("
                    select l.form,count(l.idLU) as n
                    from view_lexicon l
                    where (l.form = '{$wf1}' collate 'utf8mb4_bin' )
                    and (idLanguage = {$idLanguage})
                    and (statusLU = '{$status}')
                    group by l.form
                    having count(l.idLU) > 0
                ");
        return !empty($r);
    }

    public static function hasLU(array $wordformList): bool
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $status = Status::CREATED->value;
        $list = [];
        foreach ($wordformList as $wf) {
            if ($wf != '') {
                $wf1 = str_replace("'", "\'", $wf);
                $r = DB::select("
                    select l.form,count(l.idLU) as n
                    from view_lexicon l
                    where (l.form = '{$wf1}')
                    and (idLanguage = {$idLanguage})
                    and (statusLU = '{$status}')
                    group by l.form
                    having count(l.idLU) > 0

                ");
                if (count($r) > 0) {
                    $list[$wf] = $r[0]['n'];
                }
            }
        }
        return !empty($list);
    }

    public static function getLUs(string $wordform, int $idLanguageBase = null)
    {
        if (trim($wordform) == '') {
            return [];
        }
        $idLanguage = AppService::getCurrentIdLanguage();
        $wf1 = mb_strtolower(str_replace("'", "\'", $wordform));
        debug($wf1, md5($wf1));
//        $criteria = Criteria::table("view_lexicon as l")
//            ->select("idLU", "lu", "senseDescription", "frame.name as frameName")
//            ->join("view_frame as frame", "l.idFrame", "=", "frame.idFrame")
//            ->where("l.md5", md5($wf1))
//            ->where("l.idLanguageLM", "=", $idLanguageBase ?? $idLanguage)
//            ->where("l.lexemeOrder", "=", 1)
//            ->where("frame.idLanguage", "=", $idLanguage)
//            ->orderBy("frame.name")
//            ->orderBy("l.lu");

//        select distinct `lu`.`idLU`, `l2`.`lu`, `lu`.`senseDescription`, `frame`.`name` as `frameName`
//from `view_lexicon` as `l1`
//    inner join view_lexicon as l2 on (l1.lemmaName = l2.form)
//         inner join `view_lu` as lu on `l2`.`idLemma` = `lu`.`idLemma`
//         inner join `view_frame` as `frame` on `lu`.`idFrame` = `frame`.`idFrame`
//where l1.form = 'tem' collate 'utf8mb4_bin'
//    and `l1`.`idLanguage` = 1
//    and `l1`.`position` = 1
//    and `frame`.`idLanguage` = 1
//    and `lu`.`status` = 'CREATED'
//order by `frame`.`name` asc, `l2`.`lu` asc;

        $criteria = Criteria::table("view_lexicon as l1")
            ->select("lu.idLU", "lu.name as lu", "lu.senseDescription", "frame.name as frameName","lu.idFrame","frame.idNamespace")
            ->distinct()
            ->join("view_lexicon as l2", "l1.lemmaName","=","l2.form")
            ->join("view_lu as lu", "l2.idLemma","=","lu.idLemma")
            ->join("view_frame as frame", "lu.idFrame", "=", "frame.idFrame")
            ->whereRaw("l1.form = '{$wf1}'  collate 'utf8mb4_bin'")
            ->where("l1.idLanguage", "=", $idLanguageBase ?? $idLanguage)
            ->where("l1.position", "=", 1)
            ->where("frame.idLanguage", "=", $idLanguage)
            ->where("l1.idLanguage", "=", $idLanguage)
            ->where("l2.idLanguage", "=", $idLanguage)
            ->where("lu.status", Status::CREATED)
            ->orderBy("frame.namespace")
            ->orderBy("frame.name")
            ->orderBy("l2.lu")
            ->treeResult('idNamespace');
        return $criteria->all();
    }

    public static function listLU(array $wordformList)
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $list = [];
        foreach ($wordformList as $i => $wf) {
            if ($wf != '') {
                $wf1 = str_replace("'", "\'", $wf);
                $criteria = self::getCriteria()
                    ->distinct();
                $criteria->select([
                    'lexeme.lexemeEntries.lemma.lus.idLU',
                    'lexeme.lexemeEntries.lemma.lus.name',
                    'lexeme.lexemeEntries.lemma.lus.frame.name as frameName'
                ]);
                $criteria->where("form", "=", $wf1);
                $criteria->where("lexeme.lexemeEntries.lemma.idLanguage", "=", $idLanguage);
                $criteria->where("lexeme.lexemeEntries.lemma.lus.frame.idLanguage", "=", $idLanguage);
                $criteria->where("lexeme.lexemeEntries.headWord", "=", 1);
                $criteria->orderBy("lexeme.lexemeEntries.lemma.lus.frame.name,lexeme.lexemeEntries.lemma.lus.name");
                $r = $criteria->all();
                if (count($r)) {
                    $list[$wf] = $r;
                }
            }
        }
        return $list;
    }

}


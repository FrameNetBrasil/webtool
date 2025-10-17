<?php

namespace App\Repositories;

use App\Database\Criteria;

class Lexeme {

    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("lexeme", ['idLexeme', '=', $id])->first();
    }
    /*
    public function getIdEntity()
    {
        $idEntity = parent::getIdEntity();
        if ($idEntity == '') {
            $entity = new Entity();
            $alias = 'lexeme_' . $this->name . '_' . md5($this->name) . '_'. $this->idLexeme. '_' . $this->idLanguage;
            $entity->getByAlias($alias);
            if ($entity->getIdEntity()) {
                throw new \Exception("This Lexeme already exists!.");
            } else {
                $entity->setAlias($alias);
                $entity->setType('LX');
                $entity->save();
                $idEntity = $entity->getId();
                $this->setIdEntity($idEntity);
                parent::save();
            }
        }
        return $idEntity;
    }

    public function getByName($name, $idLanguage, $idPOS)
    {
        $criteria = $this->getCriteria()->select("idLexeme, name");
        $criteria->where("name = lower('{$name}')");
        $criteria->where("idLanguage = {$idLanguage}");
        $criteria->where("idPOS = {$idPOS}");
        return $criteria;
    }

    public function listByFilter($filter){
        $select = ['idLexeme','name','idEntity','idPOS','idUDPOS','idLanguage','pos.POS'];
        $order = "name";
        $criteria = $this->getCriteria();
        if (isset($filter->idLexeme)) {
            $criteria->where("idLexeme", "=", $filter->idLexeme);
        } else {
            if (isset($filter->idLemma)) {
                $criteria->where("lemmas.idLemma", "=", $filter->idLemma);
                $criteria->where("lexemeEntries.idLemma", "=", $filter->idLemma);
                $select[] = 'lexemeEntries.idLexemeEntry';
                $select[] = 'lexemeEntries.breakBefore';
                $select[] = 'lexemeEntries.headWord';
                $select[] = 'lexemeEntries.lexemeOrder';
                $order = 'lexemeEntries.lexemeOrder';
            } else {
                if (isset($filter->lexeme)) {
                    if (str_contains($filter->lexeme, '"')) {
                        $lexeme = str_replace('"', '', $filter->lexeme);
                        $criteria->where("name", "=", $lexeme);
                    } else {
                        if (strlen($filter->lexeme) > 2) {
                            $criteria->where("name", "startswith", $filter->lexeme);
                        } else {
                            $criteria->where("name", "startswith", '-none');
                        }
                    }
                } else {
                    $criteria->where("name", "startswith", '-none');
                }
            }
        }

        if (isset($filter->idLanguage)) {
            $criteria->where("idLanguage", "=", $filter->idLanguage);
        }
        $criteria
            ->select($select)
            ->orderBy($order);
        return $criteria;
    }

    public function listForGridLemma($filter){
        $criteria = $this->getCriteria()->select("idLexeme, concat(name,'.',pos.POS, '  [',language.language, ']') as fullname")->orderBy('name');
        if ($filter->lexeme){
            $criteria->where("upper(name) = upper('{$filter->lexeme}')");
        }
        if ($filter->language){
            $criteria->where("language.language = '{$filter->language}'");
        }
        return $criteria;
    }

    public function listForLookup($lexeme = '')
    {
        $idLanguage = \Manager::getSession()->idLanguage;
        $name = trim($lexeme);
        $name = (strlen($lexeme) == strlen($name)) ? $name . '%' : $name;
        $criteria = $this->getCriteria()->select("idLexeme, concat(name,'  [',pos.entries.name,']','  [',language.language,']') as fullname")->orderBy('name');
        $criteria->where("idLanguage = {$idLanguage}");
        $criteria->where("pos.entries.idLanguage = {$idLanguage}");
        $criteria->where("name LIKE '{$name}'");
        return $criteria;
    }

    /*
    public function save($data = NULL) {
        try {
            $transaction = $this->beginTransaction();
            if ($data != NULL) {
                $this->setData($data);
            }
            parent::save();
            Timeline::addTimeline("lexeme",$this->getId(),"S");
            $wordform = new WordForm();
            $wordform->setIdLexeme($this->getId());
            if ($data != NULL) {
                foreach ($data->listWordform as $wf) {
                    $wordform->setPersistent(false);
                    $wordform->setForm($wf->wordform);
                    //$wordform->save();
                }
            } else {
                $wordform->setForm($this->getName());
                $wordform->save();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();
            throw new \Exception($e->getMessage());
        }
    }
    */

    /*
    public function createLexemeWordform($row, $wf, $POS, $idLanguage) {
        $collate = \Manager::getDatabase(\Manager::getConf('fnbr.db'))->getConfig('collate');
        $fields = explode(' ', $row);
        ddump($fields);
        $idPOS = $POS[$fields[1]];
        //print_r('idPOS = ' . $idPOS . "\n");
        if ($idPOS != '') {
            $l = str_replace("'","\'", $fields[2]);
            $lexeme = $this->getCriteria()->select('idLexeme')
                ->where("(name = '{$l}' collate {$collate}) and (idPOS = {$idPOS}) and (idLanguage = {$idLanguage})")->asQuery()->getResult();
            $idLexeme = $lexeme[0]['idLexeme'];
            if ($idLexeme == '') {
                $this->setPersistent(false);
                $this->setData((object)['name' => $fields[2], 'idLanguage' => $idLanguage, 'idPOS' => $idPOS]);
                parent::save();
                Timeline::addTimeline("lexeme",$this->getId(),"S");
                $idLexeme = $this->getId();
            }
            $w = str_replace("'","\'", $fields[0]);
            $wordform = $wf->getCriteria()->select('idWordform')
                ->where("(form = '{$w}' collate {$collate}) and (idLexeme = {$idLexeme})")->asQuery()->getResult();
            $idWordform = $wordform[0]['idWordform'];
            if ($idWordform == '') {
                $wf->setPersistent(false);
                $wf->setData((object)['form' => $fields[0], 'md5' => md5($fields[0]), 'idLexeme' => $idLexeme]);
                $wf->save();
            } else {
                $wordform = new WordForm();
                $wordform->getById($idWordform);
                $wordform->setIdLexeme($idLexeme);
                $wordform->setMD5(md5($fields[0]));
                $wordform->save();
            }
        }
        return $idLexeme;
    }

    /**
     * Upload de lexeme+wordform em texto simples (wordform POS lexeme)
     * Parâmetro data informa: idLanguage
     * @param type $data
     * @param type $file
     */
    /*
    public function uploadLexemeWordform($data, $file) {
        $idLanguage = $data->idLanguage;
        $pos = new POS();
        $POS = $pos->listAll()->asQuery()->chunkResult('POS','idPOS');
        $wf = new WordForm();
        $transaction = $this->beginTransaction();
        try {
            $lineNum = 0;
            $rows = file($file->getTmpName());
            foreach ($rows as $row) {
                $lineNum++;
                $row = trim($row);
                if (($row == '') || (substr($row, 0, 2) == "//")) {
                    continue;
                }
                $this->createLexemeWordform($row, $wf, $POS, $idLanguage);
            }
            $transaction->commit();
        } catch (\EModelException $e) {
            // rollback da transação em caso de algum erro
            $transaction->rollback();
            throw new EModelException($e->getMessage() . ' LineNum: '. $lineNum);
        }
        return $result;
    }
    */

    /**
     * Register lexeme+wordform from an array of lines as (wordform POS lexeme)
     * Parâmetro data informa: idLanguage
     * @param type $data
     * @param type $array
     */
    /*
    public function registerLexemeWordform($data, $rows) {
        $idLanguage = $data->idLanguage;
        $pos = new POS();
        $POS = $pos->listAll()->asQuery()->chunkResult('POS','idPOS');
        $wf = new WordForm();
        $transaction = $this->beginTransaction();
        try {
            $lineNum = 0;
            foreach ($rows as $row) {
                $lineNum++;
                $row = trim($row);
                if (($row == '') || (substr($row, 0, 2) == "//")) {
                    continue;
                }
                $this->createLexemeWordform($row, $wf, $POS, $idLanguage);
            }
            $transaction->commit();
        } catch (\EModelException $e) {
            // rollback da transação em caso de algum erro
            $transaction->rollback();
            throw new EModelException($e->getMessage() . ' LineNum: '. $lineNum);
        }
        return $result;
    }
    */
    /**
     * Upload de lexeme+wordform em texto simples (wordform POS lexeme)
     * Parâmetro data informa: idLanguage
     * @param type $data
     * @param type $file
     */
    /*
    public function uploadLexemeWordformOffline($data) {
        $idLanguage = $data->idLanguage;
        $pos = new POS();
        $POS = $pos->listAll()->asQuery()->chunkResult('POS','idPOS');
        $wf = new WordForm();
        $transaction = $this->beginTransaction();
        try {
            $lineNum = 0;
            $rows = $data->rows;
            foreach ($rows as $row) {
                $lineNum++;
                $row = trim($row);
                if (($row == '') || (substr($row, 0, 2) == "//")) {
                    continue;
                }
                $this->createLexemeWordform($row, $wf, $POS, $idLanguage);
            }
            $transaction->commit();
        } catch (\EModelException $e) {
            // rollback da transação em caso de algum erro
            $transaction->rollback();
            throw new EModelException($e->getMessage() . ' LineNum: '. $lineNum);
        }
    }
    */
}


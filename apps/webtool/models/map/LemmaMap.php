<?php

/**
 * @category   Maestro
 * @package    UFJF
 *  @subpackage fnbr
 * @copyright  Copyright (c) 2003-2013 UFJF (http://www.ufjf.br)
 * @license    http://siga.ufjf.br/license
 * @version
 * @since
 */
// wizard - code section created by Wizard Module

namespace fnbr\models\map;

class LemmaMap extends \MBusinessModel
{

    public static function ORMMap()
    {

        return array(
            'class' => \get_called_class(),
            'database' => \Manager::getConf('fnbr.db'),
            'table' => 'lemma',
            'attributes' => array(
                'idLemma' => array('column' => 'idLemma', 'key' => 'primary', 'idgenerator' => 'identity', 'type' => 'integer'),
                'name' => array('column' => 'name', 'type' => 'string'),
                'idPOS' => array('column' => 'idPOS', 'type' => 'integer'),
                'idLanguage' => array('column' => 'idLanguage', 'type' => 'integer'),
                'idEntity' => array('column' => 'idEntity','type' => 'integer'),
            ),
            'associations' => array(
                'pos' => array('toClass' => 'fnbr\models\POS', 'cardinality' => 'oneToOne', 'keys' => 'idPOS:idPOS'),
                'lus' => array('toClass' => 'fnbr\models\LU', 'cardinality' => 'oneToMany', 'keys' => 'idLemma:idLemma'),
                'lexemeentries' => array('toClass' => 'fnbr\models\LexemeEntry', 'cardinality' => 'oneToMany', 'keys' => 'idLemma:idLemma'),
                'language' => array('toClass' => 'fnbr\models\Language', 'cardinality' => 'oneToOne' , 'keys' => 'idLanguage:idLanguage'), 
                'entity' => array('toClass' => 'fnbr\models\Entity', 'cardinality' => 'oneToOne' , 'keys' => 'idEntity:idEntity'),
            )
        );
    }

    /**
     * 
     * @var integer 
     */
    protected $idLemma;

    /**
     * 
     * @var string 
     */
    protected $name;

    /**
     * 
     * @var integer 
     */
    protected $idPOS;

    /**
     * 
     * @var integer 
     */
    protected $idLanguage;
    /**
     *
     * @var integer
     */
    protected $idEntity;

    /**
     * Associations
     */
    protected $entity;
    protected $pos;
    protected $lus;
    protected $lexemeentries;

    /**
     * Getters/Setters
     */
    public function getIdLemma()
    {
        return $this->idLemma;
    }

    public function setIdLemma($value)
    {
        $this->idLemma = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getIdPOS()
    {
        return $this->idPOS;
    }

    public function setIdPOS($value)
    {
        $this->idPOS = $value;
    }

    public function getIdLanguage() {
        return $this->idLanguage;
    }

    public function setIdLanguage($value) {
        $this->idLanguage = $value;
    }

    public function getIdEntity() {
        return $this->idEntity;
    }

    public function setIdEntity($value) {
        $this->idEntity = $value;
    }

    /**
     *
     * @return Association
     */
    public function getEntity() {
        if (is_null($this->entity)){
            $this->retrieveAssociation("entity");
        }
        return  $this->entity;
    }
    /**
     *
     * @param Association $value
     */
    public function setEntity($value) {
        $this->entity = $value;
    }
    /**
     *
     * @return Association
     */
    public function getAssociationEntity() {
        $this->retrieveAssociation("entity");
    }
    /**
     *
     * @return Association
     */
    public function getPos()
    {
        if (is_null($this->pos)) {
            $this->retrieveAssociation("pos");
        }
        return $this->pos;
    }

    /**
     *
     * @param Association $value
     */
    public function setPos($value)
    {
        $this->pos = $value;
    }

    /**
     *
     * @return Association
     */
    public function getAssociationPos()
    {
        $this->retrieveAssociation("pos");
    }

    /**
     *
     * @return Association
     */
    public function getLus()
    {
        if (is_null($this->lus)) {
            $this->retrieveAssociation("lus");
        }
        return $this->lus;
    }

    /**
     *
     * @param Association $value
     */
    public function setLus($value)
    {
        $this->lus = $value;
    }

    /**
     *
     * @return Association
     */
    public function getAssociationLus()
    {
        $this->retrieveAssociation("lus");
    }

    /**
     *
     * @return Association
     */
    public function getLexemeentries()
    {
        if (is_null($this->lexemeentries)) {
            $this->retrieveAssociation("lexemeentries");
        }
        return $this->lexemeentries;
    }

    /**
     *
     * @param Association $value
     */
    public function setLexemeentries($value)
    {
        $this->lexemeentries = $value;
    }

    /**
     *
     * @return Association
     */
    public function getAssociationLexemeentries()
    {
        $this->retrieveAssociation("lexemeentries");
    }

    /**
     *
     * @return Association
     */
    public function getLanguage() {
        if (is_null($this->language)){
            $this->retrieveAssociation("language");
        }
        return  $this->language;
    }
    /**
     *
     * @param Association $value
     */
    public function setLanguage($value) {
        $this->language = $value;
    }
    /**
     *
     * @return Association
     */
    public function getAssociationLanguage() {
        $this->retrieveAssociation("language");
    }
    
}

// end - wizard

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

class FrameElementMap extends \MBusinessModel {

    
    public static function ORMMap() {

        return array(
            'class' => \get_called_class(),
            'database' => \Manager::getConf('fnbr.db'),
            'table' => 'frameelement',
            'attributes' => array(
                'idFrameElement' => array('column' => 'idFrameElement','key' => 'primary','idgenerator' => 'identity','type' => 'integer'),
                'entry' => array('column' => 'entry','type' => 'string'),
                'active' => array('column' => 'active','type' => 'integer'),
                'coreType' => array('column' => 'coreType','type' => 'string'),
                'idEntity' => array('column' => 'idEntity','type' => 'integer'),
                'idFrame' => array('column' => 'idFrame','type' => 'integer'),
                'idColor' => array('column' => 'idColor','type' => 'integer'),
            ),
            'associations' => array(
                'entity' => array('toClass' => 'fnbr\models\Entity', 'cardinality' => 'oneToOne' , 'keys' => 'idEntity:idEntity'),
                'frame' => array('toClass' => 'fnbr\models\Frame', 'cardinality' => 'oneToOne' , 'keys' => 'idFrame:idFrame'),
                'color' => array('toClass' => 'fnbr\models\Color', 'cardinality' => 'oneToOne' , 'keys' => 'idColor:idColor'),
                'entries' => array('toClass' => 'fnbr\models\Entry', 'cardinality' => 'oneToMany' , 'keys' => 'entry:entry'),
                'typeinstance' => array('toClass' => 'fnbr\models\TypeInstance', 'cardinality' => 'oneToOne' , 'keys' => 'coreType:entry'),
            )
        );
    }
    
    /**
     * 
     * @var integer 
     */
    protected $idFrameElement;
    /**
     * 
     * @var string 
     */
    protected $entry;
    /**
     * 
     * @var integer 
     */
    protected $active;
    /**
     *
     * @var string
     */
    protected $coreType;
    /**
     * 
     * @var integer 
     */
    protected $idEntity;
    /**
     *
     * @var integer
     */
    protected $idFrame;
    /**
     * 
     * @var integer 
     */
    protected $idColor;

    /**
     * Associations
     */
    protected $entity;
    protected $color;
    protected $frame;
    protected $entries;
    

    /**
     * Getters/Setters
     */
    public function getIdFrameElement() {
        return $this->idFrameElement;
    }

    public function setIdFrameElement($value) {
        $this->idFrameElement = $value;
    }

    public function getEntry() {
        return $this->entry;
    }

    public function setEntry($value) {
        $this->entry = $value;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($value) {
        $this->active = $value;
    }

    public function getCoreType() {
        return $this->coreType;
    }

    public function setCoreType($value) {
        $this->coreType = $value;
    }

    public function getIdEntity() {
        return $this->idEntity;
    }

    public function setIdEntity($value) {
        $this->idEntity = $value;
    }

    public function getIdColor() {
        return $this->idColor;
    }

    public function setIdColor($value) {
        $this->idColor = $value;
    }

    public function getIdFrame() {
        return $this->idFrame;
    }

    public function setIdFrame($value) {
        $this->idFrame = $value;
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
    public function getColor() {
        if (is_null($this->color)){
            $this->retrieveAssociation("color");
        }
        return  $this->color;
    }
    /**
     *
     * @param Association $value
     */
    public function setColor($value) {
        $this->color = $value;
    }
    /**
     *
     * @return Association
     */
    public function getAssociationColor() {
        $this->retrieveAssociation("color");
    }
    /**
     *
     * @return Association
     */
    public function getFrame() {
        if (is_null($this->frame)){
            $this->retrieveAssociation("frame");
        }
        return  $this->frame;
    }
    /**
     *
     * @param Association $value
     */
    public function setFrame($value) {
        $this->frame = $value;
    }
    /**
     *
     * @return Association
     */
    public function getAssociationFrame() {
        $this->retrieveAssociation("frame");
    }
    /**
     *
     * @return Association
     */
    public function getEntries() {
        if (is_null($this->entries)){
            $this->retrieveAssociation("entries");
        }
        return  $this->entries;
    }
    /**
     *
     * @param Association $value
     */
    public function setEntries($value) {
        $this->entries = $value;
    }
    /**
     *
     * @return Association
     */
    public function getAssociationEntries() {
        $this->retrieveAssociation("entries");
    }

    

}
// end - wizard

?>
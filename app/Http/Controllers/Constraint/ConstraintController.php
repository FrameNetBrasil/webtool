<?php

namespace App\Http\Controllers\Constraint;

use App\Data\FE\ConstraintData as FEConstraintData;
use App\Data\LU\ConstraintData as LUConstraintData;
use App\Data\CE\ConstraintData as CEConstraintData;
use App\Data\Construction\ConstraintData as CxnConstraintData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Concept;
use App\Repositories\Construction;
use App\Repositories\ConstructionElement;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Repositories\Lexicon;
use App\Repositories\LU;
use App\Repositories\Qualia;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class ConstraintController extends Controller
{
    /*------
        FE
     -------*/
    #[Post(path: '/constraint/fe/{id}')]
    public function constraintFE(FEConstraintData $data)
    {
        debug($data);
        try {
            $fe = FrameElement::byId($data->idFrameElement);
            if ($data->idFrameConstraint > 0) {
                $constraintEntry = 'rel_constraint_frame';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $frame = Frame::byId($data->idFrameConstraint);
                RelationService::create($constraintEntry, $idConstraint, $fe->idEntity, $frame->idEntity);
                debug("Creating frame constraint");
            }
            if ($data->idFEQualiaConstraint > 0) {
                $constraintEntry = 'rel_qualia';
                $feQualia = FrameElement::byId($data->idFEQualiaConstraint);
                $qualia = Qualia::byId($data->idQualiaConstraint);
                RelationService::create($constraintEntry, $fe->idEntity, $feQualia->idEntity, $qualia->idEntity);
                debug("Creating qualia constraint");
            }
            if ($data->idFEMetonymConstraint > 0) {
                $constraintEntry = 'rel_festandsforfe';
                $feMetonym = FrameElement::byId($data->idFEMetonymConstraint);
                RelationService::create($constraintEntry, $fe->idEntity, $feMetonym->idEntity);
                debug("Creating fe metonym constraint");
            }
            if ($data->idLUMetonymConstraint > 0) {
                $constraintEntry = 'rel_festandsforlu';
                $luMetonym = LU::byId($data->idLUMetonymConstraint);
                RelationService::create($constraintEntry, $fe->idEntity, $luMetonym->idEntity);
                debug("Creating lu metonym constraint");
            }
            $this->trigger('reload-gridConstraintFE');
            return $this->renderNotify("success", "Constraint created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/constraint/fe/{idConstraintInstance}')]
    public function deleteConstraintFE(int $idConstraintInstance)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idConstraintInstance)->delete();
            $this->trigger('reload-gridConstraintFE');
            return $this->renderNotify("success", "Constraint deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    /*------
       LU
    -------*/
    #[Post(path: '/constraint/lu/{id}')]
    public function constraintLU(LUConstraintData $data)
    {
        try {
            debug($data);
            $lu = LU::byId($data->idLU);
            if ($data->idLUMetonymConstraint > 0) {
                $constraintEntry = 'rel_lustandsforlu';
                $luMetonym = LU::byId($data->idLUMetonymConstraint);
                RelationService::create($constraintEntry, $lu->idEntity, $luMetonym->idEntity);
            }
            if ($data->idLUEquivalenceConstraint > 0 ) {
                $constraintEntry = 'rel_luequivalence';
                $luEquivalence = LU::byId($data->idLUEquivalenceConstraint);
                RelationService::create($constraintEntry, $lu->idEntity, $luEquivalence->idEntity);
            }
            $this->trigger('reload-gridConstraintLU');
            return $this->renderNotify("success", "Constraint created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/constraint/lu/{idConstraintInstance}')]
    public function deleteConstraintLU(int $idConstraintInstance)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idConstraintInstance)->delete();
            $this->trigger('reload-gridConstraintLU');
            return $this->renderNotify("success", "Constraint deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    /*------
       CE
    -------*/

    #[Post(path: '/constraint/ce/{id}')]
    public function constraintCE(CEConstraintData $data)
    {
        debug($data);
        try {
            $ce = ConstructionElement::byId($data->idConstructionElement);
            if ($data->idConstructionConstraint > 0) {
                $constraintEntry = 'rel_constraint_cxn';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Construction::byId($data->idConstructionConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idFrameConstraint > 0) {
                $constraintEntry = 'rel_constraint_frame';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Frame::byId($data->idFrameConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idFrameFamilyConstraint > 0) {
                $constraintEntry = 'rel_constraint_framefamily';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Frame::byId($data->idFrameFamilyConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idLUConstraint > 0) {
                $constraintEntry = 'rel_constraint_lu';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = LU::byId($data->idLUConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idLemmaConstraint > 0) {
                $constraintEntry = 'rel_constraint_lemma';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Lexicon::lemmaById($data->idLemmaConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idWordFormConstraint > 0) {
                $constraintEntry = 'rel_constraint_wordform';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Lexicon::byId($data->idWordFormConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idMorphemeConstraint > 0) {
                $constraintEntry = 'rel_constraint_morpheme';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Lexicon::byId($data->idMorphemeConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idUDRelationConstraint > 0) {
                $constraintEntry = 'rel_constraint_udrelation';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Criteria::byId("udrelation","idUDRelation",$data->idUDRelationConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idUDFeatureConstraint > 0) {
                $constraintEntry = 'rel_constraint_udfeature';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Criteria::byId("udfeature","idUDFeature",$data->idUDFeatureConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idUDPOSConstraint > 0) {
                $constraintEntry = 'rel_constraint_udpos';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Criteria::byId("udpos","idUDPOS",$data->idUDPOSConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idBeforeCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_before';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idBeforeCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idAfterCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_after';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idAfterCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idMeetsCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_meets';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idMeetsCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idFEConstraint > 0) {
                $relationEntry = 'rel_evokes';
                $fe = FrameElement::byId($data->idFEConstraint);
                RelationService::create($relationEntry, $ce->idEntity, $fe->idEntity);
            }
            if ($data->idConceptConstraint > 0) {
                $constraintEntry = 'rel_constraint_evokes';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Concept::byId($data->idConceptConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idIndexGenderCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_ugender';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idIndexGenderCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idIndexPersonCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_uperson';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idIndexPersonCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            if ($data->idIndexNumberCEConstraint > 0) {
                $constraintEntry = 'rel_constraint_unumber';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = ConstructionElement::byId($data->idIndexNumberCEConstraint);
                RelationService::create($constraintEntry, $idConstraint, $ce->idEntity, $constrainedBy->idEntity);
            }
            $this->trigger('reload-gridConstraintCE');
            return $this->renderNotify("success", "Constraint created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/constraint/ce/{idConstraintInstance}')]
    public function deleteConstraintCE(int $idConstraintInstance)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idConstraintInstance)->delete();
            $this->trigger('reload-gridConstraintCE');
            return $this->renderNotify("success", "Constraint deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    /*------
      Cxn
    -------*/

    #[Post(path: '/constraint/cxn/{id}')]
    public function constraintCxn(CxnConstraintData $data)
    {
        debug($data);
        try {
            $cxn = Construction::byId($data->idConstruction);
            if ($data->idFrameConstraint > 0) {
                $relationEntry = 'rel_evokes';
                $frame = Frame::byId($data->idFrameConstraint);
                RelationService::create($relationEntry, $cxn->idEntity, $frame->idEntity);
            }
            if ($data->idConceptConstraint > 0) {
                $constraintEntry = 'rel_constraint_evokes';
                $idConstraint = Criteria::create("entity",["type" => "CON"]);
                $constrainedBy = Concept::byId($data->idConceptConstraint);
                RelationService::create($constraintEntry, $idConstraint, $cxn->idEntity, $constrainedBy->idEntity);
            }
            $this->trigger('reload-gridConstraintCxn');
            return $this->renderNotify("success", "Constraint created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/constraint/cxn/{idConstraintInstance}')]
    public function deleteConstraintCxn(int $idConstraintInstance)
    {
        try {
            Criteria::table("entityrelation")->where("idEntityRelation", $idConstraintInstance)->delete();
            $this->trigger('reload-gridConstraintCxn');
            return $this->renderNotify("success", "Constraint deleted.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}

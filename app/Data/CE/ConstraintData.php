<?php

namespace App\Data\CE;

use Spatie\LaravelData\Data;

class ConstraintData extends Data
{
    public function __construct(
        public int     $idConstructionElement,
        public ?string $idConstructionConstraint,
        public ?string $idFrameConstraint,
        public ?string $idFrameFamilyConstraint,
        public ?string $idLUConstraint,
        public ?string $idLemmaConstraint,
        public ?string $idWordFormConstraint,
        public ?string $idMorphemeConstraint,
        public ?string $idUDRelationConstraint,
        public ?string $idUDFeatureConstraint,
        public ?string $idUDPOSConstraint,
        public ?string $idBeforeCEConstraint,
        public ?string $idAfterCEConstraint,
        public ?string $idMeetsCEConstraint,
        public ?string $idFEConstraint,
        public ?string $idConceptConstraint,
        public ?string $idIndexGenderCEConstraint,
        public ?string $idIndexPersonCEConstraint,
        public ?string $idIndexNumberCEConstraint,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

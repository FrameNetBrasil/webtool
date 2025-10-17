<?php

namespace App\Data\Construction;

use Spatie\LaravelData\Data;

class ConstraintData extends Data
{
    public function __construct(
        public int     $idConstruction,
        public ?string $idFrameConstraint,
        public ?string $idConceptConstraint,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

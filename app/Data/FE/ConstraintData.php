<?php

namespace App\Data\FE;

use Spatie\LaravelData\Data;

class ConstraintData extends Data
{
    public function __construct(
        public int $idFrameElement,
        public ?string $constraint,
        public ?string $idFrameConstraint = '',
        public ?string $idQualiaConstraint = '',
        public ?string $idFEQualiaConstraint = '',
        public ?string $idFEMetonymConstraint = '',
        public ?string $idLUMetonymConstraint = '',
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

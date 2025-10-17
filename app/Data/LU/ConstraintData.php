<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class ConstraintData extends Data
{
    public function __construct(
        public int $idLU,
        public ?string $constraint,
        public ?string $idLUMetonymConstraint,
        public ?string $idLUEquivalenceConstraint,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

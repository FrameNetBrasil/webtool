<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class QualiaData extends Data
{
    public function __construct(
        public int $idLU,
        public ?string $idQualiaRelation,
        public ?string $idLURelated,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

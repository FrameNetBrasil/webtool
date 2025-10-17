<?php

namespace App\Data\Relation;

use Spatie\LaravelData\Data;

class FEData extends Data
{
    public function __construct(
        public int $idEntityRelation,
        public int $idFrameElement,
        public int $idFrameElementRelated,
    )
    {
    }
}

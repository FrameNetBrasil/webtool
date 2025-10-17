<?php

namespace App\Data\Relation;

use Spatie\LaravelData\Data;

class CEData extends Data
{
    public function __construct(
        public int $idEntityRelation,
        public int $idConstructionElement,
        public int $idConstructionElementRelated,
    )
    {
    }
}

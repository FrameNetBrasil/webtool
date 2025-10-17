<?php

namespace App\Data\Relations;

use Spatie\LaravelData\Data;

class UpdateRelationGroupData extends Data
{
    public function __construct(
        public int $idRelationGroup,
        public string $nameEn,
    )
    {
    }
}

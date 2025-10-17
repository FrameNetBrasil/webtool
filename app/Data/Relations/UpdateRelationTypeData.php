<?php

namespace App\Data\Relations;

use Spatie\LaravelData\Data;

class UpdateRelationTypeData extends Data
{
    public function __construct(
        public string $idRelationType,
        public ?string $nameCanonical = '',
        public ?string $nameDirect = '',
        public ?string $nameInverse = '',
        public ?string $color = '#000000',
        public ?string $prefix = '',
        public ?int $idRelationGroup = 0,
    )
    {
    }
}

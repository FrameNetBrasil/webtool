<?php

namespace App\Data\Relation;

use Spatie\LaravelData\Data;

class UpdateRelationTypeData extends Data
{
    public function __construct(
        public string $idLayerType,
        public ?int $allowsApositional = 0,
        public ?int $isAnnotation = 1,
        public ?int $layerOrder = 1,
        public ?int $idLayerGroup = 0,
    )
    {
    }
}

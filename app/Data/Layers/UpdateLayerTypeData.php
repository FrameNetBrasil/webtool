<?php

namespace App\Data\Layers;

use Spatie\LaravelData\Data;

class UpdateLayerTypeData extends Data
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

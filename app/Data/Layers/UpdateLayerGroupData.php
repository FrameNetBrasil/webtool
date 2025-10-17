<?php

namespace App\Data\Layers;

use Spatie\LaravelData\Data;

class UpdateLayerGroupData extends Data
{
    public function __construct(
        public int $idLayerGroup,
        public string $name,
        public string $type,
        public string $_token = '',
    )
    {
    }
}

<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class EdgeData extends Data
{
    public function __construct(
        public int $idSourceNode,
        public int $idTargetNode,
        public string $edgeType = 'dependency',
        public float $weight = 1.0,
    ) {}
}

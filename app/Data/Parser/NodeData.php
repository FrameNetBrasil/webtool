<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class NodeData extends Data
{
    public function __construct(
        public string $label,
        public string $type,
        public int $threshold = 1,
        public int $activation = 1,
        public bool $isFocus = false,
        public int $positionInSentence = 0,
        public ?int $idMWE = null,
    ) {}
}

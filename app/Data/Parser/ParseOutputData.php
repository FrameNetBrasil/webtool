<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class ParseOutputData extends Data
{
    public function __construct(
        public int $idParserGraph,
        public string $sentence,
        public string $status,
        public array $nodes,
        public array $edges,
        public int $nodeCount,
        public int $edgeCount,
        public int $focusNodeCount,
        public int $mweNodeCount,
        public bool $isValid,
        public ?string $errorMessage = null,
    ) {}
}

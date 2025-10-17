<?php

namespace App\Data\Daisy;

use Spatie\LaravelData\Data;

class DaisyNodeData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public ?int $idFrame = null,
        public ?int $idFrameElement = null,
        public ?int $idLU = null
    )
    {
    }
}

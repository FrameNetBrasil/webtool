<?php

namespace App\Data\Label;

use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?int $startChar = null,
        public ?int $endChar = null,
        public ?int $multi = 0,
        public ?int $idLabelType = null,
        public ?int $idLayer = null,
        public ?int $idInstantiationType = 12, // Normal
    )
    {
    }
}

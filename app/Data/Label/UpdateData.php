<?php

namespace App\Data\Label;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idLabel = null,
        public ?int $startChar = null,
        public ?int $endChar = null,
        public ?int $multi = null,
        public ?int $idLabelType = null,
        public ?int $idLayer = null,
        public ?int $idInstantiationType = null,
    )
    {
    }
}

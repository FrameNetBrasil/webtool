<?php

namespace App\Data\Daisy;

use Spatie\LaravelData\Data;

class DaisyLinkData extends Data
{
    public function __construct(
        public ?float $value = null,
        public ?string $type = null,
        public ?int $idDaisyNodeSource = null,
        public ?int $idDaisyNodeTarget = null
    )
    {
    }
}

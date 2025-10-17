<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class ReframingData extends Data
{
    public function __construct(
        public int $idLU,
        public ?string $senseDescription,
        public ?int $incorporatedFE,
        public ?int $idNewFrame,
        public ?array $idEntityFE,
        public ?array $changeToFE,
    )
    {
    }
}

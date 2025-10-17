<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int $idLU,
        public ?string $senseDescription,
        public ?int $incorporatedFE,
        public ?int $idFrame,
    )
    {
    }
}

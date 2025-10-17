<?php

namespace App\Data\Entity;

use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public string $alias,
        public string $type,
        public ?int $idOld = null,
    )
    {
    }
}

<?php

namespace App\Data\Entry;

use Spatie\LaravelData\Data;

class UpdateSingleData extends Data
{
    public function __construct(
        public int $idEntry,
        public string $name,
        public string $description,
        public int $idLanguage
    )
    {
    }
}

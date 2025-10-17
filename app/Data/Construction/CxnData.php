<?php

namespace App\Data\Construction;

use Spatie\LaravelData\Data;

class CxnData extends Data
{
    public function __construct(
        public int    $idConstruction,
        public string $entry,
        public string $name,
        public string $description,
        public int    $idEntity
    )
    {
    }

}

<?php

namespace App\Data\Microframe;

use Spatie\LaravelData\Data;

class ClassData extends Data
{
    public function __construct(
        public int $idFrame,
        public string $entry,
        public string $name,
        public string $description,
        public int $idEntity
    ) {}

}

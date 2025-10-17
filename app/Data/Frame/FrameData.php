<?php

namespace App\Data\Frame;

use Spatie\LaravelData\Data;

class FrameData extends Data
{
    public function __construct(
        public int    $idFrame,
        public string $entry,
        public string $name,
        public string $description,
        public int    $idEntity
    )
    {
    }

}

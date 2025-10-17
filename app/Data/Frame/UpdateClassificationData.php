<?php

namespace App\Data\Frame;

use Spatie\LaravelData\Data;

class UpdateClassificationData extends Data
{
    public function __construct(
        public int $idFrame,
        public ?array $framalDomain = [],
        public ?array $framalType = [],
    )
    {
    }
}

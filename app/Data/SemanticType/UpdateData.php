<?php

namespace App\Data\SemanticType;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int $idSemanticType,
        public ?string $name = null,
        public ?string $description = null,
        public ?int $idDomain = null,
    ) {}
}

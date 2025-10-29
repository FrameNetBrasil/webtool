<?php

namespace App\Data\Domain;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $domain = '',
        public ?string $semanticType = '',
        public int $id = 0,
        public string $type = '',
    ) {}
}

<?php

namespace App\Data\Parser\Grammar;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?string $language = '',
    ) {}
}

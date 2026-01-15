<?php

namespace App\Data\Docs;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $query = '',
        public ?string $path = null,
    ) {
    }
}

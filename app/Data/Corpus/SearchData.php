<?php

namespace App\Data\Corpus;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?int $id = 0,
        public ?string $type = '',
    ) {}

}

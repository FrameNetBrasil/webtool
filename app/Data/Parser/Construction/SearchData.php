<?php

namespace App\Data\Parser\Construction;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?int $idGrammarGraph = null,
        public ?string $name = '',
        public ?string $constructionType = '',
        public ?bool $enabled = null,
    ) {}
}

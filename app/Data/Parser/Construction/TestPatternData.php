<?php

namespace App\Data\Parser\Construction;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TestPatternData extends Data
{
    public function __construct(
        #[Required]
        public string $pattern = '',

        #[Required]
        public string $sentence = '',

        #[Required]
        public int $idGrammarGraph = 0,
    ) {}
}

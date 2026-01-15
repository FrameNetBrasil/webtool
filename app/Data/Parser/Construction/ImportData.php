<?php

namespace App\Data\Parser\Construction;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ImportData extends Data
{
    public function __construct(
        #[Required]
        public int $idGrammarGraph = 0,

        #[Required]
        public string $file = '',

        public bool $overwrite = false,
    ) {}
}

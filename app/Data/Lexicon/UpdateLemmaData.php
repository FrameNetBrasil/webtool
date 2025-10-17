<?php

namespace App\Data\Lexicon;

use Spatie\LaravelData\Data;

class UpdateLemmaData extends Data
{
    public function __construct(
        public string $idLemma,
        public string $name,
        public int $idPOS,
    )
    {
    }
}

<?php

namespace App\Data\Lexicon;

use Spatie\LaravelData\Data;

class UpdateLexemeData extends Data
{
    public function __construct(
        public string $idLexeme,
        public string $name,
        public int $idPOS,
    )
    {
    }
}

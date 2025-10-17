<?php

namespace App\Data\Lexicon;

use Spatie\LaravelData\Data;

class CreateWordformData extends Data
{
    public function __construct(
        public string $idLexemeWordform,
        public string $form,
        public string $_token = '',
    )
    {
    }
}

<?php

namespace App\Data\Lexicon;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $lemma = '',
        public ?string $form = '',
        public ?string $id = '',
        public ?string $type = '',
        public ?int $idLemma = 0,
        public ?int $idLexicon = 0,
        public string  $_token = '',
    )
    {
        if ($type == 'lemma') {
            $this->idLemma = $id;
        } elseif ($type == 'form') {
            $this->idLexicon = $id;
        }
        $this->_token = csrf_token();
    }

}

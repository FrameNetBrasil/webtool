<?php

namespace App\Data\Lemma;

use Spatie\LaravelData\Data;

class SearchLemmaData extends Data
{
    public function __construct(
        public ?string $lemma = '',
        public ?int $idLemma = 0,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

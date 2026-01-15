<?php

namespace App\Data\Lemma;

use Spatie\LaravelData\Data;

class CreatePOSData extends Data
{
    public function __construct(
        public ?int $idLemma = null,
        public ?int $idUDPOS = null,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

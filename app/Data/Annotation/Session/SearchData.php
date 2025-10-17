<?php

namespace App\Data\Annotation\Session;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $idDocumentSentence = '',
        public ?int $idUser = 0,
        public ?string $type = '',
        public ?int $id = 0,
        public ?string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

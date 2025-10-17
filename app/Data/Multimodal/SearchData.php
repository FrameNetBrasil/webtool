<?php

namespace App\Data\Multimodal;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?string $id = '',
        public ?int $idCorpus = null,
        public ?int $idDocument = null,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

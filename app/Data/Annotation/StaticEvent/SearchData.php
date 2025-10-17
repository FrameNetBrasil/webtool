<?php

namespace App\Data\Annotation\StaticEvent;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?string $idSentence = null,
        public ?string $sentence = '',
        public ?string $id = '',
        public ?int    $idCorpus = null,
        public ?int    $idDocument = null,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

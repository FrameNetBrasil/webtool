<?php

namespace App\Data\Corpus;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?string $id = '',
        public ?string $type = '',
        public ?int $idCorpus = null,
        public ?int $idDocument = null,
        public string  $_token = '',
    )
    {
        if ($type == 'corpus') {
            $this->idCorpus = $id;
        } elseif ($type == 'document') {
            $this->idDocument = $id;
        }
        $this->_token = csrf_token();
    }

}

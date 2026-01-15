<?php

namespace App\Data\Annotation\Browse;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?string $lu = '',
        public ?string $id = '',
        public ?string $type = '',
        public ?int $idCorpus = null,
        public ?int $idDocument = null,
        public ?int $idLU = null,
        public ?int $idDocumentSentence = null,
        public ?string $taskGroupName = null,
        public string $_token = '',
    ) {
        if ($type == 'corpus') {
            $this->idCorpus = $id;
        } elseif ($type == 'document') {
            $this->idDocument = $id;
        } elseif ($type == 'lu') {
            $this->idLU = $id;
        }
        $this->_token = csrf_token();
    }

}

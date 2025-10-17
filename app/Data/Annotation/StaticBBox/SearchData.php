<?php

namespace App\Data\Annotation\StaticBBox;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $corpus = '',
        public ?string $document = '',
        public ?string $id = '',
        public ?int $idCorpus = null,
        public ?int $idDocument = null,
        public ?string $annotation = 'staticbbox',
        public string $_token = '',
    )
    {
        if ($this->id != '') {
            $type = $this->id[0];
            if ($type == 'c') {
                $this->idCorpus = substr($this->id, 1);
            }
            if ($type == 'd') {
                $this->idDocument = substr($this->id, 1);
            }
        }
        $this->_token = csrf_token();
    }
}

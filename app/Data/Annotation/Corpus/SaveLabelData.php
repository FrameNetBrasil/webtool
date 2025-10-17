<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;

class SaveLabelData extends Data
{
    public function __construct(
        public ?int   $idLayer = null,
        public ?int   $idLabelType = null,
        public ?int   $idInstantiationType = null,
        public ?int   $startChar = null,
        public ?int   $endChar = null,
        public string $_token = ''
    )
    {
        $this->_token = csrf_token();
    }

}

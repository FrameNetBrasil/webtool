<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;

class DeleteLabelData extends Data
{
    public function __construct(
        public ?int   $idLabel = null,
        public ?int   $idLayer = null,
        public ?int   $startChar = null,
        public string $_token = ''
    )
    {
        $this->_token = csrf_token();
    }

}

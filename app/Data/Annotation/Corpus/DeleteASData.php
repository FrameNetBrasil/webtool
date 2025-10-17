<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;

class DeleteASData extends Data
{
    public function __construct(
        public ?int   $idAnnotationSet = null,
        public string $_token = ''
    )
    {
        $this->_token = csrf_token();
    }

}

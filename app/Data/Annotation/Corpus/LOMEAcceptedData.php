<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;
class LOMEAcceptedData extends Data
{
    public function __construct(
        public int          $idAnnotationSet,
        public ?string        $corpusAnnotationType = '',
        public ?string        $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

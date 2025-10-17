<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;
class DeleteObjectData extends Data
{
    public function __construct(
        public int          $idAnnotationSet,
        public int          $idEntity,
        public ?string        $token = '',
        public ?string        $corpusAnnotationType = '',
        public ?string        $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

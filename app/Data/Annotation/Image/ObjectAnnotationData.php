<?php

namespace App\Data\Annotation\Image;

use Spatie\LaravelData\Data;

class ObjectAnnotationData extends Data
{
    public function __construct(
        public ?int $idObject = null,
        public ?int $idFrameElement = null,
        public ?int $idLU = null,
        public ?int $idDocument = null,
        public string $annotationType = '',
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Annotation\Image;

use Spatie\LaravelData\Data;

class CreateObjectData extends Data
{
    public function __construct(
        public ?int $idLayerType = null,
        public ?int $idDocument = null,
        public string $annotationType = '',
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }

}

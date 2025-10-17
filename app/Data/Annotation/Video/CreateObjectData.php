<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class CreateObjectData extends Data
{
    public function __construct(
        public ?int $idLayerType = null,
        public ?int $idDocument = null,
        public ?int $startFrame = null,
        public ?int $endFrame = null,
        public string $annotationType = '',
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }

}

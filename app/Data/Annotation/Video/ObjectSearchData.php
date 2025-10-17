<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class ObjectSearchData extends Data
{
    public function __construct(
        public ?int $idObject = 0,
        public ?int $idDocument = 0,
        public ?int $searchIdLayerType = 0,
        public ?string $frame = '',
        public ?string $lu = '',
        public ?string $annotationType = '',
        public ?int $frameNumber = 0,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

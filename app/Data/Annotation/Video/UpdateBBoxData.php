<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class UpdateBBoxData extends Data
{
    public function __construct(
        public ?int $idBoundingBox = null,
        public ?array $bbox = [],
        public string $_token = '',
    ) {
        unset($this->bbox['visible']);
        unset($this->bbox['idDynamicObject']);
        $this->_token = csrf_token();
    }

}

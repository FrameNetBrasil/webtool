<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class CreateBBoxData extends Data
{
    public function __construct(
        public ?int $idObject = null,
        public ?int $frameNumber = null,
        public ?array $bbox = [],
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }

}

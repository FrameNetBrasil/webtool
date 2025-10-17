<?php

namespace App\Data\Annotation\DynamicMode;

use Spatie\LaravelData\Data;

class CreateBBoxData extends Data
{
    public function __construct(
        public ?int   $idDynamicObject = null,
        public ?int   $frameNumber = null,
        public ?array $bbox = [],
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Annotation\DynamicMode;

use Spatie\LaravelData\Data;

class UpdateBBoxData extends Data
{
    public function __construct(
        public ?int   $idBoundingBox = null,
        public ?array $bbox = [],
        public string $_token = '',
    )
    {
        unset($this->bbox['isGroundTruth']);
        unset($this->bbox['visible']);
        $this->_token = csrf_token();
    }

}

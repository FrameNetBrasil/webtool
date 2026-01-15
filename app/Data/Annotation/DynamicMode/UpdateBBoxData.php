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
        // Allow isGroundTruth to be updated (needed when promoting auto-tracked bbox to ground truth)
        unset($this->bbox['visible']);
        $this->_token = csrf_token();
    }

}

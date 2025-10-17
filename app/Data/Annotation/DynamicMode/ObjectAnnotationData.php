<?php

namespace App\Data\Annotation\DynamicMode;

use Spatie\LaravelData\Data;

class ObjectAnnotationData extends Data
{
    public function __construct(
        public ?int   $idDynamicObject = null,
        public ?int   $idFrameElement = null,
        public ?int   $idLU = null,
        public ?int   $idDocument = null,
        public string $_token = '',
        public ?int   $startFrame = null,
        public ?int   $endFrame = null,
        public ?int   $isBlocked = 0,
    )
    {
        $this->_token = csrf_token();
    }

}

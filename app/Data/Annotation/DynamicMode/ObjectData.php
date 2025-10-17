<?php

namespace App\Data\Annotation\DynamicMode;

use Spatie\LaravelData\Data;

class ObjectData extends Data
{
    public function __construct(
        public ?int   $idDynamicObject = null,
        public ?int   $startFrame = null,
        public ?int   $endFrame = null,
        public ?int   $idFrame = null,
        public ?int   $idFrameElement = null,
        public ?int   $idLU = null,
        public ?int   $startTime = null,
        public ?int   $endTime = null,
        public ?int   $origin = null,
        public ?int   $status = 1,
        public ?array $frames = [],
        public ?int   $idDocument = null,
        public ?int   $order = 0,
        public ?string $name = '',
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Annotation\StaticBBox;

use Spatie\LaravelData\Data;

class ObjectData extends Data
{
    public function __construct(
        public ?int   $idStaticObject = null,
        public ?int   $idFrame = null,
        public ?int   $idFrameElement = null,
        public ?int   $idLU = null,
        public ?int   $idDocument = null,
        public ?array $bbox = [],
        public ?int   $order = 0,
        public ?string $name = '',
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

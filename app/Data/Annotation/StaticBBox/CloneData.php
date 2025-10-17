<?php

namespace App\Data\Annotation\StaticBBox;

use Spatie\LaravelData\Data;

class CloneData extends Data
{
    public function __construct(
        public ?int   $idStaticObject = null,
        public ?int   $idDocument = null,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

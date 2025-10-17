<?php

namespace App\Data\Annotation\StaticBBox;

use Spatie\LaravelData\Data;

class UpdateBBoxData extends Data
{
    public function __construct(
        public ?int   $idStaticObject = null,
        public ?array $bbox = [],
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Annotation\StaticEvent;

use Spatie\LaravelData\Data;

class ObjectFrameData extends Data
{
    public function __construct(
        public ?array $objects = [],
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

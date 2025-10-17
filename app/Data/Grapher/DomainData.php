<?php

namespace App\Data\Grapher;

use Spatie\LaravelData\Data;

class DomainData extends Data
{
    public function __construct(
        public ?int $idSemanticType,
        public array $frameRelation = [],
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Layers;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $layerGroup = '',
        public ?string $layerType = '',
        public ?string $genericLabel = '',
        public ?string $type = '',
        public int $id = 0,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

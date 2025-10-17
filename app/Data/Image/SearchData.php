<?php

namespace App\Data\Image;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $image = '',
        public ?string $dataset = '',
        public ?int $idImage = null,
        public ?int $id = 0,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

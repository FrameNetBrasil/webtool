<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $lu = '',
    )
    {
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\Relations;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $relationGroup = '',
        public ?string $relationType = '',
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

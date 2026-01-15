<?php

namespace App\Data\Class;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $class = '',
        public ?string $id = '',
        public string $_token = '',
        public ?bool $isEdit = false
    ) {
        $this->_token = csrf_token();
    }

}

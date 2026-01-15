<?php

namespace App\Data\Microframe;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $microframe = '',
        public ?string $id = '',
        public string $_token = '',
        public ?bool $isEdit = false
    ) {
        $this->_token = csrf_token();
    }

}

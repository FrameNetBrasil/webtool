<?php

namespace App\Data\Sentence;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $document = '',
        public ?string $sentence = '',
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

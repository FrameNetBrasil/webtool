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
        if ($this->sentence == '') {
            $this->sentence = '--- no search ---';
        } else {
            $this->sentence = str_replace(" ","%", $this->sentence);
        }
        $this->_token = csrf_token();
    }

}

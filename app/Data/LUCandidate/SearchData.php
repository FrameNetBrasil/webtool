<?php

namespace App\Data\LUCandidate;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $lu = '',
        public ?string $email = '',
        public ?string $sort = '',
        public ?string $orderby = '',
        public ?string $order = '',
        public string  $_token = '',
    )
    {
        if ($this->sort == '') {
            $this->sort = 'name';
        }
        if ($this->order == '') {
            $this->order = 'asc';
        }
        $this->_token = csrf_token();
    }

}

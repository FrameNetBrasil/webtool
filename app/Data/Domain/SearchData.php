<?php

namespace App\Data\Domain;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $domain = '',
    )
    {
    }

}

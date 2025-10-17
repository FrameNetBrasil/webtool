<?php

namespace App\Data\User;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $group = '',
        public ?string $user = '',
    )
    {
    }

}

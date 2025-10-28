<?php

namespace App\Data\Group;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $group = '',
    ) {}

}

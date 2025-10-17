<?php

namespace App\Data\Dataset;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $project = '',
        public ?string $dataset = '',
    )
    {
    }

}

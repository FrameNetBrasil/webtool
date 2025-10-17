<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $project = '',
        public ?string $dataset = '',
        public ?int $id = 0,
        public ?string $type = '',
    )
    {
    }

}

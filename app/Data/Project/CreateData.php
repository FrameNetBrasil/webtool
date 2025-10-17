<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?string $description = '',
        public ?int $idProjectGroup = null
    )
    {
    }

}

<?php

namespace App\Data\Group;

use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?string $description = '',
    )
    {
    }

}

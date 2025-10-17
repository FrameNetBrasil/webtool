<?php

namespace App\Data\Group;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int $idGroup,
        public ?string $name = '',
        public ?string $description = '',
    )
    {
    }

}

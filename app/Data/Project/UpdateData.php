<?php

namespace App\Data\Project;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idProject = null,
        public ?string $name = '',
        public ?string $description = '',
        public ?int $idProjectGroup = null
    )
    {
    }


}

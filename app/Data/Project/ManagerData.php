<?php

namespace App\Data\Project;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class ManagerData extends Data
{
    public function __construct(
        public ?int $idUser = null,
        public ?int $idProject = null,
    )
    {
    }

}

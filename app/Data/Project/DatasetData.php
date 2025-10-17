<?php

namespace App\Data\Project;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class DatasetData extends Data
{
    public function __construct(
        public ?int $idProject = null,
        public ?int $idDataset = 0,
        public ?string $name = null,
        public ?string $description=''
    )
    {

    }

}

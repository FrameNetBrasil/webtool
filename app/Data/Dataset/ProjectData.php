<?php

namespace App\Data\Dataset;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class ProjectData extends Data
{
    public function __construct(
        public ?int $idProject = null,
        public ?int $idDataset = null,
        public ?int $isSource = 0,
    )
    {
    }


}

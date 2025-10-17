<?php

namespace App\Data\Dataset;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CorpusData extends Data
{
    public function __construct(
        public ?int $idCorpus = null,
        public ?int $idDataset = null,
    )
    {
    }


}

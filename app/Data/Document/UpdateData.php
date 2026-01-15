<?php

namespace App\Data\Document;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idDocument,
        public ?int $idCorpus
    )
    {
    }
}

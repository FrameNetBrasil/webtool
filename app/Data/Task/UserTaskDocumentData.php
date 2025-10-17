<?php

namespace App\Data\Task;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class UserTaskDocumentData extends Data
{
    public function __construct(
        public ?int $idUserTask = null,
        public ?int $idDocument = null,
        public ?int $idCorpus = null,
    )
    {
    }

}

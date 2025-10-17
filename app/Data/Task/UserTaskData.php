<?php

namespace App\Data\Task;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class UserTaskData extends Data
{
    public function __construct(
        public ?int $idUser = null,
        public ?int $idTask = null,
        public ?int $isActive = null,
        public ?int $isIgnore = null,
        public ?string $createdAt = null
    )
    {
        $this->createdAt = Carbon::now();
    }

}

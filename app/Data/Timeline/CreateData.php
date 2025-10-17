<?php

namespace App\Data\Timeline;

use App\Services\AppService;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $operation = 'S',
        public ?string $tlDateTime = '',
        public ?int $idUser = 0,
        public ?string $author = '',
        public ?string $tableName = '',
        public ?int $id = 0
    )
    {
        $this->tlDateTime = Carbon::now();
        $user = AppService::getCurrentUser();
        $this->idUser = $user ? $user->idUser : 0;
        $this->author = $user ? $user->login : 'offline';
    }
}

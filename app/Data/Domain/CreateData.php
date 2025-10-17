<?php

namespace App\Data\Domain;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $nameEn = '',
        public ?int $idUser = null,
    )
    {
        $user = AppService::getCurrentUser();
        $this->idUser = $user ? $user->idUser : 0;
    }

}

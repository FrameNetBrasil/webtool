<?php

namespace App\Data\Class;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public string $nameEn,
        public ?int $idUser = 1,
        public string $_token = '',
    ) {
        $this->idUser = AppService::getCurrentIdUser();
    }
}

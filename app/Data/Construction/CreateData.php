<?php

namespace App\Data\Construction;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public string $nameEn,
        public ?int $idLanguage,
        public ?int $abstract = 0,
        public ?int $active = 1,
        public ?int $idUser = 1,
        public string $_token = '',
    )
    {
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
        $this->idUser = AppService::getCurrentIdUser();
    }
}

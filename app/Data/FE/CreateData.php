<?php

namespace App\Data\FE;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?int $idFrame,
        public ?string $nameEn,
        public ?string $coreType,
        public ?int $idColor,
        public ?int $idUser,
        public ?string $entry,
        public string $_token = '',
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
        $this->entry = strtolower('fe_' . $this->nameEn);
        $this->_token = csrf_token();
    }
}

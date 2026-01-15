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
        if ($this->coreType == 'cty_domain') {
            $this->idColor = 75;
        }
        if ($this->coreType == 'cty_range') {
            $this->idColor = 19;
        }
        if ($this->coreType == 'cty_property') {
            $this->idColor = 1;
        }
        $this->_token = csrf_token();
    }
}

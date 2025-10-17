<?php

namespace App\Data\CE;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idConstructionElement,
        public ?int $idColorEdit,
        public ?int $idColor,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
        $this->idColor = $this->idColorEdit;
    }
}

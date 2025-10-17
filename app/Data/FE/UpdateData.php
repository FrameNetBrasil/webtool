<?php

namespace App\Data\FE;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idFrameElement,
        public ?string $coreTypeEdit,
        public ?int $idColorEdit,
        public ?string $coreType,
        public ?int $idColor,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
        $this->coreType = $this->coreTypeEdit;
        $this->idColor = $this->idColorEdit;
    }
}

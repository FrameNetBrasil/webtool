<?php

namespace App\Data\Layers;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $layer = '',
        public ?string $genericlabel = '',
        public ?string $id = '',
        public ?int $idLayerType = 0,
        public ?int $idGenericLabel = 0,
        public string  $_token = '',
    )
    {
        if ($this->id != '') {
            if ($this->id[0] == 'l') {
                $this->idLayerType = substr($this->id, 1);
            }
            if ($this->id[0] == 'g') {
                $this->idGenericLabel = substr($this->id, 1);
            }
        }
        $this->_token = csrf_token();
    }

}

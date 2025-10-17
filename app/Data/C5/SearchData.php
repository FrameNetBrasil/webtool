<?php

namespace App\Data\C5;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $concept = '',
        public ?int    $idConcept = 0,
        public ?string $id = '',
        public ?int    $idTypeInstance = 0,
        public string  $_token = '',
    )
    {
        if ($this->id != '') {
            if ($this->id[0] == 't') {
                $this->idTypeInstance = substr($this->id, 1);
            } else {
                $this->idConcept = substr($this->id, 1);
            }
        }
        $this->_token = csrf_token();
    }

}

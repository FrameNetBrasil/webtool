<?php

namespace App\Data\Qualia;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $qualia = '',
        public ?string $qualiaType = '',
        public ?int $idQualia = 0,
        public ?int $idTypeInstance = 0,
        public ?string $id = '',
        public string  $_token = '',
    )
    {
        if ($this->id != '') {
            if ($this->id[0] == 't') {
                $this->idTypeInstance = substr($this->id,1);
            }
            if ($this->id[0] == 'q') {
                $this->idQualia = substr($this->id,1);
            }
        }
        $this->_token = csrf_token();
    }

}

<?php

namespace App\Data\SemanticType;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $semanticType = '',
        public ?int $idSemanticType = 0,
        public ?string $id = '',
        public string  $_token = '',
    )
    {
        if ($this->id != '') {
            if ($this->id[0] == 't') {
                $this->idSemanticType = substr($this->id,1);
            }
        }
        $this->_token = csrf_token();
    }

}

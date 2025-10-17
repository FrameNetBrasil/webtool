<?php

namespace App\Data\Annotation\Deixis;

use Spatie\LaravelData\Data;

class DeleteBBoxData extends Data
{
    public function __construct(
        public ?int   $idDynamicObject = null,
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

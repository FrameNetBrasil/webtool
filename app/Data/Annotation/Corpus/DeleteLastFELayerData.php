<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;

class DeleteLastFELayerData extends Data
{
    public function __construct(
        public ?int   $idLayer = null,
        public string $_token = ''
    )
    {
        $this->_token = csrf_token();
    }

}

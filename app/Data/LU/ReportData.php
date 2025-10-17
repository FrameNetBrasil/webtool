<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class ReportData extends Data
{
    public function __construct(
        public array  $idAS = [],
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

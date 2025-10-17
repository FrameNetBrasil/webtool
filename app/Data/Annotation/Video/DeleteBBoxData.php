<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class DeleteBBoxData extends Data
{
    public function __construct(
        public ?int $idObject = null,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }

}

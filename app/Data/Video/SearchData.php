<?php

namespace App\Data\Video;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $video = '',
        public ?int $idVideo = null,
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}

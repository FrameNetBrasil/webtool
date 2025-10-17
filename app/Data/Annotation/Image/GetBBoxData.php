<?php

namespace App\Data\Annotation\Image;

use Spatie\LaravelData\Data;

class GetBBoxData extends Data
{
    public function __construct(
        public ?int $idObject = null,
        public string $_token = '',
    ) {}

}

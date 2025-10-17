<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class GetBBoxData extends Data
{
    public function __construct(
        public ?int $idObject = null,
        public ?int $frameNumber = null,
        public ?int $isTracking = null,
        public string $_token = '',
    ) {}

}

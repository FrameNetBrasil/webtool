<?php

namespace App\Data\Annotation\Image;

use Spatie\LaravelData\Data;

class ObjectFrameData extends Data
{
    public function __construct(
        public ?int $idDocument = null,
        public ?int $idObject = null,
        public ?int $startFrame = null,
        public ?int $endFrame = null,
        public ?float $startTime = null,
        public ?float $endTime = null,
        public ?string $annotationType = '',
    ) {
        $timeIntervalSeconds = 0.04;
        $this->startTime = ($this->startFrame - 1) * $timeIntervalSeconds;
        $this->endTime = ($this->endFrame - 1) * $timeIntervalSeconds;
    }

}

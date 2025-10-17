<?php

namespace App\Data\Annotation\Deixis;

use Spatie\LaravelData\Data;

class ObjectFrameData extends Data
{
    public function __construct(
        public ?int   $idDynamicObject = null,
        public ?int   $startFrame = null,
        public ?int   $endFrame = null,
        public ?float   $startTime = null,
        public ?float   $endTime = null,
    )
    {
        $timeIntervalSeconds = 0.04;
        $this->startTime = ($this->startFrame - 1) * $timeIntervalSeconds;
        $this->endTime = ($this->endFrame - 1) * $timeIntervalSeconds;
    }

}

<?php

namespace App\Data\Daisy;

use Spatie\LaravelData\Data;

class DaisyOutputData extends Data
{
    public function __construct(
        public array $result,
        public array $graph,
        public array $sentenceUD,
        public array $windows,
        public array $weights,
    ) {}
}

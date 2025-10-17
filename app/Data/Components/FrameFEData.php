<?php

namespace App\Data\Components;

use Spatie\LaravelData\Data;

class FrameFEData extends Data
{
    public function __construct(
        public int    $idFrame,
    )
    {
    }

}

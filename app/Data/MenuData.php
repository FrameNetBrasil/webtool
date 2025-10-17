<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MenuData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public string $href,
        public string $group,
        public array $items
    )
    {

    }
}

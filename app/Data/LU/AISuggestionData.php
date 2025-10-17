<?php

namespace App\Data\LU;

use Spatie\LaravelData\Data;

class AISuggestionData extends Data
{
    public function __construct(
        public ?int $idFrame = null,
        public ?string $model = 'llama',
        public ?array $pos = ['NOUN'],
    )
    {
    }

}

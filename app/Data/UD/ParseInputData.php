<?php

namespace App\Data\UD;

use Spatie\LaravelData\Data;

class ParseInputData extends Data
{
    public function __construct(
        public string $sentence,
    ) {}

    public static function rules(): array
    {
        return [
            'sentence' => ['required', 'string', 'min:1', 'max:1000'],
        ];
    }
}

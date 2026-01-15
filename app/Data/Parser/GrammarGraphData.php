<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class GrammarGraphData extends Data
{
    public function __construct(
        public string $name,
        public string $language,
        public ?string $description = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'language' => ['required', 'string', 'size:2'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

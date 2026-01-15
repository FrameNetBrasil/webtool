<?php

namespace App\Data\Daisy;

use Spatie\LaravelData\Data;

class DaisyInputData extends Data
{
    public function __construct(
        public string $sentence,
        public int $idLanguage = 1,
        public int $searchType = 2,
        public int $level = 1,
        public bool $gregnetMode = false,
    ) {}

    public static function rules(): array
    {
        return [
            'sentence' => ['required', 'string', 'min:3'],
            'idLanguage' => ['required', 'integer', 'in:1,2'],
            'searchType' => ['required', 'integer', 'min:1', 'max:4'],
            'level' => ['required', 'integer', 'min:1', 'max:5'],
            'gregnetMode' => ['boolean'],
        ];
    }
}

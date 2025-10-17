<?php

namespace App\Data\Annotation\Corpus;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Normalizers\JsonNormalizer;

class SelectionData extends Data
{
    public function __construct(
        public ?string $type = '',
        public ?string $id = '',
        public ?string $start = '',
        public ?string $end = '',
    )
    {

    }
    public static function normalizers(): array
    {
        return [
            JsonNormalizer::class,
        ];
    }

}

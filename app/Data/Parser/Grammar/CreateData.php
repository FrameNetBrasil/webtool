<?php

namespace App\Data\Parser\Grammar;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        #[Required, Min(3), Max(100)]
        public string $name = '',

        #[Required, Min(2), Max(10)]
        public string $language = '',

        #[Max(1000)]
        public ?string $description = null,
    ) {}
}

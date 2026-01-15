<?php

namespace App\Data\ComboBox;

use Spatie\LaravelData\Data;

class QData extends Data
{
    public function __construct(
        public ?string $q = '',
        public ?string $frame = '',
        public ?string $class = '',
        public ?string $microframe = '',
        public ?string $lu = '',
        public ?string $lemmaName = '',
        public ?string $semanticType = '',
        public ?int $idLanguage = 0
    )
    {
    }
}

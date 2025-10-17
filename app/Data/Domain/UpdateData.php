<?php

namespace App\Data\Domain;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int $idDomain,
        public ?string $nameEn = '',
    )
    {
    }

}

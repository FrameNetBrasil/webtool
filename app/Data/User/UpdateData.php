<?php

namespace App\Data\User;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int $idUser,
        public ?string $email = '',
        public ?string $name = '',
        public ?int $idGroup = 7 // READER
    )
    {
    }

}

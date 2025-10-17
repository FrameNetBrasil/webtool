<?php

namespace App\Data\User;

use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $login = '',
        public ?string $email = '',
        public ?string $name = '',
        public ?int $idGroup = 7, // READER
        public ?array $groups = [],
        public ?string $passMD5 = '',
        public ?string $status = '0',
    )
    {
    }

}

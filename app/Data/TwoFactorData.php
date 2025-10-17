<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TwoFactorData extends Data
{
    public function __construct(
        public string $field0,
        public string $field1,
        public string $field2,
        public string $field3,
        public string $field4,
        public string $field5,
        public ?string $token = ''
    )
    {
        $this->token = $this->field0 . $this->field1 . $this->field2 . $this->field3 . $this->field4 . $this->field5;
    }

}

<?php

namespace App\Data\Entry;

use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public array $idEntry,
        public array $name,
        public array $description,
        public ?string $trigger = '',
        public string $_token = '',
    )
    {
        $this->_token = csrf_token();
    }
}

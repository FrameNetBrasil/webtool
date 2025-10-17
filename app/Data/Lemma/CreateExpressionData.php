<?php

namespace App\Data\Lemma;

use Spatie\LaravelData\Data;

class CreateExpressionData extends Data
{
    public function __construct(
        public ?int $idLemma = null,
        public ?string $form = '',
        public ?int $position = 1,
        public ?int $head = 0,
        public ?int $breakBefore = 0,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

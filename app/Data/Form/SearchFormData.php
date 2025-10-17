<?php

namespace App\Data\Form;

use Spatie\LaravelData\Data;

class SearchFormData extends Data
{
    public function __construct(
        public ?string $form = '',
        public ?int $idLexicon = 0,
        public string $_token = '',
    ) {
        $this->_token = csrf_token();
    }
}

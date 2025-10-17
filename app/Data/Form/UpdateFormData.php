<?php

namespace App\Data\Form;

use Spatie\LaravelData\Data;

class UpdateFormData extends Data
{
    public function __construct(
        public ?string $form,
        public ?int $idLexiconGroup,
        public string $_token = '',
    ) {}

    public static function rules(): array
    {
        return [
            'form' => ['required', 'string'],
            'idLexiconGroup' => ['required', 'int'],
        ];
    }

    public static function messages(): array
    {
        return [
            'form.required' => 'Form is required.',
            'idLexiconGroup.required' => 'Lexicon group is required.',
        ];
    }
}

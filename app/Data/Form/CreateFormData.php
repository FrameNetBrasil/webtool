<?php

namespace App\Data\Form;

use App\Services\AppService;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Data;

class CreateFormData extends Data
{
    public function __construct(
        public ?string $form,
        public ?int $idLanguage,
        public ?int $idLexiconGroup,
        public string $_token = '',
    ) {
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
    }

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

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            if (Str::contains($data['form'] ?? '', [',', ';', ':', '(', ')'])) {
                $validator->errors()->add('form', 'Invalid characters in form.');
            }
            if (($data['idLexiconGroup'] ?? 0) == 2) {
                $validator->errors()->add('idLexiconGroup', 'Lemmas cannot be created as forms. Use lemma creation.');
            }
        });
    }

    public static function redirect(): string
    {
        return 'error';
    }
}

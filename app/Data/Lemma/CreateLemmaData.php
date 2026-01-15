<?php

namespace App\Data\Lemma;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Data;

class CreateLemmaData extends Data
{
    public function __construct(
        public ?string $name,
//        public ?int $idUDPOS,
        public ?int $idLanguage,
        public string $_token = '',
    ) {
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
        if (str_contains($this->name, '.')) {
            $this->name = substr($this->name, strpos($this->name, '.') + 1);
        }
    }

    public static function rules(): array
    {
        return [
//            'idUDPOS' => ['required', 'int'],
            'name' => ['required', 'string'],
        ];
    }

    public static function messages(): array
    {
        return [
//            'idUDPOS.required' => 'UD-POS is required.',
            'name.required' => 'Lemma name is required.',
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            if (Str::contains($data['name'] ?? '', [',', ';', ':', '(', ')','_','?','!','[',']'])) {
                $validator->errors()->add('name', 'Invalid characters in lemma.');
            }
        });
    }

    public static function redirect(): string
    {
        return 'error';
    }
}

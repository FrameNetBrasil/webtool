<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use \Illuminate\Validation\Validator;

class CreateLexiconData extends Data
{
    public function __construct(
        public ?string $form = '',
        public ?int    $idPOS,
        public ?int    $idUDPOS,
        public ?int    $idLanguage,
        public ?int    $idLexiconGroup,
        public string  $_token = '',
    )
    {
        if ($this->idLexiconGroup == 2) {
            if (is_null($this->idPOS)) {
                $pos = Criteria::byId("pos_udpos", "idUDPOS", $this->idUDPOS);
                $this->idPOS = $pos->idPOS;
            }
        }
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
        if (str_contains($this->form, '.')) {
            $this->form = substr($this->form, strpos($this->form, '.'));
        }
        if (str_contains($this->form, '.') && ($this->idLexiconGroup == 2)) {
            $this->form = substr($this->form, strpos($this->form, '.'));
        }

    }

    public static function rules(): array
    {
        return [
            'idUDPOS' => ['required_if:idLexiconGroup,2', 'int'],
        ];
    }

    public static function messages(): array
    {
        return [
            'idUDPOS.required_if' => 'UD-POS is required.',
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            if (Str::contains($data['form'], [',',';',':','(',')'])) {
                $msg = ($data['idLexiconGroup'] == 2) ? "Invalid characters in lemma." : "Invalid characters in form.";
                $validator->errors()->add('form', $msg);
            }
        });
    }

    public static function redirect(): string
    {
        return "error";
    }
}

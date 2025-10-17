<?php

namespace App\Data\Lemma;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class UpdateLemmaData extends Data
{
    public function __construct(
        public ?int $idLemma= null,
        public ?string $name,
        public ?int $idUDPOS,
        public ?int $idLanguage,
        public ?int $idUser= null,
        public string $_token = '',
    ) {
        $this->idUser = AppService::getCurrentIdUser();
    }

    public static function rules(): array
    {
        return [
            'idUDPOS' => ['required', 'int'],
            'name' => ['required', 'string'],
        ];
    }

    public static function messages(): array
    {
        return [
            'idUDPOS.required' => 'UD-POS is required.',
            'name.required' => 'Lemma name is required.',
        ];
    }
}

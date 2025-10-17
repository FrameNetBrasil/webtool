<?php

namespace App\Data\SemanticType;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?int $idSemanticType = null,
        public ?int $idEntity = null,
        public ?int $idUser = null,
        public ?int $idDomain = null,
        public ?string $semanticTypeName = ''
    )
    {
        $user = AppService::getCurrentUser();
        $this->idUser = $user ? $user->idUser : 0;
    }
}

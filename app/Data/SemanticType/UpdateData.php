<?php

namespace App\Data\SemanticType;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idSemanticType = null,
        public ?int $idSemanticTypeParent = null,
        public ?int $idUser = null,
    )
    {
        $user = AppService::getCurrentUser();
        $this->idUser = $user ? $user->idUser : 0;
    }
}

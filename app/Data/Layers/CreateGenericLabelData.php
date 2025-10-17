<?php

namespace App\Data\Layers;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateGenericLabelData extends Data
{
    public function __construct(
        public ?string $name = null,
        public ?int $idLanguage = null,
        public ?int $idColor = null,
        public ?int $idLayerType = null,
        public ?string $definition = '',
        public ?int $idUser = null,
    )
    {
        $user = AppService::getCurrentUser();
        $this->idUser = $user ? $user->idUser : 0;
    }
}

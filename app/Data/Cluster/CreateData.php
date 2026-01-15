<?php

namespace App\Data\Cluster;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $nameEn = 'test',
        public ?int $idNamespace = 14,
        public ?int $idSemanticType = 1,
        public ?int $idUser = 1,
        public ?string $_token = '',
    ) {
        $this->idUser = AppService::getCurrentIdUser();
    }
}

<?php

namespace App\Data\Layers;

use App\Database\Criteria;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateLayerTypeData extends Data
{
    public function __construct(
        public ?string $nameEn = '',
        public ?int $allowsApositional = 0,
        public ?int $isAnnotation = 1,
        public ?int $layerOrder = 1,
        public ?int $idLayerGroup = 0,
        public string $_token = '',
    )
    {
    }
}

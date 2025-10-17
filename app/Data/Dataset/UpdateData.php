<?php

namespace App\Data\Dataset;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idDataset = null,
        public ?string $name = '',
        public ?string $description = '',
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }

}

<?php

namespace App\Data\Image;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class DatasetData extends Data
{
    public function __construct(
        public ?int $idImage = null,
        public ?int $idDataset = null,
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }


}

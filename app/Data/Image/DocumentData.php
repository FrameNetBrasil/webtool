<?php

namespace App\Data\Image;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    public function __construct(
        public ?int $idImage = null,
        public ?int $idDocument = null,
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }


}

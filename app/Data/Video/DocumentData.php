<?php

namespace App\Data\Video;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    public function __construct(
        public ?int $idVideo = null,
        public ?int $idDocument = null,
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }


}

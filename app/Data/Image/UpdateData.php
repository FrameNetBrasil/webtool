<?php

namespace App\Data\Image;

use App\Services\AppService;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int          $idImage,
        public ?string       $name = '',
        public ?string       $currentURL = '',
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }

}

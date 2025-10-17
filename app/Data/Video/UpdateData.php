<?php

namespace App\Data\Video;

use App\Services\AppService;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int          $idVideo,
        public ?string       $title = '',
        public ?string       $originalFile = '',
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }

}

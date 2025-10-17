<?php

namespace App\Data\Utils;

use App\Services\AppService;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class ImportFullTextData extends Data
{
    public function __construct(
        public ?int          $idDocument = null,
        public ?int          $idLanguage = null,
        public ?UploadedFile $file = null,
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }

}

<?php

namespace App\Data\Sentence;

use App\Services\AppService;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public int          $idSentence,
        public ?string       $text = '',
        public ?int          $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }

}

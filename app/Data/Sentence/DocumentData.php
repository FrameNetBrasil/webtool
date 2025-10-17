<?php

namespace App\Data\Sentence;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    public function __construct(
        public ?int $idSentence = null,
        public ?int $idDocument = null,
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }


}

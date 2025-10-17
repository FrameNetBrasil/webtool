<?php

namespace App\Data\Annotation\Session;

use App\Services\AppService;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class SessionData extends Data
{
    public function __construct(
        public ?int $idDocumentSentence = null,
        public ?int $idUser = null,
        public ?Carbon $timestamp = null,
        public string  $_token = '',
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
        $this->timestamp = Carbon::now();
        $this->_token = csrf_token() ?? '';
    }

}

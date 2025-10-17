<?php

namespace App\Data\Corpus;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?int $idUser = null
    )
    {
        $this->idUser = AppService::getCurrentIdUser();
    }


}

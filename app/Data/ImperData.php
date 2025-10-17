<?php

namespace App\Data;

use App\Http\Controllers\Index\MainController;
use Spatie\LaravelData\Data;

class ImperData extends Data
{
    public function __construct(
        public int $idUser,
        public string $password
    )
    {
    }

}

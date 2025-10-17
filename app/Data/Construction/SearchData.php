<?php

namespace App\Data\Construction;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $cxn = '',
        public ?string $ce = '',
        public ?string $listBy = '',
        public ?int $idLanguage= 0,
        public ?string $id = '',
        public ?int    $idConstruction = 0,
        public string  $_token = '',
        public ?string $byLanguage = '',
        public ?string $language = '',
    )
    {
        if (($this->id != '') && ($this->id[0] == 'l')) {
            $this->idLanguage = substr($this->id, 1);
        }
//        if ($this->idLanguage == 0) {
//            $this->idLanguage = AppService::getCurrentIdLanguage();
//        }
        $this->_token = csrf_token();
    }

}

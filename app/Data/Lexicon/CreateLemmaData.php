<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateLemmaData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?int $idPOS = null,
        public ?int $idUDPOS = null,
        public ?int $idLanguage = null,
        public ?string $addName = '',
        public string $_token = '',
    )
    {
        if ($this->name == '') {
            $this->name = $this->addName;
        } else {
            $this->addName = $this->name;
        }
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
        if (is_null($this->idPOS)) {
            if (str_contains($this->addName,'.')) {
                $parts = explode('.',$this->addName);
                $pos = Criteria::table("pos")
                    ->whereRaw("upper(pos) = '" . strtoupper($parts[1]) . "'")
                    ->first();
                $this->idPOS = $pos->idPOS;
            } else {
                $pos = Criteria::byId("pos","POS","N");
                $this->idPOS = $pos->idPOS;
            }
        }
        if (is_null($this->idUDPOS)) {
            if (str_contains($this->addName,'.')) {
                $parts = explode('.',$this->addName);
                $pos = Criteria::table("udpos")
                    ->whereRaw("upper(pos) = '" . strtoupper($parts[1]) . "'")
                    ->first();
                $this->idUDPOS = $pos->idUDPOS;
            } else {
                $pos = Criteria::byId("udpos","POS","NOUN");
                $this->idUDPOS = $pos->idUDPOS;
            }
        }
    }
}

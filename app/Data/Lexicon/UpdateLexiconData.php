<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use Spatie\LaravelData\Data;

class UpdateLexiconData extends Data
{
    public function __construct(
        public ?int $idLexicon = 0,
        public ?string $form = '',
        public ?int    $idPOS,
        public ?int    $idUDPOS,
        public ?int    $idLanguage,
        public ?int    $idLexiconGroup,
        public string  $_token = '',
    )
    {
        if ($this->idLexiconGroup == 2) {
            if (is_null($this->idPOS)) {
                $pos = Criteria::byId("pos_udpos", "idUDPOS", $this->idUDPOS);
                $this->idPOS = $pos->idPOS;
            }
        }
    }
}

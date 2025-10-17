<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateLexemeEntryData extends Data
{
    public function __construct(
        public ?int    $idLemma = null,
        public ?int    $idLemmaEntry = null,
        public ?string $lexeme = '',
        public ?int    $idLexeme = null,
        public ?int    $idPOSLexeme = null,
        public ?int    $lexemeOrder = null,
        public ?int    $breakBefore = null,
        public ?int    $headWord = null,
        public string  $_token = '',
    )
    {
        if (is_null($this->idLemma)) {
            $this->idLemma = $this->idLemmaEntry;
        }
        if (is_null($this->lexemeOrder)) {
            $this->lexemeOrder = 1;
        }
        if (is_null($this->breakBefore)) {
            $this->breakBefore = 0;
        }
        if (is_null($this->headWord)) {
            $this->headWord = 0;
        }
        $lexeme = Criteria::table("lexeme")
            ->whereRaw("name = '{$this->lexeme}' collate 'utf8mb4_bin'")
            ->where("idPOS", "=", $this->idPOSLexeme)
            ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
            ->first();
        if (!is_null($lexeme)) {
            $this->idLexeme = $lexeme->idLexeme;
        }
    }
}

<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateExpressionData extends Data
{
    public function __construct(
        public ?int    $idLemma = null,
        public ?int    $idLemmaBase = null,
        public ?int    $idLexiconGroup = null,
        public ?int    $idLexicon = null,
        public ?string $form = '',
        public ?int    $idUDPOSExpression = null,
        public ?int    $position = null,
        public ?int    $breakBefore = null,
        public ?int    $head = null,
        public string  $_token = '',
    )
    {
        if (is_null($this->idLemma)) {
            $this->idLemma = $this->idLemmaBase;
        }
        if (is_null($this->position)) {
            $this->position = 1;
        }
        if (is_null($this->breakBefore)) {
            $this->breakBefore = 0;
        }
        if (is_null($this->head)) {
            $this->head = 0;
        }
        debug($this);
        if ($this->idLexiconGroup == 1) {
            $lexicon = Criteria::table("lexicon")
                ->whereRaw("form = '{$this->form}' collate 'utf8mb4_bin'")
                ->where("idLexiconGroup", 1)
                ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
                ->first();
        } else if ($this->idLexiconGroup == 2) {
            $lexicon = Criteria::table("lexicon")
                ->whereRaw("form = '{$this->form}' collate 'utf8mb4_bin'")
                ->where("idLexiconGroup", 2)
                ->where("idUDPos",$this->idUDPOSExpression)
                ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
                ->first();
        }
        if (!is_null($lexicon)) {
            $this->idLexicon = $lexicon->idLexicon;
        }
    }
}

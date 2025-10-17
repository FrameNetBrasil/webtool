<?php

namespace App\Data\LUCandidate;

use App\Repositories\Lexicon;
use Spatie\LaravelData\Data;

class UpdateData extends Data
{
    public function __construct(
        public ?int $idLU = null,
        public ?string $name = '',
        public ?string $senseDescription = '',
        public ?string $discussion = '',
        public ?int $idLemma = null,
        public ?int $idFrame = null,
        public ?int $idDocumentSentence = null,
        public ?int $idDynamicObject = null,
        public ?int $idStaticObject = null,
        public ?int $incorporatedFE = null,
        public ?string $suggestedNewFrame = '',
    ) {
        if ($this->idFrame == 0) {
            $this->idFrame = null;
        }
        if (is_null($this->senseDescription)) {
            $this->senseDescription = '';
        }
        if (is_null($this->discussion)) {
            $this->discussion = '';
        }
        $lemma = Lexicon::lemmaById($this->idLemma);
        $this->name = $lemma->shortName;
    }

}

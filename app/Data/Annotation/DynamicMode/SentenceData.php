<?php

namespace App\Data\Annotation\DynamicMode;

use App\Services\AppService;
use Spatie\LaravelData\Data;

class SentenceData extends Data
{
    public function __construct(
        public ?int $idSentence = null,
        public ?int $idDocument = null,
        public ?int $idLanguage = null,
        public ?string $startTime = '',
        public ?string $endTime = '',
        public ?int $idOriginMM = null,
        public ?string $text = ''
    )
    {
        if (is_null($this->idLanguage)) {
            $this->idLanguage = AppService::getCurrentIdLanguage();
        }
    }

}

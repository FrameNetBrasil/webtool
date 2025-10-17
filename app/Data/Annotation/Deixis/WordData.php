<?php

namespace App\Data\Annotation\DynamicMode;

use App\Services\AppService;
use Doctrine\Inflector\Rules\Word;
use Spatie\LaravelData\Data;

class WordData extends Data
{
    public function __construct(
        public ?int $idDocument = null,
        public ?int $idVideo = null,
        public ?int $idLanguage = null,
        public ?array $words = [],
    )
    {
        foreach ($words as $i => $word) {
            $this->words[$i] = (object)$word;
        }
    }

}

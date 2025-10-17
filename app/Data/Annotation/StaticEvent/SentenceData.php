<?php

namespace App\Data\Annotation\StaticEvent;

use Spatie\LaravelData\Data;

class SentenceData extends Data
{
    public function __construct(
        public ?int $idDocumentSentence = null,
        public ?int $idPrevious = null,
        public ?int $idNext = null,
        public ?object $document = null,
        public ?object $sentence = null,
        public ?object $corpus = null,
        public ?object $image = null,
        public ?array $objects = null,
        public ?array $frames = null,
        public ?string $type = '',
        public ?string $comment = '',
    )
    {
    }

}

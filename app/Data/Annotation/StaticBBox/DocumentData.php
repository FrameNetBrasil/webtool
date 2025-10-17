<?php

namespace App\Data\Annotation\StaticBBox;

use Spatie\LaravelData\Data;

class DocumentData extends Data
{
    public function __construct(
        public ?int $idDocument = null,
        public ?int $idDocumentImage = null,
        public ?int $idStaticObject = null,
        public ?int $idPrevious = null,
        public ?int $idNext = null,
        public ?object $document = null,
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

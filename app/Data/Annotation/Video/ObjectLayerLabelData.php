<?php

namespace App\Data\Annotation\Video;

use Spatie\LaravelData\Data;

class ObjectLayerLabelData extends Data
{
    public function __construct(
        public ?int $idDocument = null,
        public ?int $idObject = null,
        public ?int $idLayerTypeNew = null,
        public ?int $idGenericLabelNew = null,
        public ?string $annotationType = '',
    ) {
    }

}

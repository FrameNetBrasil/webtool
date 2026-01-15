<?php

namespace App\Data\Relation;

use App\Database\Criteria;
use App\Repositories\RelationType;
use Spatie\LaravelData\Data;

class ClassData extends Data
{
    public function __construct(
        public int $idFrame,
        public int $idFrameRelated,
        public ?int $idEntityMicroframe,
        public ?string $class,
    )
    {
    }
}

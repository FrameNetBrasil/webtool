<?php

namespace App\Data\Relation;

use App\Database\Criteria;
use App\Repositories\RelationType;
use Spatie\LaravelData\Data;

class FrameData extends Data
{
    public function __construct(
        public int $idFrame,
        public string $relationType,
        public int $idFrameRelated,
        public ?int $idRelationType,
        public ?string $direction,
        public ?string $relationTypeEntry = ''
    )
    {
        $this->direction = $this->relationType[0];
        $this->idRelationType = (int)(substr($this->relationType, 1));
        $relationType = Criteria::byId("relationtype","idRelationType", $this->idRelationType);
        $this->relationTypeEntry = $relationType->entry;
    }
}

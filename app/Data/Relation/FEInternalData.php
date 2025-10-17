<?php

namespace App\Data\Relation;

use App\Database\Criteria;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;

class FEInternalData extends Data
{
    #[Computed]
    public function __construct(
        public int $idFrame,
        public ?array $idFrameElementRelated,
        public ?string $relationTypeFEInternal = '',
        public ?int $idRelationType = null,
        public ?string $relationTypeEntry = ''
    )
    {
        $this->idRelationType = (int)substr($this->relationTypeFEInternal, 1);
        $relationType = Criteria::byId("relationtype","idRelationType", $this->idRelationType);
        $this->relationTypeEntry = $relationType->entry;
    }
}

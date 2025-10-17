<?php

namespace App\Data\Relation;

use App\Database\Criteria;
use App\Repositories\RelationType;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public int  $idEntity1,
        public int  $idEntity2,
        public ?int $idEntity3 = null,
        public ?int $idEntityRelation = null,
        public ?int $idRelation = null,
        public ?int $idRelationType = null,
        public ?string $relationTypeEntry = ''
    )
    {
        if (!is_null($this->idRelationType)) {
            $relationType = Criteria::byId("relationtype","idRelationType", $this->idRelationType);
            $this->relationTypeEntry = $relationType->entry;
        } else if ($relationTypeEntry != '') {
            $rt = RelationType::getByEntry($relationTypeEntry);
            $this->idRelationType = $rt->idRelationType;
        }
    }
}

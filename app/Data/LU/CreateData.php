<?php

namespace App\Data\LU;

use App\Enum\Status;
use App\Repositories\Lemma;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public int $idFrame,
        public int $idLemma,
        public ?string $name = '',
        public ?string $senseDescription = '',
        public ?int $incorporatedFE = -1,
        public ?string $status = null,
        public ?int $active = 1,
        public ?int $idUser = 1,
        public ?int $idEntity = null
    ) {
        $lemma = Lemma::byId($this->idLemma);
        $this->name = strtolower($lemma->name.'.'.$lemma->udPOS);
        $this->incorporatedFE = ($this->incorporatedFE < 0) ? null : $this->incorporatedFE;
        $this->idUser = AppService::getCurrentIdUser();
        $this->senseDescription = $this->senseDescription ?? '';
        $this->status = $this->status ?? Status::CREATED->value;
    }
}

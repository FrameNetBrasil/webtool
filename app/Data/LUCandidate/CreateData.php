<?php

namespace App\Data\LUCandidate;

use App\Services\AppService;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        public ?string $name = '',
        public ?string $senseDescription = '',
        public ?string $discussion = '',
        public ?int $idLemma = null,
        public ?int $idFrame = null,
        public ?int $idDocumentSentence = null,
        public ?int $idDynamicObject = null,
        public ?int $idStaticObject = null,
        public ?int $incorporatedFE = null,
        public ?string $suggestedNewFrame = '',
        public ?int $idUser = null,
        public ?string $createdAt = '',
        public ?string $status = '',
        public ?string $origin = ''
    ) {
        if (is_null($this->senseDescription)) {
            $this->senseDescription = '';
        }
        if (is_null($this->discussion)) {
            $this->discussion = '';
        }
        if ($this->idFrame == 0) {
            $this->idFrame = null;
        }
        $this->idUser = AppService::getCurrentIdUser();
        $this->createdAt = Carbon::now();
        $this->status = 'PENDING';
        $this->origin = 'USER';
    }

}

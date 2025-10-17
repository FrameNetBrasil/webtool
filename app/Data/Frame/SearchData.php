<?php

namespace App\Data\Frame;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $frame = '',
        public ?string $fe = '',
        public ?string $lu = '',
        public ?string $listBy = '',
        public ?int $idFramalDomain = null,
        public ?int $idFramalType = null,
        public ?int $idFrameScenario = null,
        public ?string $id = '',
        public ?int    $idFrame = 0,
        public ?int    $idFramePrevious = 0,
        public string  $_token = '',
        public ?string $byGroup = '',
        public ?string $group = '',
        public ?bool $isEdit = false
    )
    {
        $idFramePrevious = session('idFramePrevious')  ?? 0;
        $this->isEdit = ($this->idFrame != 0) && ($this->idFrame == $idFramePrevious);
        session(['idFramePrevious' => $this->idFrame]);
        $this->_token = csrf_token();
    }

}

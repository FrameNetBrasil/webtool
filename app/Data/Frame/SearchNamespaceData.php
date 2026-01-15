<?php

namespace App\Data\Frame;

use Spatie\LaravelData\Data;

class SearchNamespaceData extends Data
{
    public function __construct(
        public ?string $frame = '',
        public ?string $lu = '',
        public ?int $idNamespace = null,
        public ?string $id = '',
        public ?int    $idFrame = 0,
        public string  $_token = '',
    )
    {
        $idFramePrevious = session('idFramePrevious')  ?? 0;
        session(['idFramePrevious' => $this->idFrame]);
        $this->_token = csrf_token();
    }

}

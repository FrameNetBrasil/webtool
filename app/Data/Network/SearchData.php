<?php

namespace App\Data\Network;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?string $frame = '',
        public ?int    $idFramalDomain = 0,
        public ?string $id = '',
        public ?int    $idFrame = 0,
        public string  $type = '',
        public string  $_token = '',
    )
    {
        if ($this->id != '') {
            $type = $this->id[0];
            if ($type == 'n') {
                $this->type = 'node';
            } else {
                if ($type == 'd') {
                    $this->idFramalDomain = substr($this->id, 1);
                    $this->type = 'domain';
                } else {
                    $parts = explode('_', $this->id);
                    $this->idFrame = array_pop($parts);
                    $this->type = 'frame';
                }
            }
        }
        $this->_token = csrf_token();
    }

}

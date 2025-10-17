<?php

namespace App\Data\Annotation\Image;

use Spatie\LaravelData\Data;

class UpdateBBoxData extends Data
{
    public function __construct(
        public ?int   $idBoundingBox = null,
        public ?array $bbox = [],
        public string $_token = '',
    )
    {
        unset($this->bbox['visible']);
        unset($this->bbox['idStaticObject']);
//        $this->bbox['x'] = (int)$this->bbox['x'];
//        $this->bbox['y'] = (int)$this->bbox['y'];
//        $this->bbox['width'] = (int)$this->bbox['width'];
//        $this->bbox['height'] = (int)$this->bbox['height'];
        $this->_token = csrf_token();
    }

}

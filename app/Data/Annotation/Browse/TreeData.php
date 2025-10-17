<?php

namespace App\Data\Annotation\Browse;

use Spatie\LaravelData\Data;

class TreeData extends Data
{
    public function __construct(
        public ?int $idCorpus = null,
        public ?int $idDocument = null,
        public ?string $id = '',
        public ?string $type = '',
        public ?string $taskGroupName = null,
        public string $_token = '',
    ) {
        if ($type == 'corpus') {
            $this->idCorpus = $id;
        } elseif ($type == 'document') {
            $this->idDocument = $id;
        }
        $this->_token = csrf_token();
    }

}

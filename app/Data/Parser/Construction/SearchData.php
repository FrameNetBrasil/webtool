<?php

namespace App\Data\Parser\Construction;

use Spatie\LaravelData\Data;

class SearchData extends Data
{
    public function __construct(
        public ?int $idGrammarGraph = null,
        public ?string $name = '',
        public ?string $constructionType = 'all',
        public ?string $enabled = '2',
    ) {

        //        if ($this->name == '') {
        //            $this->name = '%';
        //        }
    }
}

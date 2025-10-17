<?php

namespace App\Data\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use \Illuminate\Validation\Validator;

class CreateFeatureData extends Data
{
    public function __construct(
        public ?int    $idLexiconExpression,
        public ?int    $idUDFeature,
        public string  $_token = '',
    )
    {
    }

}

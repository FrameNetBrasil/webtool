<?php

namespace App\Repositories;

use App\Database\Criteria;

class Corpus
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_corpus", ["idCorpus","=", $id])->first();
    }
}

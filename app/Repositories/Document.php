<?php

namespace App\Repositories;

use App\Database\Criteria;

class Document
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage("view_document", ["idDocument","=", $id])->first();
    }
}

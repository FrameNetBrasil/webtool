<?php

namespace App\Repositories;

use App\Database\Criteria;

class Form
{
    public static function byId(int $id): object
    {
        return Criteria::byFilterLanguage('view_form', ['idLexicon', '=', $id])->first();
    }

    public static function byForm(string $form, int $idLanguage): ?object
    {
        return Criteria::table('lexicon')
            ->whereRaw("form = '{$form}' collate 'utf8mb4_bin'")
            ->where('idLanguage', $idLanguage)
            ->first();
    }

    public static function byExpression(int $idLexiconExpression): ?object
    {
        return Criteria::table('view_form')
            ->where('idLexiconExpression', $idLexiconExpression)
            ->first();
    }
}

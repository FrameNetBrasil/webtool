<?php

namespace App\Repositories;

use App\Database\Criteria;

class Lemma
{
    public static function byId(int $id): object
    {
        return Criteria::byFilter('view_lemma', [
            'idLemma', '=', $id,
        ])->first();
    }

    public static function byIdLexicon(int $idLexicon): object
    {
        return Criteria::byFilter('view_lemma', [
            'idLexicon', '=', $idLexicon,
        ])->first();
    }

    public static function byName(string $name, int $idLanguage): ?object
    {
        return Criteria::table('view_lemma')
            ->whereRaw("name = '{$name}' collate 'utf8mb4_bin'")
            ->where('idLanguage', $idLanguage)
            ->first();
    }
}

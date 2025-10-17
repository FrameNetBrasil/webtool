<?php

namespace App\Services\Lemma;

use App\Database\Criteria;
use App\Services\AppService;

class BrowseService
{
    public static int $limit = 300;

    public static function browseLemmaBySearch(object $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->lemma != '') {
            $lemmas = Criteria::table('view_lemma as lm')
                ->where('lm.idLanguage', AppService::getCurrentIdLanguage())
                ->whereRaw("lm.name LIKE '{$search->lemma}%' collate 'utf8mb4_bin'")
                ->select('lm.idLemma', 'lm.fullName as lemma')
                ->limit(self::$limit)
                ->all();
            foreach ($lemmas as $lemma) {
                $result[$lemma->idLemma] = [
                    'id' => $lemma->idLemma,
                    'type' => 'lemma',
                    'text' => view('Lemma.partials.tree-item', (array) $lemma)->render(),
                    'leaf' => $leaf,
                ];
            }
        }

        return $result;
    }
}

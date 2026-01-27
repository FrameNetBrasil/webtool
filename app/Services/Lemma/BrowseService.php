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
        debug($search);
        if ($search->lemma != '') {
            $lemmas = Criteria::table('view_lemma as lm')
                ->where('lm.idLanguage', AppService::getCurrentIdLanguage())
                ->whereRaw("lm.name LIKE '{$search->lemma}%' collate 'utf8mb4_bin'")
                ->select('lm.idLemma', 'lm.name as lemma')
                ->limit(self::$limit)
                ->all();
            foreach ($lemmas as $lemma) {
                $result[$lemma->idLemma] = [
                    'id' => $lemma->idLemma,
                    'type' => 'lemma',
                    'text' => view('Lemma.partials.tree-lemma', (array) $lemma)->render(),
                    'leaf' => $leaf,
                ];
            }
        } elseif ($search->idLemma != 0) {
            $forms = Criteria::table('view_lexicon as lx')
                ->where('lx.idLanguage', AppService::getCurrentIdLanguage())
                ->where('lx.idLemma', $search->idLemma)
                ->select('lx.idExpression', 'lx.form')
                ->all();
            foreach ($forms as $form) {
                $result[$form->idExpression] = [
                    'id' => $form->idExpression,
                    'type' => 'form',
                    'text' => view('Lemma.partials.tree-form', (array) $form)->render(),
                    'leaf' => true,
                ];
            }
        }

        return $result;
    }
}

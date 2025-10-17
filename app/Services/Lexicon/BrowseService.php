<?php

namespace App\Services\Lexicon;

use App\Database\Criteria;
use App\Services\AppService;
use Illuminate\Database\Query\JoinClause;

class BrowseService
{
    static int $limit = 300;
    public static function browseLemmaBySearch(object $search, bool $leaf = false): array
    {
        $result = [];
        if ($search->lemma != '') {
            $lemmas = Criteria::table("view_lexicon_lemma as lm")
                ->where("lm.idLanguage", AppService::getCurrentIdLanguage())
                ->whereRaw("lm.name LIKE '{$search->lemma}%' collate 'utf8mb4_bin'")
                ->select("lm.idLexicon", "lm.fullNameUD as lemma")
                ->limit(self::$limit)
                ->all();
            foreach ($lemmas as $lemma) {
                $result[$lemma->idLexicon] = [
                    'id' => $lemma->idLexicon,
                    'type' => 'lemma',
                    'text' => view('Lexicon3.partials.lemma', (array)$lemma)->render(),
                    'leaf' => $leaf,
                ];
            }
        }
        return $result;
    }

    public static function browseFormBySearch(object $search): array
    {
        $result = [];
        if ($search->form != '') {
            $forms = Criteria::byFilter("view_lexicon_form", [
                ["form", "startswith", $search->form],
                ['idLanguage', "=", AppService::getCurrentIdLanguage()]
            ])->select('idLexicon', 'form', 'shortName')
                ->distinct()
                ->limit(self::$limit)
                ->orderBy("form")->all();
            foreach ($forms as $form) {
                $result[$form->idLexicon] = [
                    'id' => $form->idLexicon,
                    'type' => 'form',
                    'text' => view('Lexicon3.partials.form', (array)$form)->render(),
                    'leaf' => true,
                ];
            }
        }
        return $result;
    }

    public static function browseFormByLemmaSearch(object $search): array
    {
        $result = [];
        if ($search->idLemma != '') {
            $forms = Criteria::table("view_lexicon_lemma as lm")
                ->leftJoin("lexicon_expression as le","le.idLexicon","=","lm.idLexicon")
                ->leftJoin("lexicon as lx","le.idExpression","=","lx.idLexicon")
                ->where("lm.idLanguage", AppService::getCurrentIdLanguage())
                ->where("lm.idLexicon", $search->idLemma)
                ->select("le.idExpression","lx.form")
                ->limit(self::$limit)
                ->orderBy("form")
                ->all();
            foreach ($forms as $form) {
                $result[$form->idExpression] = [
                    'id' => $form->idExpression,
                    'type' => 'form',
                    'text' => view('Lexicon3.partials.form', (array)$form)->render(),
                    'leaf' => true,
                ];
            }
        }
        return $result;
    }
}

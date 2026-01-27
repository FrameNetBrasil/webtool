<?php

namespace App\Services\Daisy;

use App\Database\Criteria;

class DBService
{
    public static array $lemmas = [];
    public static array $lus = [];
    public static array $vectors = [];

    public static function queryLUByLemmas(array $lemmas): array
    {
        $result = [];
        try {
            $lus = Criteria::table('view_lu as lu')
                ->selectRaw('DISTINCT lu.idLU')
                ->whereIn('lu.idLemma', $lemmas)
                ->all();
            foreach ($lus as $lu) {
                $result[$lu->idLU] = $lu->idLU;
            }
        } catch (\Exception $e) {
            logger()->error('Daisy LU query error: ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Query LU by word form (fallback)
     */
    public static function getLemmas(string $word, int $idLanguage): array
    {
        if (isset(self::$lemmas[$word][$idLanguage])) {
            return self::$lemmas[$word][$idLanguage];
        }
        $idLemmas = [];
        try {
            // select distinct `idLemma`, lemmaName from `view_lexicon` where `idLanguage` = 1 and `head` = 1 and `form` = 'de' collate utf8mb4_general_ci and lemmaName not like '% %'
            $lemmas = Criteria::table('view_lexicon')
                ->select('idLemma')
                ->distinct()
                ->where('idLanguage', '=', $idLanguage)
                ->where('head', 1)
                ->whereRaw("form = '" . strtolower($word) . "' collate utf8mb4_general_ci ")
                ->whereRaw("lemmaName not like '% %'")
                ->all();
            foreach ($lemmas as $lemma) {
                $idLemmas[$lemma->idLemma] = $lemma->idLemma;
            }
            self::$lemmas[$word][$idLanguage] = $idLemmas;
        } catch (\Exception $e) {
            logger()->error('Daisy LU word query error: ' . $e->getMessage());
        }
        return $idLemmas;
    }

    public static function setVector(int $idLU, array $vector): void
    {
        self::$vectors[$idLU] = $vector;
    }

    public static function getVector(int $idLU): array
    {
        return self::$vectors[$idLU];
    }

    public static function getLUFrame(int $idLU, int $idLanguage = 1): object
    {
        return Criteria::table('view_lu as lu')
            ->join("view_frame as f", "f.idFrame", "=", "lu.idFrame")
            ->select('lu.idLU', 'lu.idFrame', 'lu.name', 'f.name as frameName')
            ->where('lu.idLU', $idLU)
            ->where('f.idLanguage', $idLanguage)
            ->first();
    }

}

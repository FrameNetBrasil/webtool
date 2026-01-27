<?php

namespace App\Data\Daisy;

use App\Database\Criteria;
use App\Services\Daisy\DBService;
use App\Services\Daisy\SpreadingActivationService;

class ComponentData
{
    public int $id;

    //    public ?int $idLemma = null;
    //
    //    public array $fn = []; // possible grid functions

    //    public ?string $fnDef = null; // definitive grid function

    public string $word;

    public string $pos; // UPOS tag

    public ?int $idCluster = null;

    public ?int $idLU = null;

    //    public bool $main = false;

    //    private int $head = 0;

    public int $idLanguage = 1;

    public int $isMwe = 0;

    public array $lemmas = [];

    public array $lus = [];

    public array $compare = [];

    public array $sum = [];

    public function __construct(int $id, string $word, string $pos, int $idLanguage)
    {
        $this->id = $id;
        $this->word = $word;
        $this->pos = $pos;
        $this->idLanguage = $idLanguage;
        $this->isMwe = 0;
        $this->lemmas = [];
    }

    public function head(?int $value = null): int
    {
        if ($value !== null) {
            $this->head = $value;
        }

        return $this->head;
    }

    /**
     * Query lexical units from database
     */
    public function setLexicalUnits(): void
    {
        // $this->lemmas = DBService::getLemmas($this->word, $this->idLanguage);
        $this->lus = DBService::queryLUByLemmas($this->lemmas);
    }

    //    /**
    //     * Query LU by lemmas
    //     */
    //    private function queryLUByLemmas(): void
    //    {
    //        try {
    //            $lus = Criteria::table('view_lu as lu')
    //                ->selectRaw('DISTINCT lu.idFrame, lu.frameName as frameEntry, lu.name as lu,
    //                            lu.idLU, lu.idEntity as idEntityLU,
    //                            lu.frameIdEntity as idEntityFrame,
    //                            lu.idLemma')
    //                ->where('lu.idLanguage', '=', $this->idLanguage)
    //                ->whereIn('lu.idLemma', $this->lemmas)
    //                ->all();
    //            foreach ($lus as $lu) {
    //                $this->lus[$lu->idLU] = $lu->idLU;
    // //                $this->frames[$lu->idFrame] = $lu->idFrame;
    //            }
    //        } catch (\Exception $e) {
    //            // Log error and return empty array
    //            logger()->error('Daisy LU query error: '.$e->getMessage());
    //        }
    //    }
    //
    //    /**
    //     * Query LU by word form (fallback)
    //     */
    //    private function getLemmas(): void
    //    {
    //        try {
    //            // select distinct `idLemma`, lemmaName from `view_lexicon` where `idLanguage` = 1 and `head` = 1 and `form` = 'de' collate utf8mb4_general_ci and lemmaName not like '% %'
    //            $lemmas = Criteria::table('view_lexicon')
    //                ->select('idLemma')
    //                ->distinct()
    //                ->where('idLanguage', '=', $this->idLanguage)
    //                ->where('head', 1)
    //                ->whereRaw("form = '".strtolower($this->word)."' collate utf8mb4_general_ci ")
    //                ->whereRaw("lemmaName not like '% %'")
    //                ->all();
    //            $idLemmas = [];
    //            foreach ($lemmas as $lemma) {
    //                $idLemmas[$lemma->idLemma] = $lemma->idLemma;
    //            }
    //            $this->lemmas = $idLemmas;
    //        } catch (\Exception $e) {
    //            logger()->error('Daisy LU word query error: '.$e->getMessage());
    //        }
    //    }

    public function createVectors(SpreadingActivationService $spreadingActivationService): void
    {
        foreach ($this->lus as $idLU) {
            DBService::setVector($idLU, $spreadingActivationService->vectorForLU($idLU));
        }
    }

    public function compareToComponent(ComponentData $component): void
    {
        foreach ($this->lus as $idLU) {
            $sum = 0;
            $vectorBase = DBService::getVector($idLU);
            foreach ($component->lus as $idLUComponent) {
                $value = 0;
                $vectorTarget = DBService::getVector($idLUComponent);
                foreach ($vectorTarget as $idNode => $weight) {
                    if (isset($vectorBase[$idNode])) {
                        $value += $weight;
                    }
                }
                $this->compare[$idLU][$idLUComponent] = $value;
                $sum += $this->compare[$idLU][$idLUComponent];
            }
            $this->sum[$idLU] = $sum;
        }
        $maxValue = 0;
        foreach ($this->sum as $idLU => $value) {
            if ($value > $maxValue) {
                $this->idLU = $idLU;
                $maxValue = $value;
            }
        }
        if (is_null($this->idLU)) {
            $this->idLU = array_first($this->lus);
        }
    }
}

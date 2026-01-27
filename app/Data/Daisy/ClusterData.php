<?php

namespace App\Data\Daisy;

use App\Database\Criteria;
use App\Services\Daisy\SpreadingActivationService;

class ClusterData
{
    public int $id;

    public string $type;

    public string $word;

    public array $canUse = [];

    public array $components = [];

    public ?int $idWindow = null;

    public int $idLanguage;

//    public array $lemmas = [];

//    public array $lus = [];

//    public array $frames = [];

//    public array $vectors = [];

    public function __construct(int $id, string $type, string $word = '', array $canUse = [])
    {
        $this->id = $id;
        $this->type = $type;
        $this->word = $word;
        $this->canUse = $canUse;
        $this->idLanguage = 1;
    }

    public function canAdd(string $function): bool
    {
        return in_array($function, $this->canUse);
    }

    public function addComponent(ComponentData $component): void
    {
        $component->idCluster = $this->id;
        $this->components[] = $component;
    }

//    /**
//     * Query lexical units from database
//     */
//    public function setLexicalUnits(): void
//    {
//        $this->getLemmas();
//        $this->queryLUByLemmas();
//    }
//
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
////                $this->frames[$lu->idFrame] = $lu->idFrame;
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
//
//    public function createVectors(SpreadingActivationService $spreadingActivationService): void
//    {
//        foreach ($this->lus as $idLU) {
//            $this->vectors[$idLU] = $spreadingActivationService->vectorForLU($idLU);
//        }
//
//    }
}

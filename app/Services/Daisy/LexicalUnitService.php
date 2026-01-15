<?php

namespace App\Services\Daisy;

use App\Data\Daisy\FrameCandidateData;
use App\Database\Criteria;
use Illuminate\Support\Facades\Cache;

/**
 * LexicalUnitService - Lexical Unit Matching
 *
 * Responsible for:
 * - Querying lexical units from view_lu based on lemmas
 * - Matching POS tags to grid functions
 * - Creating initial frame candidate sets
 * - Distributing initial energy across candidates
 */
class LexicalUnitService
{
    private array $gridToPOS;

    private int $idLanguage;

    public function __construct(int $idLanguage = 1)
    {
        $this->idLanguage = $idLanguage;
        $this->gridToPOS = config('daisy.gridToPOS');
    }

    /**
     * Match lexical units for window components
     *
     * @param  array  $windows  WindowData[]
     * @param  array  $lemmas  ComponentData[]
     * @return array Frames indexed by window, word, frameEntry
     */
    public function matchLexicalUnits(array $windows, array $lemmas): array
    {
        $result = [];

        foreach ($windows as $idWindow => $window) {
            $result[$idWindow] = [];

            foreach ($window->components as $component) {
                $word = $component->word;
                $gridFunction = $component->fnDef;

                // Query LUs for this word/lemma
                $lus = $this->queryLexicalUnits($component);
                debug("***************************");
debug($lus);
                // Create frame candidates
                $frameCandidates = $this->createFrameCandidates($lus, $component, $gridFunction);

                if (! empty($frameCandidates)) {
                    $result[$idWindow][$word] = $frameCandidates;
                }
            }
        }

        return $result;
    }

    /**
     * Query lexical units from database
     */
    private function queryLexicalUnits(object $component): array
    {
        // If no lemmas, try to query by word form
        if (empty($component->lemmas)) {
            return $this->queryLUByWord($component->word);
        }

        $cacheKey = "daisy:lu:{$this->idLanguage}:".md5(json_encode($component->lemmas));
        $ttl = config('daisy.cacheTTL.lemmaToLU');

        return Cache::remember($cacheKey, $ttl, function () use ($component) {
            return $this->queryLUByLemmas($component->lemmas);
        });
    }

    /**
     * Query LU by lemmas
     */
    private function queryLUByLemmas(array $lemmas): array
    {
        try {
            // First, get lemma IDs from view_lemma (uses 'name' column, not 'lemma')
            $lemmaRecords = Criteria::table('view_lemma')
                ->where('idLanguage', '=', $this->idLanguage)
                ->where(function ($query) use ($lemmas) {
                    foreach ($lemmas as $lemma) {
                        $query->orWhere('name', '=', strtolower($lemma));
                    }
                })
                ->get();

            if ($lemmaRecords->isEmpty()) {
                return [];
            }

            $idLemmas = $lemmaRecords->pluck('idLemma')->toArray();

            // Query lexical units
            $lus = Criteria::table('view_lu as lu')
                ->selectRaw('DISTINCT lu.idFrame, lu.frameName as frameEntry, lu.name as lu,
                            lu.idLU, lu.idEntity as idEntityLU,
                            lu.frameIdEntity as idEntityFrame,
                            pos.POS as POSLemma, lu.idLemma,
                            (SELECT COUNT(*) FROM view_domain d WHERE d.idDomain = ? AND d.idEntity = lu.frameIdEntity) as mknob',
                    [config('daisy.domains.mknob')])
                ->join('pos', 'lu.idPOS', '=', 'pos.idPOS')
                ->where('lu.idLanguage', '=', $this->idLanguage)
                ->whereIn('lu.idLemma', $idLemmas)
                ->get()
                ->toArray();

            return array_map(fn ($lu) => (array) $lu, $lus);
        } catch (\Exception $e) {
            // Log error and return empty array
            logger()->error('Daisy LU query error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Query LU by word form (fallback)
     */
    private function queryLUByWord(string $word): array
    {
        try {
            $lus = Criteria::table('view_lu as lu')
                ->selectRaw('DISTINCT lu.idFrame, lu.frameName as frameEntry, lu.name as lu,
                            lu.idLU, lu.idEntity as idEntityLU,
                            lu.frameIdEntity as idEntityFrame,
                            pos.POS as POSLemma, lu.idLemma,
                            (SELECT COUNT(*) FROM view_domain d WHERE d.idDomain = ? AND d.idEntity = lu.frameIdEntity) as mknob',
                    [config('daisy.domains.mknob')])
                ->join('pos', 'lu.idPOS', '=', 'pos.idPOS')
                ->where('lu.idLanguage', '=', $this->idLanguage)
                ->where('lu.name', 'LIKE', strtolower($word).'%')
                ->get()
                ->toArray();
            return array_map(fn ($lu) => (array) $lu, $lus);
        } catch (\Exception $e) {
            logger()->error('Daisy LU word query error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Create frame candidates from LU results
     */
    private function createFrameCandidates(array $lus, object $component, string $gridFunction): array
    {
        $candidates = [];

        if (empty($lus)) {
            return $candidates;
        }

        // Distribute initial energy equally among candidates
        $initialEnergy = 1.0 / count($lus);

        foreach ($lus as $lu) {
            // Match POS if available
            $expectedPOS = $this->gridToPOS[$gridFunction] ?? null;
            if ($expectedPOS && isset($lu['POSLemma']) && $lu['POSLemma'] !== $expectedPOS) {
                continue; // Skip mismatched POS
            }

            $candidate = new FrameCandidateData(
                lu: $lu['lu'],
                idLU: $lu['idLU'],
                idFrame: $lu['idFrame'],
                frameEntry: $lu['frameEntry'],
                energy: $initialEnergy,
                iword: $component->id,
                id: $component->id
            );

            // Check if MWE
            $candidate->mwe = ($lu['mwe'] ?? 0) == 1;

            // Check if MKNOB domain
            $candidate->mknob = ($lu['mknob'] ?? 0) == 1;

            $candidates[$lu['frameEntry']] = $candidate;
        }

        return $candidates;
    }
}

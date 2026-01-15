<?php

namespace App\Services\Parser;

use App\Repositories\Lemma;

class LemmaResolverService
{
    /**
     * Get lemma ID by lemma text and language
     */
    public function getLemmaId(string $lemmaText, int $idLanguage): ?int
    {
        $lemma = Lemma::byName($lemmaText, $idLanguage);

        return $lemma?->idLemma;
    }

    /**
     * Get or create lemma entry
     * For now, this only retrieves existing lemmas
     * Creation of new lemmas should be handled separately
     */
    public function getOrCreateLemma(string $lemmaText, int $idLanguage, string $pos): ?int
    {
        // Try to find existing lemma
        $existing = $this->getLemmaId($lemmaText, $idLanguage);

        if ($existing) {
            return $existing;
        }

        // TODO: Implement lemma creation logic
        // This would require:
        // - Creating Entity entry
        // - Creating Lexicon entry with proper idUDPOS
        // - Creating Lemma entry linking them
        // For now, return null for unknown lemmas

        return null;
    }

    /**
     * Check if a lemma exists
     */
    public function lemmaExists(string $lemmaText, int $idLanguage): bool
    {
        return $this->getLemmaId($lemmaText, $idLanguage) !== null;
    }

    /**
     * Batch lookup for multiple lemmas
     * Returns array mapping lemma text to idLemma
     */
    public function batchLookup(array $lemmaTexts, int $idLanguage): array
    {
        $result = [];

        foreach ($lemmaTexts as $lemmaText) {
            $idLemma = $this->getLemmaId($lemmaText, $idLanguage);
            if ($idLemma) {
                $result[$lemmaText] = $idLemma;
            }
        }

        return $result;
    }
}

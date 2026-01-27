<?php

namespace App\Services\Daisy;

use App\Data\Daisy\TokenData;
use App\Database\Criteria;

/**
 * TokenizerService - Sentence Tokenization with MWE Support
 *
 * Responsible for:
 * - Tokenizing sentences into words and punctuation
 * - Recognizing Multi-Word Expressions (MWEs) using lemma-based matching
 * - Looking up lemma IDs for each token
 */
class TokenizerService
{
    /**
     * MWE patterns indexed by first node's idLemma
     *
     * @var array<int, array<array{idLexiconPattern: int, mweLemma: int, nodeLemmas: int[]}>>
     */
    private array $mweByFirstLemma = [];

    /**
     * Cache for word form to possible lemmas lookup
     *
     * @var array<string, int[]>
     */
    private array $formToLemmasCache = [];

    private int $idLanguage;

    private bool $initialized = false;

    public function __construct(int $idLanguage = 1)
    {
        $this->idLanguage = $idLanguage;
    }

    /**
     * Tokenize a sentence into an array of TokenData objects
     *
     * @return TokenData[]
     */
    public function tokenize(string $sentence, ?int $idLanguage = null): array
    {
        if ($idLanguage !== null && $idLanguage !== $this->idLanguage) {
            $this->idLanguage = $idLanguage;
            $this->initialized = false;
        }

        if (! $this->initialized) {
            $this->initialize();
        }

        // Split into basic tokens (words and punctuation)
        $basicTokens = $this->splitIntoBasicTokens($sentence);

        $tokens = [];
        $i = 0;
        $position = 0;

        while ($i < count($basicTokens)) {
            $currentToken = $basicTokens[$i];

            // Check if this token starts an MWE
            $mweMatch = $this->findLongestMweMatch($basicTokens, $i);

            if ($mweMatch !== null) {
                // Create MWE token
                $mweWords = array_slice($basicTokens, $i, $mweMatch['wordCount']);
                $form = implode(' ', $mweWords);

                $tokens[] = new TokenData(
                    form: $form,
                    idLemmas: [$mweMatch['mweLemma']],
                    isMwe: true,
                    position: $position
                );

                $i += $mweMatch['wordCount'];
            } else {
                // Single token - look up lemmas (excluding MWE lemmas)
                $idLemmas = $this->lookupNonMweLemmas($currentToken);

                $tokens[] = new TokenData(
                    form: $currentToken,
                    idLemmas: $idLemmas,
                    isMwe: false,
                    position: $position
                );

                $i++;
            }

            $position++;
        }

        return $tokens;
    }

    /**
     * Initialize the service by loading MWE patterns
     */
    public function initialize(): void
    {
        $this->buildMweLookup();
        $this->initialized = true;
    }

    /**
     * Get the number of MWE first-lemma entries loaded
     */
    public function getMweCount(): int
    {
        return count($this->mweByFirstLemma);
    }

    /**
     * Clear the caches (useful for testing or language change)
     */
    public function clearCache(): void
    {
        $this->mweByFirstLemma = [];
        $this->formToLemmasCache = [];
        $this->initialized = false;
    }

    private function buildMweLookup(): void
    {
        $this->mweByFirstLemma = [];

        // Get all MWE patterns for the language with their nodes
        $patterns = Criteria::table('lexicon_pattern as lp')
            ->join('view_lemma as vl', 'vl.idLemma', '=', 'lp.idLemma')
            ->where('lp.patternType', '=', 'MWE')
            ->where('vl.idLanguage', '=', $this->idLanguage)
            ->select('lp.idLexiconPattern', 'vl.idLemma as mweLemma', 'vl.name as mweName')
            ->get();

        foreach ($patterns as $pattern) {
            // Get all nodes for this pattern ordered by position
            $nodes = Criteria::table('lexicon_pattern_node')
                ->where('idLexiconPattern', '=', $pattern->idLexiconPattern)
                ->orderBy('position')
                ->select('idLemma', 'position')
                ->get();

            if ($nodes->isEmpty()) {
                continue;
            }

            // Skip patterns where any node has NULL idLemma (incomplete data)
            $nodeLemmas = $nodes->pluck('idLemma')->toArray();
            if (in_array(null, $nodeLemmas, true)) {
                continue;
            }

            $firstLemma = $nodeLemmas[0];

            if (! isset($this->mweByFirstLemma[$firstLemma])) {
                $this->mweByFirstLemma[$firstLemma] = [];
            }

            $this->mweByFirstLemma[$firstLemma][] = [
                'idLexiconPattern' => $pattern->idLexiconPattern,
                'mweLemma' => $pattern->mweLemma,
                'mweName' => $pattern->mweName,
                'nodeLemmas' => $nodeLemmas,
            ];
        }

        // Sort each first-lemma group by pattern length (longest first) for greedy matching
        foreach ($this->mweByFirstLemma as &$patterns) {
            usort($patterns, fn ($a, $b) => count($b['nodeLemmas']) <=> count($a['nodeLemmas']));
        }
    }

    /**
     * Get possible lemma IDs for a word form
     *
     * @return int[]
     */
    private function getFormLemmas(string $form): array
    {
        $lowerForm = mb_strtolower($form);

        if (isset($this->formToLemmasCache[$lowerForm])) {
            return $this->formToLemmasCache[$lowerForm];
        }

        // Query view_lexicon by form
        $lemmas = Criteria::table('view_lexicon')
            ->where('form', '=', $form)
            ->select('idLemma')
            ->distinct()
            ->pluck('idLemma')
            ->toArray();

        // Also try lowercase if different
        if ($form !== $lowerForm) {
            $lowerLemmas = Criteria::table('view_lexicon')
                ->where('form', '=', $lowerForm)
                ->select('idLemma')
                ->distinct()
                ->pluck('idLemma')
                ->toArray();

            $lemmas = array_unique(array_merge($lemmas, $lowerLemmas));
        }

        $this->formToLemmasCache[$lowerForm] = $lemmas;

        return $lemmas;
    }

    /**
     * Split sentence into basic tokens (words and punctuation)
     *
     * @return string[]
     */
    private function splitIntoBasicTokens(string $sentence): array
    {
        // Match words (including accented characters) or punctuation
        preg_match_all('/[\p{L}\p{N}]+|[^\s\p{L}\p{N}]/u', $sentence, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Find the longest MWE match starting at position $startIndex using lemma-based matching
     *
     * @return array{wordCount: int, mweLemma: int, mweName: string}|null
     */
    private function findLongestMweMatch(array $tokens, int $startIndex): ?array
    {
        $firstToken = $tokens[$startIndex];
        $firstTokenLemmas = $this->getFormLemmas($firstToken);

        if (empty($firstTokenLemmas)) {
            return null;
        }

        // Check each possible lemma of the first token
        foreach ($firstTokenLemmas as $firstLemma) {
            if (! isset($this->mweByFirstLemma[$firstLemma])) {
                continue;
            }

            // Try each MWE pattern starting with this lemma (already sorted by length, longest first)
            foreach ($this->mweByFirstLemma[$firstLemma] as $pattern) {
                $nodeLemmas = $pattern['nodeLemmas'];
                $patternLength = count($nodeLemmas);

                // Check if we have enough tokens remaining
                if ($startIndex + $patternLength > count($tokens)) {
                    continue;
                }

                // Check if all positions match by lemma
                $matches = true;
                for ($j = 0; $j < $patternLength; $j++) {
                    $tokenAtPos = $tokens[$startIndex + $j];
                    $tokenLemmas = $this->getFormLemmas($tokenAtPos);
                    $expectedLemma = $nodeLemmas[$j];

                    if (! in_array($expectedLemma, $tokenLemmas)) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    return [
                        'wordCount' => $patternLength,
                        'mweLemma' => $pattern['mweLemma'],
                        'mweName' => $pattern['mweName'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Look up lemma IDs for a word form, excluding MWE lemmas
     *
     * @return int[]
     */
    private function lookupNonMweLemmas(string $form): array
    {
        $lemmas = Criteria::table('view_lexicon')
            ->where('form', '=', $form)
            ->whereNotIn('idLemma', function ($query) {
                $query->select('idLemma')
                    ->from('lexicon_pattern')
                    ->where('patternType', '=', 'MWE');
            })
            ->select('idLemma')
            ->distinct()
            ->pluck('idLemma')
            ->toArray();

        return $lemmas;
    }
}

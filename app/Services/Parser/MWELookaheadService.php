<?php

namespace App\Services\Parser;

use App\Data\Parser\AlternativeState;
use App\Data\Parser\ConstructionDefinition;

/**
 * MWE Lookahead Service
 *
 * Handles lookahead validation for MWEs with ambiguous boundaries.
 *
 * Example: "gol contra" can be:
 * - MWE "gol_contra" (own goal) when followed by preposition/verb/EOS
 * - Separate words when followed by determiner/noun (contra o time)
 *
 * Process:
 * 1. MWE reaches tentative_complete status
 * 2. Check following tokens within maxDistance window
 * 3. Match against invalidationPatterns (breaks MWE)
 * 4. Match against confirmationPatterns (confirms MWE)
 * 5. Return result: confirmed, invalidated, or pending
 *
 * @see docs/parser/v4/MWE_LOOKAHEAD.md
 */
class MWELookaheadService
{
    /**
     * Check lookahead for a tentatively complete MWE
     *
     * @param  AlternativeState  $mweAlternative  The MWE alternative
     * @param  ConstructionDefinition  $construction  The MWE construction definition
     * @param  array  $allTokens  All tokens in the sentence
     * @param  int  $currentPosition  Current parsing position
     * @return array ['status' => string, 'reason' => string, 'matchedToken' => ?object]
     *
     * Status values:
     * - 'confirmed': MWE is valid, safe to aggregate
     * - 'invalidated': Following pattern breaks MWE, should abandon
     * - 'pending': Still within window, no decision yet
     */
    public function checkLookahead(
        AlternativeState $mweAlternative,
        ConstructionDefinition $construction,
        array $allTokens,
        int $currentPosition
    ): array {
        // MWE ends at currentPosition
        $startLookahead = $currentPosition + 1;
        $maxDistance = $construction->lookaheadMaxDistance;
        $endLookahead = min($startLookahead + $maxDistance - 1, count($allTokens) - 1);

        // Get following tokens within lookahead window
        $followingTokens = [];
        for ($i = $startLookahead; $i <= $endLookahead; $i++) {
            if (isset($allTokens[$i])) {
                $followingTokens[] = $allTokens[$i];
            }
        }

        // If no following tokens (end of sentence), confirm MWE
        if (empty($followingTokens)) {
            return [
                'status' => 'confirmed',
                'reason' => 'End of sentence',
                'matchedToken' => null,
            ];
        }

        // Check invalidation patterns first (higher priority)
        foreach ($construction->invalidationPatterns as $pattern) {
            $patternDef = $pattern['pattern'] ?? '';
            $description = $pattern['description'] ?? 'Invalidation pattern matched';

            foreach ($followingTokens as $token) {
                if ($this->matchesPattern($token, $patternDef)) {
                    return [
                        'status' => 'invalidated',
                        'reason' => $description,
                        'matchedToken' => $token,
                    ];
                }
            }
        }

        // Check confirmation patterns
        foreach ($construction->confirmationPatterns as $pattern) {
            $patternDef = $pattern['pattern'] ?? '';
            $description = $pattern['description'] ?? 'Confirmation pattern matched';

            foreach ($followingTokens as $token) {
                if ($this->matchesPattern($token, $patternDef)) {
                    return [
                        'status' => 'confirmed',
                        'reason' => $description,
                        'matchedToken' => $token,
                    ];
                }
            }
        }

        // No patterns matched yet, still within window
        return [
            'status' => 'pending',
            'reason' => 'Within lookahead window, no decision yet',
            'matchedToken' => null,
        ];
    }

    /**
     * Check if a token matches a pattern
     *
     * Pattern formats:
     * - {POS}: Match POS tag (e.g., {DET}, {NOUN}, {VERB})
     * - "word": Match literal word (e.g., "café", "contra")
     * - EOS: End of sentence marker
     * - Multiple patterns: "word1" | "word2" | {POS}
     *
     * @param  object  $token  The token to check
     * @param  string  $pattern  The pattern definition
     * @return bool True if token matches pattern
     */
    private function matchesPattern(object $token, string $pattern): bool
    {
        // Handle end-of-sentence marker
        if ($pattern === 'EOS') {
            return false; // Already handled by empty followingTokens check
        }

        // Handle OR patterns: "word1" | "word2" | {POS}
        if (str_contains($pattern, '|')) {
            $alternatives = array_map('trim', explode('|', $pattern));
            foreach ($alternatives as $alt) {
                if ($this->matchesSimplePattern($token, $alt)) {
                    return true;
                }
            }

            return false;
        }

        return $this->matchesSimplePattern($token, $pattern);
    }

    /**
     * Match a single simple pattern (no OR logic)
     */
    private function matchesSimplePattern(object $token, string $pattern): bool
    {
        $pattern = trim($pattern);

        // Match POS tag: {NOUN}, {DET}, etc.
        if (preg_match('/^\{([A-Z]+)\}$/', $pattern, $matches)) {
            $expectedPOS = $matches[1];
            $tokenPOS = $token->upos ?? $token->pos ?? '';

            return $tokenPOS === $expectedPOS;
        }

        // Match literal word: "café", "contra", etc.
        if (preg_match('/^"([^"]+)"$/', $pattern, $matches)) {
            $expectedWord = strtolower($matches[1]);
            $tokenWord = strtolower($token->word ?? $token->form ?? '');
            $tokenLemma = strtolower($token->lemma ?? '');

            return $tokenWord === $expectedWord || $tokenLemma === $expectedWord;
        }

        return false;
    }

    /**
     * Determine if an MWE needs lookahead checking
     *
     * @param  ConstructionDefinition  $construction  The MWE construction
     * @return bool True if lookahead is needed
     */
    public function needsLookahead(ConstructionDefinition $construction): bool
    {
        return $construction->lookaheadEnabled &&
               ! empty($construction->invalidationPatterns);
    }

    /**
     * Check if lookahead window has been exceeded
     *
     * @param  AlternativeState  $mweAlternative  The MWE alternative
     * @param  ConstructionDefinition  $construction  The MWE construction
     * @return bool True if lookahead window exceeded
     */
    public function hasExceededLookaheadWindow(
        AlternativeState $mweAlternative,
        ConstructionDefinition $construction
    ): bool {
        return $mweAlternative->lookaheadCounter > $construction->lookaheadMaxDistance;
    }

    /**
     * Get lookahead statistics for debugging
     *
     * @param  AlternativeState  $mweAlternative  The MWE alternative
     * @param  ConstructionDefinition  $construction  The MWE construction
     * @return array Statistics about lookahead state
     */
    public function getLookaheadStats(
        AlternativeState $mweAlternative,
        ConstructionDefinition $construction
    ): array {
        return [
            'enabled' => $construction->lookaheadEnabled,
            'maxDistance' => $construction->lookaheadMaxDistance,
            'currentCounter' => $mweAlternative->lookaheadCounter,
            'exceeded' => $this->hasExceededLookaheadWindow($mweAlternative, $construction),
            'invalidationPatterns' => count($construction->invalidationPatterns),
            'confirmationPatterns' => count($construction->confirmationPatterns),
        ];
    }

    /**
     * Log MWE invalidation for debugging
     *
     * @param  AlternativeState  $mweAlternative  The invalidated MWE
     * @param  string  $reason  Reason for invalidation
     * @param  ?object  $matchedToken  The token that caused invalidation
     */
    public function logInvalidation(
        AlternativeState $mweAlternative,
        string $reason,
        ?object $matchedToken = null
    ): void {
        if (config('parser.v4.mwe.logInvalidations', false)) {
            logger()->info('MWE Invalidated', [
                'construction' => $mweAlternative->constructionName,
                'reason' => $reason,
                'matchedWord' => $matchedToken ? ($matchedToken->word ?? $matchedToken->form ?? '') : null,
                'matchedPOS' => $matchedToken ? ($matchedToken->upos ?? $matchedToken->pos ?? '') : null,
                'components' => array_map(
                    fn ($c) => is_object($c) ? ($c->word ?? $c->form ?? '') : $c,
                    $mweAlternative->matchedComponents
                ),
            ]);
        }
    }

    /**
     * Log MWE confirmation for debugging
     *
     * @param  AlternativeState  $mweAlternative  The confirmed MWE
     * @param  string  $reason  Reason for confirmation
     */
    public function logConfirmation(
        AlternativeState $mweAlternative,
        string $reason
    ): void {
        if (config('parser.v4.mwe.logInvalidations', false)) {
            logger()->info('MWE Confirmed', [
                'construction' => $mweAlternative->constructionName,
                'reason' => $reason,
                'components' => array_map(
                    fn ($c) => is_object($c) ? ($c->word ?? $c->form ?? '') : $c,
                    $mweAlternative->matchedComponents
                ),
            ]);
        }
    }
}

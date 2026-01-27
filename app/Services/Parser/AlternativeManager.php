<?php

namespace App\Services\Parser;

use App\Data\Parser\AlternativeState;
use App\Data\Parser\ConstructionDefinition;
use App\Data\Parser\ParseStateV4;

/**
 * Alternative Manager Service
 *
 * Manages the lifecycle of construction alternatives during incremental parsing:
 * - Creating new alternatives when constructions can start
 * - Updating existing alternatives as tokens are processed
 * - Promoting alternatives through status transitions
 * - Pruning stale or abandoned alternatives
 * - Maintaining priority queue ordering
 *
 * Alternative lifecycle:
 * pending → progressing → complete/tentative_complete → confirmed → aggregated
 *                                ↓
 *                          invalidated/abandoned
 */
class AlternativeManager
{
    private static int $alternativeIdCounter = 1;

    public function __construct(
        private ConstraintChecker $constraintChecker,
    ) {}

    /**
     * Create a new alternative for a construction starting with a token
     *
     * @param  ConstructionDefinition  $construction  The construction to instantiate
     * @param  object  $token  The starting token
     * @param  int  $position  Current position in sentence
     * @return AlternativeState The new alternative
     */
    public function createAlternative(
        ConstructionDefinition $construction,
        object $token,
        int $position
    ): AlternativeState {
        $threshold = $this->calculateThreshold($construction);

        $alternative = new AlternativeState(
            id: self::$alternativeIdCounter++,
            constructionName: $construction->name,
            constructionType: $construction->constructionType,
            priority: $construction->priority,
            startPosition: $position,
            currentPosition: $position,
            matchedComponents: [$token],
            expectedNext: $this->computeExpectedNext($construction, [$token]),
            activation: 1.0,
            threshold: $threshold,
            status: $threshold === 1.0 ? 'complete' : 'pending',
            features: $this->extractFeatures($token),
            pendingConstraints: $construction->constraints,
        );

        return $alternative;
    }

    /**
     * Try to advance an alternative with a new token
     *
     * @param  AlternativeState  $alternative  The alternative to advance
     * @param  ConstructionDefinition  $construction  The construction definition
     * @param  object  $token  The next token
     * @return AlternativeState|null The advanced alternative, or null if cannot advance
     */
    public function tryAdvance(
        AlternativeState $alternative,
        ConstructionDefinition $construction,
        object $token
    ): ?AlternativeState {
        // Check if token matches expected next element
        if (! $this->matchesExpected($alternative, $token)) {
            return null;
        }

        // Check constraints
        $constraintResult = $this->constraintChecker->checkConstraints(
            $construction,
            $alternative,
            $token
        );

        if (! $constraintResult['valid']) {
            return null;
        }

        // Advance the alternative
        $advanced = $alternative->advance($token);

        // For MWEs, check if we need tentative_complete status
        if ($advanced->isMWE() && $advanced->isComplete()) {
            if ($construction->lookaheadEnabled) {
                return new AlternativeState(
                    id: $advanced->id,
                    constructionName: $advanced->constructionName,
                    constructionType: $advanced->constructionType,
                    priority: $advanced->priority,
                    startPosition: $advanced->startPosition,
                    currentPosition: $advanced->currentPosition,
                    matchedComponents: $advanced->matchedComponents,
                    expectedNext: $advanced->expectedNext,
                    activation: $advanced->activation,
                    threshold: $advanced->threshold,
                    status: 'tentative_complete',
                    features: $advanced->features,
                    pendingConstraints: $advanced->pendingConstraints,
                    lookaheadCounter: 0,
                );
            }
        }

        return $advanced;
    }

    /**
     * Prune abandoned or stale alternatives from the state
     *
     * @param  ParseStateV4  $state  The parse state
     * @param  int  $currentPosition  Current parsing position
     * @param  int  $maxStaleness  Maximum positions without progress before pruning
     */
    public function pruneAlternatives(
        ParseStateV4 $state,
        int $currentPosition,
        int $maxStaleness = 5
    ): int {
        $pruned = 0;
        $newQueue = new \SplPriorityQueue;

        $alternatives = $state->getActiveAlternativesArray();

        foreach ($alternatives as $alt) {
            $shouldPrune = false;

            // Prune abandoned alternatives
            if ($alt->status === 'abandoned' || $alt->status === 'aggregated') {
                $shouldPrune = true;
            }

            // Prune stale alternatives (no progress for too long)
            $staleness = $currentPosition - $alt->currentPosition;
            if ($alt->status === 'pending' && $staleness > $maxStaleness && $alt->activation === 0) {
                $shouldPrune = true;
            }

            if (! $shouldPrune) {
                $newQueue->insert($alt, $alt->priority);
            } else {
                $pruned++;
            }
        }

        $state->activeAlternatives = $newQueue;

        return $pruned;
    }

    /**
     * Get all alternatives of a specific type
     *
     * @param  ParseStateV4  $state  The parse state
     * @param  string  $type  Construction type (mwe, phrasal, clausal, sentential)
     * @return array<AlternativeState>
     */
    public function getAlternativesByType(ParseStateV4 $state, string $type): array
    {
        $alternatives = $state->getActiveAlternativesArray();

        return array_filter($alternatives, fn ($alt) => $alt->constructionType === $type);
    }

    /**
     * Get all alternatives with a specific status
     *
     * @param  ParseStateV4  $state  The parse state
     * @param  string  $status  Status to filter by
     * @return array<AlternativeState>
     */
    public function getAlternativesByStatus(ParseStateV4 $state, string $status): array
    {
        $alternatives = $state->getActiveAlternativesArray();

        return array_filter($alternatives, fn ($alt) => $alt->status === $status);
    }

    /**
     * Calculate threshold for construction completion
     */
    private function calculateThreshold(ConstructionDefinition $construction): float
    {
        // For simple patterns like {NOUN}, threshold is 1
        // For multi-element patterns, count components
        // This is a simplified version - actual implementation would parse the pattern

        $pattern = $construction->pattern;

        // Count quoted words in pattern
        $wordCount = preg_match_all('/"[^"]+?"/', $pattern);

        // Count POS slots
        $slotCount = preg_match_all('/\{[A-Z]+\}/', $pattern);

        $total = max(1, $wordCount + $slotCount);

        return (float) $total;
    }

    /**
     * Compute what to expect next based on matched components
     * Simplified version - actual implementation would use compiled pattern
     */
    private function computeExpectedNext(ConstructionDefinition $construction, array $matched): array
    {
        // TODO: Implement pattern-based next expected computation
        // For now, return empty array
        return [];
    }

    /**
     * Check if token matches expected next element
     * Simplified version - actual implementation would use compiled pattern
     */
    private function matchesExpected(AlternativeState $alternative, object $token): bool
    {
        // TODO: Implement pattern-based matching
        // For now, return true to allow advancement
        return true;
    }

    /**
     * Extract features from a token
     */
    private function extractFeatures(object $token): array
    {
        $features = [];

        // Extract from features array
        if (isset($token->features) && is_array($token->features)) {
            $features = $token->features;
        }

        // Extract from feats string (UD format)
        if (isset($token->feats) && is_string($token->feats)) {
            $features = array_merge($features, $this->parseUDFeatures($token->feats));
        }

        // Add POS and lemma
        $features['pos'] = $token->upos ?? $token->pos ?? '';
        $features['lemma'] = $token->lemma ?? '';
        $features['word'] = $token->word ?? $token->form ?? '';

        return $features;
    }

    /**
     * Parse UD format features
     */
    private function parseUDFeatures(string $featsString): array
    {
        $features = [];

        if (empty($featsString) || $featsString === '_') {
            return $features;
        }

        $pairs = explode('|', $featsString);
        foreach ($pairs as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $features[trim($key)] = trim($value);
            }
        }

        return $features;
    }

    /**
     * Reset the alternative ID counter (for testing)
     */
    public static function resetIdCounter(): void
    {
        self::$alternativeIdCounter = 1;
    }

    /**
     * Get statistics about alternatives in the state
     */
    public function getStatistics(ParseStateV4 $state): array
    {
        $alternatives = $state->getActiveAlternativesArray();

        $byType = [
            'mwe' => 0,
            'phrasal' => 0,
            'clausal' => 0,
            'sentential' => 0,
        ];

        $byStatus = [];

        foreach ($alternatives as $alt) {
            if (isset($byType[$alt->constructionType])) {
                $byType[$alt->constructionType]++;
            }

            $byStatus[$alt->status] = ($byStatus[$alt->status] ?? 0) + 1;
        }

        return [
            'total' => count($alternatives),
            'byType' => $byType,
            'byStatus' => $byStatus,
        ];
    }
}

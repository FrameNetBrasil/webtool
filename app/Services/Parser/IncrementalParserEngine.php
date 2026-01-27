<?php

namespace App\Services\Parser;

use App\Data\Parser\AlternativeState;
use App\Data\Parser\ConstructionDefinition;
use App\Data\Parser\ParseStateV4;

/**
 * Incremental Parser Engine (V4)
 *
 * Main parsing engine for the V4 unified constructional parser.
 * Implements word-by-word incremental parsing with parallel construction evaluation.
 *
 * Algorithm (per word):
 * 1. INSTANTIATION: Create new alternatives for constructions that can start with this token
 * 2. UPDATE: Update existing alternatives, trying to advance them with this token
 * 3. COMPLETION: Process completed alternatives (aggregate MWEs, confirm constructions)
 * 4. LINK BUILDING: Build dependency links between confirmed nodes
 * 5. PRUNING: Remove abandoned or stale alternatives
 *
 * Key features:
 * - Parallel construction evaluation (all levels simultaneously)
 * - Priority-based resolution (MWE > Phrasal > Clausal > Sentential)
 * - MWE lookahead for ambiguous boundaries
 * - Deferred aggregation for tentative MWEs
 * - Component preservation when MWE invalidated
 *
 * @see docs/parser/v4/V4_CONSTRUCTIONAL_PARSER_PLAN.md
 * @see docs/parser/v4/MWE_LOOKAHEAD.md
 */
class IncrementalParserEngine
{
    public function __construct(
        private ConstructionRegistry $registry,
        private AlternativeManager $alternativeManager,
        private MWELookaheadService $lookaheadService,
        private MWEAggregator $aggregator,
        private ConstraintChecker $constraintChecker,
        private LinkBuilder $linkBuilder,
    ) {}

    /**
     * Parse a sentence using incremental construction-based parsing
     *
     * @param  array  $tokens  Array of UD-parsed tokens
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @return ParseStateV4 The final parse state with nodes and edges
     */
    public function parse(array $tokens, int $idGrammarGraph): ParseStateV4
    {
        // Load constructions for this grammar
        $this->registry->loadConstructions($idGrammarGraph);

        // Initialize parse state
        $state = ParseStateV4::create();

        // Process each token incrementally
        foreach ($tokens as $position => $token) {
            $state->currentPosition = $position;

            if ($this->shouldLogProgress()) {
                logger()->debug("Parser V4: Processing position {$position}", [
                    'word' => $token->word ?? $token->form ?? '',
                    'pos' => $token->upos ?? $token->pos ?? '',
                ]);
            }

            // === PHASE 1: INSTANTIATION ===
            $this->instantiateNewAlternatives($state, $token, $position);

            // === PHASE 2: UPDATE ===
            $this->updateActiveAlternatives($state, $token);

            // === PHASE 3: COMPLETION ===
            $this->processCompletedAlternatives($state, $tokens, $position);

            // === PHASE 4: LINK BUILDING ===
            $this->buildCrossLevelLinks($state, $position);

            // === PHASE 5: PRUNING ===
            $this->pruneAbandoned($state, $position);

            // Save state snapshot for debugging/backtracking
            if ($this->shouldSaveHistory()) {
                $state->saveToHistory();
            }
        }

        // Final processing
        return $this->finalize($state);
    }

    /**
     * PHASE 1: Instantiate new alternatives for constructions that can start with this token
     */
    private function instantiateNewAlternatives(ParseStateV4 $state, object $token, int $position): void
    {
        // Check limit before creating alternatives
        $maxAlternatives = config('parser.v4.maxAlternatives', 100);
        $currentCount = $state->countActiveAlternatives();

        if ($currentCount >= $maxAlternatives) {
            if ($this->shouldLogProgress()) {
                logger()->warning("⚠ Max alternatives reached ({$maxAlternatives}), skipping instantiation", [
                    'position' => $position,
                    'current' => $currentCount,
                ]);
            }

            return; // Skip this phase entirely
        }

        // Get all constructions that could start with this token
        $constructions = $this->registry->getConstructionsForToken($token);

        // Prioritize when approaching limit (80% threshold)
        if ($currentCount >= $maxAlternatives * 0.8) {
            usort($constructions, fn ($a, $b) => $b->priority <=> $a->priority);

            if ($this->shouldLogProgress()) {
                logger()->info('⚡ Approaching limit, prioritizing by priority (top constructions only)');
            }
        }

        foreach ($constructions as $construction) {
            // Hard stop if we hit limit during loop
            if ($state->countActiveAlternatives() >= $maxAlternatives) {
                if ($this->shouldLogProgress()) {
                    logger()->warning('⚠ Reached limit mid-instantiation, stopping');
                }
                break;
            }

            // Quick pre-check using constraint checker
            if (! $this->constraintChecker->canTokenMatch($construction, $token)) {
                continue;
            }

            // Create new alternative
            $alternative = $this->alternativeManager->createAlternative(
                $construction,
                $token,
                $position
            );

            // Add to active alternatives
            $state->addAlternative($alternative);

            if ($this->shouldLogProgress()) {
                logger()->debug('  ✓ Created alternative', [
                    'construction' => $construction->name,
                    'type' => $construction->constructionType,
                    'priority' => $construction->priority,
                    'status' => $alternative->status,
                ]);
            }
        }
    }

    /**
     * PHASE 2: Update active alternatives by trying to advance them with this token
     */
    private function updateActiveAlternatives(ParseStateV4 $state, object $token): void
    {
        $updatedQueue = new \SplPriorityQueue;

        $alternatives = $state->getActiveAlternativesArray();

        foreach ($alternatives as $alt) {
            // Skip if abandoned or already aggregated
            if (! $alt->isActive()) {
                continue;
            }

            // Skip if this alternative was just created at current position
            if ($alt->startPosition === $state->currentPosition) {
                $updatedQueue->insert($alt, $alt->priority);

                continue;
            }

            // Try to advance
            $construction = $this->registry->getConstruction($alt->constructionName);
            if (! $construction) {
                continue;
            }

            $advanced = $this->alternativeManager->tryAdvance($alt, $construction, $token);

            if ($advanced) {
                // Successfully advanced
                $updatedQueue->insert($advanced, $advanced->priority);

                if ($this->shouldLogProgress()) {
                    logger()->debug('  ↑ Advanced alternative', [
                        'construction' => $alt->constructionName,
                        'activation' => $advanced->activation,
                        'status' => $advanced->status,
                    ]);
                }
            } else {
                // Could not advance - try to handle optional elements or abandon
                if ($this->canSkip($alt, $construction, $token)) {
                    // Construction allows skipping - keep waiting
                    $updatedQueue->insert($alt, $alt->priority);
                } else {
                    // Cannot continue - abandon
                    $abandoned = $alt->abandon('Token mismatch');
                    $updatedQueue->insert($abandoned, $abandoned->priority);

                    if ($this->shouldLogProgress()) {
                        logger()->debug('  ✗ Abandoned alternative', [
                            'construction' => $alt->constructionName,
                            'reason' => 'Token mismatch',
                        ]);
                    }
                }
            }
        }

        $state->activeAlternatives = $updatedQueue;
    }

    /**
     * PHASE 3: Process completed alternatives (aggregate MWEs, confirm constructions)
     */
    private function processCompletedAlternatives(ParseStateV4 $state, array $allTokens, int $currentPosition): void
    {
        // Get all complete or tentatively complete alternatives
        $completed = array_merge(
            $this->alternativeManager->getAlternativesByStatus($state, 'complete'),
            $this->alternativeManager->getAlternativesByStatus($state, 'tentative_complete')
        );

        // Sort by priority (MWE > Phrasal > Clausal > Sentential)
        usort($completed, fn ($a, $b) => $b->priority <=> $a->priority);

        foreach ($completed as $alt) {
            $construction = $this->registry->getConstruction($alt->constructionName);
            if (! $construction) {
                continue;
            }

            if ($alt->isMWE()) {
                $this->processMWEAlternative($state, $alt, $construction, $allTokens, $currentPosition);
            } else {
                $this->processNonMWEAlternative($state, $alt, $construction);
            }
        }
    }

    /**
     * Process an MWE alternative (with lookahead if needed)
     */
    private function processMWEAlternative(
        ParseStateV4 $state,
        AlternativeState $alt,
        ConstructionDefinition $construction,
        array $allTokens,
        int $currentPosition
    ): void {
        if ($alt->status === 'tentative_complete') {
            // Check lookahead
            $lookaheadResult = $this->lookaheadService->checkLookahead(
                $alt,
                $construction,
                $allTokens,
                $currentPosition
            );

            switch ($lookaheadResult['status']) {
                case 'confirmed':
                    // Safe to aggregate
                    $this->lookaheadService->logConfirmation($alt, $lookaheadResult['reason']);
                    $this->aggregator->aggregateMWE($state, $alt, $construction);
                    break;

                case 'invalidated':
                    // Continuation breaks MWE - preserve components
                    $this->lookaheadService->logInvalidation(
                        $alt,
                        $lookaheadResult['reason'],
                        $lookaheadResult['matchedToken']
                    );

                    $preservationStrategy = config('parser.v4.mwe.componentPreservation', 'hybrid');
                    $this->aggregator->preserveComponents($state, $alt, $construction, $preservationStrategy);
                    break;

                case 'pending':
                    // Still within window - increment counter
                    if ($this->lookaheadService->hasExceededLookaheadWindow($alt, $construction)) {
                        // Window exceeded - confirm by default
                        $this->aggregator->aggregateMWE($state, $alt, $construction);
                    }
                    // Otherwise, keep as tentative_complete for next iteration
                    break;
            }
        } else {
            // Already confirmed or no lookahead needed
            $this->aggregator->aggregateMWE($state, $alt, $construction);
        }
    }

    /**
     * Process a non-MWE construction (phrasal, clausal, sentential)
     */
    private function processNonMWEAlternative(
        ParseStateV4 $state,
        AlternativeState $alt,
        ConstructionDefinition $construction
    ): void {
        // Determine if single token or span
        $isSingleToken = ($alt->startPosition === $alt->currentPosition);

        // Extract text from matched components
        $words = array_map(fn ($t) => $t->word ?? $t->form ?? '', $alt->matchedComponents);
        $lemmas = array_map(fn ($t) => $t->lemma ?? '', $alt->matchedComponents);
        $firstToken = $alt->matchedComponents[0] ?? null;

        // Create node structure matching MWE pattern
        $node = [
            'id' => $isSingleToken
                ? "{$construction->name}_{$alt->startPosition}"
                : "{$construction->name}_{$alt->startPosition}_{$alt->currentPosition}",
            'type' => $construction->constructionType, // phrasal, clausal, sentential
            'constructionName' => $construction->name,
            'constructionId' => $construction->idConstruction,

            // Position fields (critical for LinkBuilder)
            'position' => $isSingleToken ? $alt->startPosition : null,
            'startPosition' => ! $isSingleToken ? $alt->startPosition : null,
            'endPosition' => ! $isSingleToken ? $alt->currentPosition : null,

            // Text fields
            'word' => implode(' ', $words),
            'lemma' => implode(' ', $lemmas),
            'pos' => $firstToken->upos ?? $firstToken->pos ?? null,

            // CE labels (critical for LinkBuilder pattern matching)
            'phrasalCE' => $construction->phrasalCE,
            'clausalCE' => $construction->clausalCE,
            'sententialCE' => $construction->sententialCE,

            // Metadata
            'features' => $alt->features,
            'priority' => $alt->priority,
            'activation' => $alt->activation,
            'threshold' => $alt->threshold,
            'semanticType' => $construction->semanticType,
            'semantics' => $construction->semantics,

            // Span info
            'components' => $alt->matchedComponents,
            'componentCount' => count($alt->matchedComponents),
        ];

        // THE FIX: Actually confirm the node
        $state->confirmNode($node);

        if ($this->shouldLogProgress()) {
            logger()->debug('  ✓ Confirmed non-MWE node', [
                'construction' => $construction->name,
                'type' => $construction->constructionType,
                'id' => $node['id'],
                'span' => $isSingleToken ? "pos:{$alt->startPosition}"
                                         : "span:{$alt->startPosition}-{$alt->currentPosition}",
                'CE' => $construction->getPrimaryCE(),
            ]);
        }
    }

    /**
     * PHASE 4: Build cross-level dependency links between confirmed nodes
     */
    private function buildCrossLevelLinks(ParseStateV4 $state, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 4: Building links at position {$position}", [
                'confirmedNodesCount' => count($state->confirmedNodes),
            ]);
        }

        $this->linkBuilder->buildLinksAtPosition($state, $position, $this->shouldLogProgress());
    }

    /**
     * PHASE 5: Prune abandoned alternatives
     */
    private function pruneAbandoned(ParseStateV4 $state, int $position): void
    {
        $maxStaleness = config('parser.v4.maxStaleness', 5);
        $pruned = $this->alternativeManager->pruneAlternatives($state, $position, $maxStaleness);

        if ($pruned > 0 && $this->shouldLogProgress()) {
            logger()->debug("  🗑 Pruned {$pruned} alternatives");
        }
    }

    /**
     * Finalize parsing
     */
    private function finalize(ParseStateV4 $state): ParseStateV4
    {
        // Process any remaining valid partial matches
        $remaining = $this->alternativeManager->getAlternativesByStatus($state, 'progressing');

        foreach ($remaining as $alt) {
            if ($alt->activation > 0) {
                // Partial match - could be useful
                // TODO: Handle partial matches
            }
        }

        // Log final statistics
        if ($this->shouldLogProgress()) {
            logger()->info('Parser V4: Completed', [
                'statistics' => $state->getStatistics(),
                'registryStats' => $this->registry->getStatistics(),
                'alternativeStats' => $this->alternativeManager->getStatistics($state),
                'aggregationStats' => $this->aggregator->getAggregationStatistics($state),
            ]);
        }

        return $state;
    }

    /**
     * Check if construction allows skipping (has optional elements)
     * Placeholder - actual implementation would analyze compiled pattern
     */
    private function canSkip(
        AlternativeState $alt,
        ConstructionDefinition $construction,
        object $token
    ): bool {
        // TODO: Implement optional element handling
        return false;
    }

    /**
     * Check if should log progress
     */
    private function shouldLogProgress(): bool
    {
        return config('parser.v4.logProgress', false);
    }

    /**
     * Check if should save history
     */
    private function shouldSaveHistory(): bool
    {
        return config('parser.v4.saveHistory', false);
    }
}

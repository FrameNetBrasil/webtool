<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\GhostNode;
use App\Data\Parser\V5\ParseStateV5;
use App\Data\Parser\V5\TypeGraph;

/**
 * Incremental Parser Engine V5
 *
 * Extends V4 incremental parser with ghost node support and token graph reconfiguration.
 *
 * 9-Phase Algorithm (per word):
 * 1. INSTANTIATION: Create new alternatives for constructions matching token (V4)
 * 2. UPDATE: Update existing alternatives, advance with token (V4)
 * 3. GHOST DETECTION: Identify missing mandatory elements, create ghosts (NEW)
 * 4. GHOST FULFILLMENT: Check if token fulfills ghosts, merge if match (NEW)
 * 5. COMPLETION: Process completed alternatives, aggregate MWEs (V4)
 * 6. RECONFIGURATION: Re-link edges, re-evaluate alternatives (NEW)
 * 7. LINK BUILDING: Build dependency links including ghosts (V4+)
 * 8. PRUNING: Remove abandoned alternatives (V4)
 * 9. STATE SNAPSHOT: Capture Token Graph state (NEW)
 *
 * Key V5 Features:
 * - Type Graph: Unified construction ontology
 * - Ghost Nodes: Null instantiation for mandatory-but-implicit elements
 * - Token Graph: Runtime graph with real + ghost nodes
 * - Reconfiguration: Dynamic graph reshaping during parsing
 * - State Snapshots: Capture parse state at each position
 *
 * @see docs/parser/v5/ALGORITHM.md
 * @see docs/parser/v5/GHOST_NODES.md
 * @see docs/parser/v5/TOKEN_GRAPH_RECONFIGURATION.md
 */
class IncrementalParserEngineV5
{
    public function __construct(
        private ConstructionRegistry $registry,
        private AlternativeManager $alternativeManager,
        private MWELookaheadService $lookaheadService,
        private MWEAggregator $aggregator,
        private ConstraintChecker $constraintChecker,
        private LinkBuilder $linkBuilder,
        private GhostNodeManager $ghostManager,
        private TokenGraphReconfigurator $reconfigurator,
    ) {}

    /**
     * Parse a sentence using V5 incremental construction-based parsing with ghost nodes
     *
     * @param  array  $tokens  Array of UD-parsed tokens
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  int  $idParserGraph  Parser graph ID
     * @param  TypeGraph|null  $typeGraph  Pre-built Type Graph (optional)
     * @return ParseStateV5 The final parse state with nodes, edges, and ghosts
     */
    public function parse(
        array $tokens,
        int $idGrammarGraph,
        int $idParserGraph,
        ?TypeGraph $typeGraph = null
    ): ParseStateV5 {
        // Load constructions for this grammar
        $this->registry->loadConstructions($idGrammarGraph);

        // Initialize V5 parse state
        $state = ParseStateV5::create(
            idParserGraph: $idParserGraph,
            idGrammarGraph: $idGrammarGraph,
            sentence: $this->extractSentenceText($tokens),
            tokens: $tokens,
            typeGraph: $typeGraph
        );

        // Clear ghost manager for fresh parse
        $this->ghostManager->clear();

        if ($this->shouldLogProgress()) {
            logger()->info('Parser V5: Starting parse', [
                'tokenCount' => count($tokens),
                'grammarGraph' => $idGrammarGraph,
                'parserGraph' => $idParserGraph,
            ]);
        }

        // Process each token incrementally
        foreach ($tokens as $position => $token) {
            $state->currentPosition = $position;

            if ($this->shouldLogProgress()) {
                logger()->debug("Parser V5: Processing position {$position}", [
                    'word' => $token->word ?? $token->form ?? '',
                    'pos' => $token->upos ?? $token->pos ?? '',
                ]);
            }

            // === PHASE 1: INSTANTIATION (V4) ===
            $this->instantiateNewAlternatives($state, $token, $position);

            // === PHASE 2: UPDATE (V4) ===
            $this->updateActiveAlternatives($state, $token);

            // === PHASE 3: GHOST DETECTION (NEW) ===
            $this->detectMissingMandatoryElements($state, $position);

            // === PHASE 4: GHOST FULFILLMENT (NEW) ===
            $this->fulfillGhosts($state, $token, $position);

            // === PHASE 5: COMPLETION (V4) ===
            $this->processCompletedAlternatives($state, $tokens, $position);

            // === PHASE 6: RECONFIGURATION (NEW) ===
            $this->reconfigureGraph($state, $position);

            // === PHASE 7: LINK BUILDING (V4+) ===
            $this->buildCrossLevelLinks($state, $position);

            // === PHASE 8: PRUNING (V4) ===
            $this->pruneAbandoned($state, $position);

            // === PHASE 9: STATE SNAPSHOT (NEW) ===
            $this->captureStateSnapshot($state);

            // Advance to next position
            $state->advance();
        }

        // Final processing
        return $this->finalize($state);
    }

    /**
     * PHASE 1: Instantiate new alternatives (V4 logic)
     */
    private function instantiateNewAlternatives(ParseStateV5 $state, object $token, int $position): void
    {
        // Check limit before creating alternatives
        $maxAlternatives = function_exists('config') ? config('parser.v5.maxAlternatives', 100) : 100;
        $currentCount = count($state->alternatives);

        if ($currentCount >= $maxAlternatives) {
            if ($this->shouldLogProgress()) {
                logger()->warning("⚠ Max alternatives reached ({$maxAlternatives}), skipping instantiation", [
                    'position' => $position,
                    'current' => $currentCount,
                ]);
            }

            return;
        }

        // Get all constructions that could start with this token
        $constructions = $this->registry->getConstructionsForToken($token);

        // Prioritize when approaching limit (80% threshold)
        if ($currentCount >= $maxAlternatives * 0.8) {
            usort($constructions, fn ($a, $b) => $b->priority <=> $a->priority);

            if ($this->shouldLogProgress()) {
                logger()->info('⚡ Approaching limit, prioritizing by priority');
            }
        }

        foreach ($constructions as $construction) {
            // Hard stop if we hit limit during loop
            if (count($state->alternatives) >= $maxAlternatives) {
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
            $state->alternatives[] = $alternative->toArray();

            if ($this->shouldLogProgress()) {
                logger()->debug('  ✓ Created alternative', [
                    'construction' => $construction->name,
                    'type' => $construction->constructionType,
                    'priority' => $construction->priority,
                ]);
            }
        }
    }

    /**
     * PHASE 2: Update active alternatives (V4 logic)
     */
    private function updateActiveAlternatives(ParseStateV5 $state, object $token): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug('PHASE 2: Updating alternatives', [
                'beforeUpdate' => count($state->alternatives),
                'position' => $state->currentPosition,
            ]);
        }

        $updatedAlternatives = [];

        foreach ($state->alternatives as $idx => $alt) {
            if ($this->shouldLogProgress()) {
                logger()->debug("  Checking alternative {$idx}", [
                    'construction' => $alt['constructionName'] ?? 'unknown',
                    'status' => $alt['status'] ?? 'active',
                    'startPosition' => $alt['startPosition'] ?? 'not set',
                    'currentPosition' => $state->currentPosition,
                ]);
            }

            // Keep if this alternative was just created at current position (regardless of status)
            if (($alt['startPosition'] ?? 0) === $state->currentPosition) {
                $updatedAlternatives[] = $alt;
                if ($this->shouldLogProgress()) {
                    logger()->debug('    → Keeping (just created)');
                }

                continue;
            }

            // Skip if abandoned (but not if pending/complete - those need to try advancing)
            if (($alt['status'] ?? '') === 'abandoned') {
                if ($this->shouldLogProgress()) {
                    logger()->debug('    → Skipping (abandoned)');
                }

                continue;
            }

            // Keep completed/pending alternatives for ghost detection (Phase 3)
            // They won't be advanced, but we need them to check for missing mandatory elements
            if (in_array($alt['status'] ?? 'active', ['complete', 'pending'])) {
                $updatedAlternatives[] = $alt;
                if ($this->shouldLogProgress()) {
                    logger()->debug('    → Keeping for ghost detection', [
                        'status' => $alt['status'],
                    ]);
                }

                continue;
            }

            // Try to advance active alternatives
            $construction = $this->registry->getConstruction($alt['constructionName'] ?? '');
            if (! $construction) {
                if ($this->shouldLogProgress()) {
                    logger()->debug('    → Skipping (construction not found)');
                }

                continue;
            }

            // For now, keep alternatives that need advancing
            // TODO: Implement proper advancement logic with AlternativeManager
            $updatedAlternatives[] = $alt;
            if ($this->shouldLogProgress()) {
                logger()->debug('    → Keeping (active)', [
                    'construction' => $alt['constructionName'],
                ]);
            }
        }

        $state->alternatives = $updatedAlternatives;

        if ($this->shouldLogProgress()) {
            logger()->debug('  Update complete', [
                'afterUpdate' => count($state->alternatives),
                'active' => count(array_filter($updatedAlternatives, fn ($a) => ($a['status'] ?? 'active') === 'active')),
                'abandoned' => count(array_filter($updatedAlternatives, fn ($a) => ($a['status'] ?? '') === 'abandoned')),
            ]);
        }
    }

    /**
     * PHASE 3: Detect missing mandatory elements and create ghosts (NEW)
     */
    private function detectMissingMandatoryElements(ParseStateV5 $state, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 3: Detecting missing mandatory elements at position {$position}");
        }

        // Check each active alternative for missing mandatory elements
        foreach ($state->alternatives as &$alternative) {
            if (($alternative['status'] ?? 'active') !== 'active') {
                continue;
            }

            $construction = $this->registry->getConstruction($alternative['constructionName'] ?? '');
            if (! $construction) {
                continue;
            }

            // Get mandatory elements for this construction
            $mandatoryElements = $construction->mandatoryElements ?? [];
            if (empty($mandatoryElements)) {
                continue;
            }

            // Check which mandatory elements are present
            $presentElements = array_column($alternative['nodes'] ?? [], 'ce');
            $missingElements = array_diff($mandatoryElements, $presentElements);

            // Create ghosts for missing mandatory elements
            foreach ($missingElements as $missingCE) {
                // Check if we already have a ghost for this element in this alternative
                $hasGhost = false;
                foreach ($alternative['nodes'] ?? [] as $node) {
                    if (($node['isGhost'] ?? false) &&
                        ($node['expectedCE'] ?? null) === $missingCE &&
                        ! ($node['isFulfilled'] ?? false)) {
                        $hasGhost = true;
                        break;
                    }
                }

                if ($hasGhost) {
                    continue; // Already have a pending ghost for this element
                }

                // Determine ghost type based on CE
                $ghostType = $this->determineGhostType($missingCE);

                // Create ghost node
                $ghost = $this->ghostManager->createGhost(
                    ghostType: $ghostType,
                    position: $position,
                    alternativeId: $alternative['id'] ?? 0,
                    constructionId: $construction->idConstruction,
                    expectedCE: $missingCE,
                    expectedPOS: $this->determineExpectedPOS($missingCE),
                    metadata: [
                        'constructionName' => $construction->name,
                        'reason' => "Mandatory element '{$missingCE}' missing",
                    ]
                );

                // Add ghost to Token Graph
                $state->tokenGraph->addGhostNode($ghost);

                // Add ghost to alternative's nodes
                $alternative['nodes'][] = [
                    'id' => $ghost->id,
                    'ce' => $missingCE,
                    'isGhost' => true,
                    'isFulfilled' => false,
                    'expectedCE' => $missingCE,
                    'ghostType' => $ghostType,
                ];

                // Log ghost creation
                $state->logReconfiguration(
                    \App\Data\Parser\V5\ReconfigurationOperation::ghostCreated(
                        ghostId: $ghost->id,
                        position: $position,
                        alternativeId: $alternative['id'] ?? 0,
                        constructionId: $construction->idConstruction,
                        ghostType: $ghostType,
                        expectedCE: $missingCE
                    )
                );

                if ($this->shouldLogProgress()) {
                    logger()->debug('  👻 Created ghost', [
                        'ghostId' => $ghost->id,
                        'type' => $ghostType,
                        'expectedCE' => $missingCE,
                        'construction' => $construction->name,
                    ]);
                }
            }
        }
    }

    /**
     * PHASE 4: Fulfill ghosts with real nodes (NEW)
     */
    private function fulfillGhosts(ParseStateV5 $state, object $token, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 4: Checking ghost fulfillment at position {$position}");
        }

        // Create real node representation from token
        $realNode = $this->createRealNodeFromToken($token, $position);

        // Add real node to Token Graph
        $state->tokenGraph->addRealNode($realNode);

        // Check if this real node can fulfill any ghosts
        $result = $this->reconfigurator->reconfigureAfterFulfillment($state, $realNode, $position);

        if ($result) {
            if ($this->shouldLogProgress()) {
                logger()->debug('  ✓ Ghost fulfilled', [
                    'ghostId' => $result['ghostId'],
                    'realNodeId' => $result['realNodeId'],
                    'relinkedEdges' => count($result['relinkedEdges']),
                ]);
            }

            // Update alternatives to reflect fulfillment
            foreach ($state->alternatives as &$alternative) {
                foreach ($alternative['nodes'] ?? [] as &$node) {
                    if (($node['id'] ?? null) === $result['ghostId']) {
                        $node['isFulfilled'] = true;
                        $node['fulfilledBy'] = $result['realNodeId'];
                        break;
                    }
                }
            }
        }
    }

    /**
     * PHASE 5: Process completed alternatives (V4 logic)
     */
    private function processCompletedAlternatives(ParseStateV5 $state, array $allTokens, int $currentPosition): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 5: Processing completed alternatives at position {$currentPosition}");
        }

        // Get all complete alternatives
        $completed = array_filter(
            $state->alternatives,
            fn ($alt) => in_array($alt['status'] ?? 'active', ['complete', 'tentative_complete'])
        );

        // Sort by priority (MWE > Phrasal > Clausal > Sentential)
        usort($completed, fn ($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));

        foreach ($completed as $alt) {
            $construction = $this->registry->getConstruction($alt['constructionName'] ?? '');
            if (! $construction) {
                continue;
            }

            if ($this->isMWEConstruction($construction)) {
                $this->processMWEAlternative($state, $alt, $construction, $allTokens, $currentPosition);
            } else {
                $this->processNonMWEAlternative($state, $alt, $construction);
            }
        }
    }

    /**
     * PHASE 6: Reconfigure graph after changes (NEW)
     */
    private function reconfigureGraph(ParseStateV5 $state, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 6: Reconfiguring graph at position {$position}");
        }

        // Re-evaluate affected alternatives
        // (already done in fulfillGhosts, but could do additional checks here)

        // Expire stale ghosts (created more than N positions ago)
        $maxGhostAge = function_exists('config') ? config('parser.v5.maxGhostAge', 3) : 3;
        if ($position > $maxGhostAge) {
            $expired = $this->ghostManager->expireStaleGhosts($position - $maxGhostAge);

            if ($expired > 0 && $this->shouldLogProgress()) {
                logger()->debug("  🕐 Expired {$expired} stale ghosts");
            }
        }
    }

    /**
     * PHASE 7: Build cross-level links (V4 logic)
     */
    private function buildCrossLevelLinks(ParseStateV5 $state, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 7: Building links at position {$position}");
        }

        // Note: LinkBuilder needs to be updated to handle ghost nodes
        // For now, we skip link building to avoid errors
        // TODO: Update LinkBuilder to support ghosts
    }

    /**
     * PHASE 8: Prune abandoned alternatives (V4 logic)
     */
    private function pruneAbandoned(ParseStateV5 $state, int $position): void
    {
        if ($this->shouldLogProgress()) {
            logger()->debug("PHASE 8: Pruning abandoned alternatives at position {$position}", [
                'beforePruning' => count($state->alternatives),
            ]);
        }

        $maxStaleness = function_exists('config') ? config('parser.v5.maxStaleness', 5) : 5;

        // Remove alternatives that are:
        // - Abandoned
        // - Too old (position - startPosition > maxStaleness)
        $before = count($state->alternatives);

        $state->alternatives = array_filter(
            $state->alternatives,
            function ($alt) use ($position, $maxStaleness) {
                if (($alt['status'] ?? 'active') === 'abandoned') {
                    return false;
                }

                $age = $position - ($alt['startPosition'] ?? 0);
                if ($age > $maxStaleness && ($alt['status'] ?? 'active') !== 'complete') {
                    return false;
                }

                return true;
            }
        );

        $pruned = $before - count($state->alternatives);

        if ($this->shouldLogProgress()) {
            logger()->debug('  Pruning result', [
                'before' => $before,
                'after' => count($state->alternatives),
                'pruned' => $pruned,
            ]);
        }

        if ($pruned > 0 && $this->shouldLogProgress()) {
            logger()->debug("  🗑 Pruned {$pruned} alternatives");
        }
    }

    /**
     * PHASE 9: Capture state snapshot (NEW)
     */
    private function captureStateSnapshot(ParseStateV5 $state): void
    {
        // Always capture snapshots in memory (lightweight, essential for debugging)
        $snapshot = $state->captureSnapshot();

        if ($this->shouldLogProgress()) {
            logger()->debug('  📸 Captured state snapshot', [
                'position' => $snapshot['position'],
                'activeAlternatives' => $snapshot['activeAlternatives'],
                'confirmedNodes' => $snapshot['confirmedNodes'],
                'ghostNodes' => count($snapshot['ghostNodes'] ?? []),
            ]);
        }
    }

    /**
     * Finalize parsing
     */
    private function finalize(ParseStateV5 $state): ParseStateV5
    {
        // Expire all pending ghosts at sentence end
        $expiredCount = $this->ghostManager->expirePendingGhosts();

        // Mark state as complete
        $state->markComplete();

        // Log final statistics
        if ($this->shouldLogProgress()) {
            logger()->info('Parser V5: Completed', [
                'statistics' => $state->getStatistics(),
                'ghostStats' => $this->ghostManager->getStatistics(),
                'reconfigurationStats' => $this->reconfigurator->getStatistics($state),
            ]);
        }

        return $state;
    }

    /**
     * Helper: Determine ghost type from CE label
     */
    private function determineGhostType(string $ce): string
    {
        return match (true) {
            str_contains(strtolower($ce), 'head') => GhostNode::TYPE_IMPLICIT_HEAD,
            str_contains(strtolower($ce), 'subj') => GhostNode::TYPE_SUBJECT_PRO,
            str_contains(strtolower($ce), 'arg') => GhostNode::TYPE_DROPPED_ARGUMENT,
            str_contains(strtolower($ce), 'mod') => GhostNode::TYPE_IMPLICIT_MODIFIER,
            default => GhostNode::TYPE_IMPLICIT_HEAD,
        };
    }

    /**
     * Helper: Determine expected POS from CE label
     */
    private function determineExpectedPOS(string $ce): ?string
    {
        return match (true) {
            str_contains(strtolower($ce), 'noun') => 'NOUN',
            str_contains(strtolower($ce), 'verb') => 'VERB',
            str_contains(strtolower($ce), 'adj') => 'ADJ',
            str_contains(strtolower($ce), 'adv') => 'ADV',
            str_contains(strtolower($ce), 'adp') => 'ADP',
            str_contains(strtolower($ce), 'det') => 'DET',
            str_contains(strtolower($ce), 'pro') => 'PRON',
            default => null,
        };
    }

    /**
     * Helper: Create real node from token
     */
    private function createRealNodeFromToken(object $token, int $position): array
    {
        static $nodeIdCounter = 1;

        return [
            'idNode' => $nodeIdCounter++,
            'label' => $token->word ?? $token->form ?? '',
            'lemma' => $token->lemma ?? '',
            'positionInSentence' => $position,
            'udpos' => $token->upos ?? $token->pos ?? null,
            'features' => $token->feats ?? $token->features ?? null,
        ];
    }

    /**
     * Helper: Extract sentence text from tokens
     */
    private function extractSentenceText(array $tokens): string
    {
        $words = array_map(fn ($t) => $t->word ?? $t->form ?? '', $tokens);

        return implode(' ', $words);
    }

    /**
     * Helper: Check if construction is MWE
     */
    private function isMWEConstruction($construction): bool
    {
        return ($construction->constructionType ?? '') === 'mwe';
    }

    /**
     * Helper: Process MWE alternative (placeholder)
     */
    private function processMWEAlternative($state, $alt, $construction, $allTokens, $currentPosition): void
    {
        // TODO: Implement MWE processing with reconfiguration
        if ($this->shouldLogProgress()) {
            logger()->debug('  ⚠ MWE processing not yet implemented in V5');
        }
    }

    /**
     * Helper: Process non-MWE alternative (placeholder)
     */
    private function processNonMWEAlternative($state, $alt, $construction): void
    {
        // TODO: Implement non-MWE processing with reconfiguration
        if ($this->shouldLogProgress()) {
            logger()->debug('  ⚠ Non-MWE processing not yet implemented in V5');
        }
    }

    /**
     * Check if should log progress
     */
    private function shouldLogProgress(): bool
    {
        return function_exists('config') ? config('parser.v5.logProgress', false) : false;
    }

    /**
     * Check if should save snapshots
     */
    private function shouldSaveSnapshots(): bool
    {
        return function_exists('config') ? config('parser.v5.saveSnapshots', false) : false;
    }
}

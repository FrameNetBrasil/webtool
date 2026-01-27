<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\L5Layer;
use App\Models\CLN_RNT\Node;

/**
 * Partial Construction Manager
 *
 * Manages the lifecycle of partial constructions (partially activated patterns at L5).
 * Partial constructions represent construction hypotheses that predict future input.
 *
 * Key responsibilities:
 * - Create partial constructions when pattern partially matches
 * - Update partial constructions when new elements match
 * - Confirm partial constructions to full constructions when complete
 * - Expire partial constructions when predictions fail
 * - Track partial construction activation and competition
 * - Generate predictions from active partial constructions
 */
class PartialConstructionManager
{
    /**
     * All partial constructions indexed by construction ID and position
     * Format: ['constructionId_position' => Node]
     */
    private array $partialConstructions = [];

    /**
     * Node factory for creating partial construction nodes
     */
    private NodeFactory $factory;

    /**
     * Prediction engine for generating predictions
     */
    private PredictionEngine $predictionEngine;

    /**
     * Create a new Partial Construction Manager
     *
     * @param  NodeFactory|null  $factory  Optional factory
     * @param  PredictionEngine|null  $predictionEngine  Optional engine
     */
    public function __construct(
        ?NodeFactory $factory = null,
        ?PredictionEngine $predictionEngine = null
    ) {
        $this->factory = $factory ?? new NodeFactory;
        $this->predictionEngine = $predictionEngine ?? new PredictionEngine;
    }

    // ========================================================================
    // partial construction Lifecycle
    // ========================================================================

    /**
     * Create a partial construction
     *
     * Called when a pattern partially matches input.
     *
     * @param  L5Layer  $l5  Target L5 layer
     * @param  int  $constructionId  Construction ID from database
     * @param  string  $name  Construction name
     * @param  array  $pattern  Pattern elements
     * @param  array  $matched  Boolean array of matched elements
     * @param  int  $anchorPosition  Column where construction started
     * @return Node The created partial construction
     */
    public function createPartialConstruction(
        L5Layer $l5,
        int $constructionId,
        string $name,
        array $pattern,
        array $matched,
        int $anchorPosition
    ): Node {
        // Create partial construction node
        $partialConstruction = $l5->createPartialConstruction(
            constructionId: $constructionId,
            metadata: [
                'name' => $name,
                'pattern' => $pattern,
                'matched' => $matched,
                'anchor_position' => $anchorPosition,
            ]
        );

        // Track partial construction
        $key = $this->getPartialConstructionKey($constructionId, $anchorPosition);
        $this->partialConstructions[$key] = $partialConstruction;

        return $partialConstruction;
    }

    /**
     * Update partial construction when element matches
     *
     * Updates the matched array to reflect new element.
     *
     * @param  Node  $partialConstruction  PartialConstruction to update
     * @param  int  $elementIndex  Index of element that matched
     * @return bool True if partial construction is now fully matched
     */
    public function updatePartialConstruction(Node $partialConstruction, int $elementIndex): bool
    {
        $matched = $partialConstruction->metadata['matched'] ?? [];

        // Mark element as matched
        if (isset($matched[$elementIndex])) {
            $matched[$elementIndex] = true;
            $partialConstruction->metadata['matched'] = $matched;
        }

        // Check if all elements matched
        return $this->isPartialConstructionComplete($partialConstruction);
    }

    /**
     * Promote partial construction to full construction
     *
     * When all pattern elements match, partial construction becomes confirmed construction.
     *
     * @param  L5Layer  $l5  L5 layer containing partial construction
     * @param  Node  $partialConstruction  PartialConstruction to promote
     */
    public function promotePartialConstruction(L5Layer $l5, Node $partialConstruction): void
    {
        $l5->confirmConstruction($partialConstruction->id);

        // Remove from tracking
        $constructionId = $partialConstruction->metadata['construction_id'] ?? 0;
        $anchorPosition = $partialConstruction->metadata['anchor_position'] ?? 0;
        $key = $this->getPartialConstructionKey($constructionId, $anchorPosition);
        unset($this->partialConstructions[$key]);
    }

    /**
     * Expire partial construction
     *
     * Remove partial construction when predictions don't match or construction is no longer viable.
     *
     * @param  L5Layer  $l5  L5 layer containing partial construction
     * @param  Node  $partialConstruction  PartialConstruction to expire
     */
    public function expirePartialConstruction(L5Layer $l5, Node $partialConstruction): void
    {
        $l5->expirePartialConstruction($partialConstruction->id);

        // Remove from tracking
        $constructionId = $partialConstruction->metadata['construction_id'] ?? 0;
        $anchorPosition = $partialConstruction->metadata['anchor_position'] ?? 0;
        $key = $this->getPartialConstructionKey($constructionId, $anchorPosition);
        unset($this->partialConstructions[$key]);
    }

    // ========================================================================
    // Querying
    // ========================================================================

    /**
     * Get partial constructions at specific position
     *
     * @param  int  $position  Column position
     * @return array Array of partial construction Nodes
     */
    public function getPartialConstructionsAtPosition(int $position): array
    {
        return array_filter(
            $this->partialConstructions,
            fn ($partialConstruction) => ($partialConstruction->metadata['anchor_position'] ?? null) === $position
        );
    }

    /**
     * Get partial constructions for specific construction
     *
     * @param  int  $constructionId  Construction ID
     * @return array Array of partial construction Nodes
     */
    public function getPartialConstructionsForConstruction(int $constructionId): array
    {
        return array_filter(
            $this->partialConstructions,
            fn ($partialConstruction) => ($partialConstruction->metadata['construction_id'] ?? null) === $constructionId
        );
    }

    /**
     * Get all active partial constructions
     *
     * @return array All partial constructions
     */
    public function getActivePartialConstructions(): array
    {
        return $this->partialConstructions;
    }

    /**
     * Check if partial construction is fully matched
     *
     * @param  Node  $partialConstruction  PartialConstruction to check
     * @return bool True if all elements matched
     */
    public function isPartialConstructionComplete(Node $partialConstruction): bool
    {
        $matched = $partialConstruction->metadata['matched'] ?? [];

        if (empty($matched)) {
            return false;
        }

        // All elements must be matched
        foreach ($matched as $isMatched) {
            if (! $isMatched) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get partial construction activation level
     *
     * Returns proportion of pattern elements that matched.
     *
     * @param  Node  $partialConstruction  PartialConstruction to check
     * @param  L5Layer  $l5  L5 layer (for boost information)
     * @return float Activation level (0-1)
     */
    public function getPartialConstructionActivation(Node $partialConstruction, L5Layer $l5): float
    {
        $matched = $partialConstruction->metadata['matched'] ?? [];
        $pattern = $partialConstruction->metadata['pattern'] ?? [];

        if (empty($pattern)) {
            return 0.0;
        }

        // Base activation
        $matchedCount = count(array_filter($matched));
        $baseActivation = $matchedCount / count($pattern);

        // Add boosts from confirmations
        $boost = $l5->getPartialConstructionBoost($partialConstruction->id);

        return min(1.0, $baseActivation + $boost);
    }

    // ========================================================================
    // Prediction
    // ========================================================================

    /**
     * Generate predictions from partial construction
     *
     * Uses PredictionEngine to generate predictions for next position.
     *
     * @param  Node  $partialConstruction  partial construction
     * @param  int  $targetPosition  Target column position
     * @return array Array of Prediction objects
     */
    public function predictionsFromPartialConstruction(Node $partialConstruction, int $targetPosition): array
    {
        return $this->predictionEngine->generatePredictionsFromPartialConstruction($partialConstruction, $targetPosition);
    }

    /**
     * Generate all predictions from active partial constructions
     *
     * Collects predictions from all partial constructions, merges duplicates, prioritizes by strength.
     *
     * @param  int  $targetPosition  Target column position
     * @return array Array of Prediction objects
     */
    public function generateAllPredictions(int $targetPosition): array
    {
        $allPredictions = [];

        foreach ($this->partialConstructions as $partialConstruction) {
            $predictions = $this->predictionsFromPartialConstruction($partialConstruction, $targetPosition);
            $allPredictions = array_merge($allPredictions, $predictions);
        }

        // Merge duplicate predictions (keep strongest)
        $merged = $this->predictionEngine->mergePredictions($allPredictions);

        // Prioritize by strength
        return $this->predictionEngine->prioritizePredictions($merged);
    }

    // ========================================================================
    // Competition
    // ========================================================================

    /**
     * Apply competition between partial constructions
     *
     * Stronger partial constructions inhibit weaker ones at the same position.
     *
     * @param  L5Layer  $l5  L5 layer containing partial constructions
     * @return array Competition results
     */
    public function applyCompetition(L5Layer $l5): array
    {
        $inhibitionStrength = config('cln.activation.inhibition_strength', 0.5);

        // Group partial constructions by anchor position
        $groups = [];
        foreach ($this->partialConstructions as $partialConstruction) {
            $anchorPos = $partialConstruction->metadata['anchor_position'] ?? 0;
            if (! isset($groups[$anchorPos])) {
                $groups[$anchorPos] = [];
            }
            $groups[$anchorPos][] = $partialConstruction;
        }

        $inhibited = [];

        foreach ($groups as $position => $partialConstructions) {
            if (count($partialConstructions) < 2) {
                continue; // No competition
            }

            // Sort by activation (descending)
            usort($partialConstructions, function ($a, $b) use ($l5) {
                return $this->getPartialConstructionActivation($b, $l5) <=> $this->getPartialConstructionActivation($a, $l5);
            });

            // Strongest wins, others inhibited
            $winner = array_shift($partialConstructions);

            foreach ($partialConstructions as $loser) {
                $inhibited[] = [
                    'partial construction_id' => $loser->id,
                    'inhibited_by' => $winner->id,
                    'inhibition_amount' => $inhibitionStrength,
                ];
            }
        }

        return [
            'inhibited_count' => count($inhibited),
            'inhibited_partial constructions' => $inhibited,
        ];
    }

    /**
     * Find mutually exclusive partial constructions
     *
     * PartialConstructions are mutually exclusive if they occupy the same span.
     *
     * @param  Node  $partialConstruction  PartialConstruction to check
     * @return array Array of mutually exclusive partial constructions
     */
    public function findMutuallyExclusive(Node $partialConstruction): array
    {
        $anchorPos = $partialConstruction->metadata['anchor_position'] ?? 0;
        $pattern = $partialConstruction->metadata['pattern'] ?? [];
        $span = count($pattern);

        $mutuallyExclusive = [];

        foreach ($this->partialConstructions as $otherPartialConstruction) {
            if ($otherPartialConstruction->id === $partialConstruction->id) {
                continue;
            }

            $otherAnchorPos = $otherPartialConstruction->metadata['anchor_position'] ?? 0;
            $otherPattern = $otherPartialConstruction->metadata['pattern'] ?? [];
            $otherSpan = count($otherPattern);

            // Check if spans overlap
            $partialConstructionEnd = $anchorPos + $span - 1;
            $otherPartialConstructionEnd = $otherAnchorPos + $otherSpan - 1;

            if ($this->spansOverlap($anchorPos, $partialConstructionEnd, $otherAnchorPos, $otherPartialConstructionEnd)) {
                $mutuallyExclusive[] = $otherPartialConstruction;
            }
        }

        return $mutuallyExclusive;
    }

    // ========================================================================
    // Private Helpers
    // ========================================================================

    /**
     * Get unique key for partial construction
     *
     * @param  int  $constructionId  Construction ID
     * @param  int  $anchorPosition  Anchor position
     * @return string Unique key
     */
    private function getPartialConstructionKey(int $constructionId, int $anchorPosition): string
    {
        return sprintf('%d_%d', $constructionId, $anchorPosition);
    }

    /**
     * Check if two spans overlap
     *
     * @param  int  $start1  Start of first span
     * @param  int  $end1  End of first span
     * @param  int  $start2  Start of second span
     * @param  int  $end2  End of second span
     * @return bool True if spans overlap
     */
    private function spansOverlap(int $start1, int $end1, int $start2, int $end2): bool
    {
        return ! ($end1 < $start2 || $end2 < $start1);
    }

    // ========================================================================
    // Cross-Column Pattern Matching
    // ========================================================================

    /**
     * Update partial constructions from previous columns based on current L23 nodes
     *
     * This is critical for multi-token constructions: partial constructions created in
     * Column 0 need to be checked against tokens arriving in Columns 1, 2, etc.
     *
     * When a token arrives at position N, this method checks all partial constructions
     * from positions 0 to N-1 to see if the token matches their next expected element.
     *
     * Uses graph traversal state to handle alternatives, optionals, and repetition.
     *
     * @param  array  $partialConstructions  Array of Node partial constructions
     * @param  array  $l23Nodes  L23 nodes from current column
     * @param  int  $currentPosition  Current token position
     * @return array Array of update actions: ['action' => 'update'|'confirm', 'partial' => Node]
     */
    public function updatePartials(
        array $partialConstructions,
        array $l23Nodes,
        int $currentPosition
    ): array {
        $matcher = new PatternMatcher;
        $updated = [];

        foreach ($partialConstructions as $partial) {
            $anchorPosition = $partial->metadata['anchor_position'] ?? 0;

            // Ensure graph traversal state exists
            $traversalState = $partial->metadata['traversal_state'] ?? null;
            $graph = $partial->metadata['graph'] ?? null;

            if ($traversalState === null || $graph === null) {
                throw new \RuntimeException(
                    'Partial construction missing required graph traversal state. '.
                    'All partial constructions must use graph-based pattern matching.'
                );
            }

            // Use graph traversal logic
            $spanLength = $partial->metadata['span_length'] ?? 0;
            $expectedIndex = $currentPosition - $anchorPosition;

            // Skip if this token position already processed
            if ($expectedIndex < $spanLength) {
                continue; // Already matched this position
            }

            // Find possible next nodes from current graph position
            $currentNodeId = $traversalState['current_node_id'] ?? null;
            if (! $currentNodeId) {
                continue;
            }

            $possibleNextNodes = $this->findNextPossibleNodes($graph, $currentNodeId, $traversalState);

            // Try to match and select best option
            $bestMatch = $this->selectBestMatch($l23Nodes, $possibleNextNodes, $matcher);

            if ($bestMatch) {
                // Update traversal state
                $newTraversalState = $this->advanceTraversalState(
                    $traversalState,
                    $bestMatch['node_id'],
                    $bestMatch['path_type']
                );

                $partial->metadata['traversal_state'] = $newTraversalState;
                $partial->metadata['span_length'] = $expectedIndex + 1;

                // Check if complete
                if ($this->isPatternComplete($graph, $newTraversalState)) {
                    $updated[] = ['action' => 'confirm', 'partial' => $partial];
                } else {
                    $updated[] = ['action' => 'update', 'partial' => $partial];
                }
            }

            // NOTE: We do NOT create L23 → L5 connections here!
            // Cross-column connections violate CLN architecture.
            // Circuit 1 (L23 → L5) is WITHIN COLUMN ONLY.
        }

        return $updated;
    }

    /**
     * Find possible next nodes (delegates to L5Layer logic)
     *
     * @param  array  $graph  Pattern graph
     * @param  string  $currentNodeId  Current node ID
     * @param  array  $traversalState  Traversal state
     * @return array Possible next nodes
     */
    private function findNextPossibleNodes(array $graph, string $currentNodeId, array $traversalState): array
    {
        // This is the same logic as L5Layer::findNextPossibleNodes()
        // Duplicated here to avoid coupling
        $possibilities = [];
        $edges = $graph['edges'] ?? [];
        $nodes = $graph['nodes'] ?? [];

        foreach ($edges as $edge) {
            if ($edge['from'] !== $currentNodeId) {
                continue;
            }

            $targetNodeId = $edge['to'];
            $targetNode = $nodes[$targetNodeId] ?? null;

            if (! $targetNode || ($targetNode['type'] ?? '') === 'END') {
                continue;
            }

            $pathType = 'sequential';
            if ($edge['bypass'] ?? false) {
                $pathType = 'bypass';
            }
            if ($targetNodeId === $currentNodeId) {
                $pathType = 'repeat';
            }

            $possibilities[] = [
                'node_id' => $targetNodeId,
                'node' => $targetNode,
                'path_type' => $pathType,
                'edge' => $edge,
            ];
        }

        return $possibilities;
    }

    /**
     * Select best match (delegates to L5Layer logic)
     *
     * @param  array  $l23Nodes  L23 nodes
     * @param  array  $possibleNextNodes  Possible nodes
     * @param  PatternMatcher  $matcher  Matcher instance
     * @return array|null Best match
     */
    private function selectBestMatch(array $l23Nodes, array $possibleNextNodes, PatternMatcher $matcher): ?array
    {
        $matches = [];

        foreach ($possibleNextNodes as $possibility) {
            if ($matcher->matchesNode($l23Nodes, $possibility['node'])) {
                $matches[] = $possibility;
            }
        }

        if (empty($matches)) {
            foreach ($possibleNextNodes as $possibility) {
                if ($possibility['path_type'] === 'bypass') {
                    return $possibility;
                }
            }

            return null;
        }

        // Prefer non-bypass
        $nonBypass = array_filter($matches, fn ($m) => $m['path_type'] !== 'bypass');
        if (! empty($nonBypass)) {
            $matches = $nonBypass;
        }

        // Prefer repeat (greedy)
        $repeats = array_filter($matches, fn ($m) => $m['path_type'] === 'repeat');
        if (! empty($repeats)) {
            return $repeats[0];
        }

        return $matches[0];
    }

    /**
     * Advance traversal state (delegates to L5Layer logic)
     *
     * @param  array  $traversalState  Current state
     * @param  string  $matchedNodeId  Matched node
     * @param  string  $pathType  Path type
     * @return array Updated state
     */
    private function advanceTraversalState(array $traversalState, string $matchedNodeId, string $pathType): array
    {
        $newState = $traversalState;
        $newState['current_node_id'] = $matchedNodeId;
        $newState['path_taken'][] = $matchedNodeId;

        if ($pathType === 'repeat') {
            $count = $newState['repetition_state'][$matchedNodeId]['count'] ?? 0;
            $newState['repetition_state'][$matchedNodeId] = ['count' => $count + 1];
        }

        return $newState;
    }

    /**
     * Check if pattern complete (delegates to L5Layer logic)
     *
     * @param  array  $graph  Pattern graph
     * @param  array  $traversalState  Traversal state
     * @return bool True if complete
     */
    private function isPatternComplete(array $graph, array $traversalState): bool
    {
        $currentNodeId = $traversalState['current_node_id'] ?? null;
        $edges = $graph['edges'] ?? [];
        $nodes = $graph['nodes'] ?? [];

        if (! $currentNodeId) {
            return false;
        }

        foreach ($edges as $edge) {
            if ($edge['from'] === $currentNodeId) {
                $targetNode = $nodes[$edge['to']] ?? null;
                if ($targetNode && ($targetNode['type'] ?? '') === 'END') {
                    return true;
                }
            }
        }

        return false;
    }

    // NOTE: linkL23ToConstruction() method removed.
    // Cross-column L23 → L5 connections violate CLN architecture.
    // Within-column L23 → L5 connections are handled by L5Layer::linkL23ToConstruction()
    // when partial constructions are first created.
}

<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;
use SplPriorityQueue;

/**
 * Parse State V4 Data Transfer Object
 *
 * Maintains the current state of incremental parsing:
 * - Current position in the sentence
 * - Active alternatives (construction hypotheses being evaluated)
 * - Confirmed nodes (completed parse nodes with CE labels)
 * - Confirmed edges (dependency links between nodes)
 * - Aggregated MWEs (multi-word expressions combined into single nodes)
 * - Consumed positions (positions taken by aggregated MWEs)
 *
 * The parse state is incrementally updated as each token is processed.
 */
class ParseStateV4 extends Data
{
    public function __construct(
        public int $currentPosition,
        public SplPriorityQueue $activeAlternatives,
        public array $confirmedNodes,
        public array $confirmedEdges,
        public array $aggregatedMWEs,
        public array $consumedPositions,
        public array $stateHistory = [],
    ) {}

    public static function rules(): array
    {
        return [
            'currentPosition' => ['required', 'integer', 'min:0'],
            'confirmedNodes' => ['required', 'array'],
            'confirmedEdges' => ['required', 'array'],
            'aggregatedMWEs' => ['required', 'array'],
            'consumedPositions' => ['required', 'array'],
            'stateHistory' => ['sometimes', 'array'],
        ];
    }

    /**
     * Create a new initial parse state
     */
    public static function create(): self
    {
        return new self(
            currentPosition: 0,
            activeAlternatives: new SplPriorityQueue,
            confirmedNodes: [],
            confirmedEdges: [],
            aggregatedMWEs: [],
            consumedPositions: [],
            stateHistory: [],
        );
    }

    /**
     * Add a new alternative to the active alternatives queue
     */
    public function addAlternative(AlternativeState $alt): void
    {
        $this->activeAlternatives->insert($alt, $alt->priority);
    }

    /**
     * Confirm a node (add to confirmed nodes)
     */
    public function confirmNode(array $node): void
    {
        $this->confirmedNodes[] = $node;
    }

    /**
     * Confirm an edge (add to confirmed edges)
     */
    public function confirmEdge(array $edge): void
    {
        $this->confirmedEdges[] = $edge;
    }

    /**
     * Mark a position as consumed (by MWE aggregation)
     */
    public function consumePosition(int $position): void
    {
        if (! in_array($position, $this->consumedPositions)) {
            $this->consumedPositions[] = $position;
        }
    }

    /**
     * Check if a position is consumed
     */
    public function isPositionConsumed(int $position): bool
    {
        return in_array($position, $this->consumedPositions);
    }

    /**
     * Aggregate an MWE (combine components into single node)
     *
     * @param  AlternativeState  $mweAlt  The MWE alternative to aggregate
     * @return array The aggregated node
     */
    public function aggregateMWE(AlternativeState $mweAlt): array
    {
        // Create aggregated node
        $aggregatedNode = [
            'type' => 'mwe',
            'constructionName' => $mweAlt->constructionName,
            'startPosition' => $mweAlt->startPosition,
            'endPosition' => $mweAlt->currentPosition,
            'components' => $mweAlt->matchedComponents,
            'features' => $mweAlt->features,
        ];

        // Mark all component positions as consumed
        for ($i = $mweAlt->startPosition; $i <= $mweAlt->currentPosition; $i++) {
            $this->consumePosition($i);
        }

        // Add to aggregated MWEs
        $this->aggregatedMWEs[] = $aggregatedNode;

        return $aggregatedNode;
    }

    /**
     * Get all active alternatives as an array (for iteration)
     */
    public function getActiveAlternativesArray(): array
    {
        $alternatives = [];
        $queue = clone $this->activeAlternatives;

        while (! $queue->isEmpty()) {
            $alternatives[] = $queue->extract();
        }

        return $alternatives;
    }

    /**
     * Count active alternatives
     */
    public function countActiveAlternatives(): int
    {
        return $this->activeAlternatives->count();
    }

    /**
     * Get confirmed nodes at a specific position
     */
    public function getNodesAtPosition(int $position): array
    {
        return array_values(array_filter($this->confirmedNodes, function ($node) use ($position) {
            // Check if node has a single position field
            if (isset($node['position'])) {
                return $node['position'] === $position;
            }

            // Check if node has a range (startPosition, endPosition)
            if (isset($node['startPosition']) && isset($node['endPosition'])) {
                return $node['startPosition'] <= $position && $node['endPosition'] >= $position;
            }

            return false;
        }));
    }

    /**
     * Save current state to history (for potential backtracking)
     */
    public function saveToHistory(): void
    {
        $this->stateHistory[] = [
            'position' => $this->currentPosition,
            'confirmedNodesCount' => count($this->confirmedNodes),
            'confirmedEdgesCount' => count($this->confirmedEdges),
            'activeAlternativesCount' => $this->countActiveAlternatives(),
        ];
    }

    /**
     * Get statistics about the current parse state
     */
    public function getStatistics(): array
    {
        return [
            'currentPosition' => $this->currentPosition,
            'activeAlternatives' => $this->countActiveAlternatives(),
            'confirmedNodes' => count($this->confirmedNodes),
            'confirmedEdges' => count($this->confirmedEdges),
            'aggregatedMWEs' => count($this->aggregatedMWEs),
            'consumedPositions' => count($this->consumedPositions),
        ];
    }
}

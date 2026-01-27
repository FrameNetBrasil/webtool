<?php

namespace App\Models\CLN_RNT;

/**
 * PC Parse Result
 *
 * Encapsulates the complete result of parsing a token sequence using
 * the Predictive Coding framework.
 *
 * Contains:
 * - Original input sequence
 * - Parsed tokens
 * - Complete parser graph (nodes and edges)
 * - Completed constructions
 * - Parsing statistics
 */
class PCParseResult
{
    /**
     * Original input sequence
     */
    public string $sequence;

    /**
     * Parsed tokens from the sequence
     * Format: [['word' => string, 'pos' => string, 'position' => int], ...]
     */
    public array $tokens;

    /**
     * All parser graph nodes
     *
     * @var PCParserGraphNode[]
     */
    public array $nodes;

    /**
     * All parser graph edges
     *
     * @var PCParserGraphEdge[]
     */
    public array $edges;

    /**
     * Completed constructions
     *
     * @var PCParserGraphNode[]
     */
    public array $constructions;

    /**
     * Parsing statistics
     */
    public array $statistics;

    /**
     * Create a new parse result
     *
     * @param  array  $data  Result data
     */
    public function __construct(array $data)
    {
        $this->sequence = $data['sequence'];
        $this->tokens = $data['tokens'] ?? [];
        $this->nodes = $data['nodes'] ?? [];
        $this->edges = $data['edges'] ?? [];
        $this->constructions = $data['constructions'] ?? [];
        $this->statistics = $data['statistics'] ?? [];
    }

    /**
     * Get completed constructions
     *
     * @return PCParserGraphNode[]
     */
    public function getConstructions(): array
    {
        return $this->constructions;
    }

    /**
     * Get the complete parser graph
     *
     * @return array ['nodes' => array, 'edges' => array]
     */
    public function getGraph(): array
    {
        return [
            'nodes' => array_map(fn ($node) => $node->toArray(), $this->nodes),
            'edges' => array_map(fn ($edge) => $edge->toArray(), $this->edges),
        ];
    }

    /**
     * Get active nodes (non-waiting)
     *
     * @return PCParserGraphNode[]
     */
    public function getActiveNodes(): array
    {
        return array_filter($this->nodes, fn ($node) => $node->isActive() || $node->isCompleted());
    }

    /**
     * Get waiting nodes
     *
     * @return PCParserGraphNode[]
     */
    public function getWaitingNodes(): array
    {
        return array_filter($this->nodes, fn ($node) => $node->isWaiting());
    }

    /**
     * Get nodes by position
     *
     * @return PCParserGraphNode[]
     */
    public function getNodesByPosition(int $position): array
    {
        return array_filter($this->nodes, fn ($node) => $node->position === $position);
    }

    /**
     * Check if any constructions were completed
     */
    public function hasCompletedConstructions(): bool
    {
        return count($this->constructions) > 0;
    }

    /**
     * Convert result to array
     */
    public function toArray(): array
    {
        return [
            'sequence' => $this->sequence,
            'tokens' => $this->tokens,
            'nodes' => array_map(fn ($node) => $node->toArray(), $this->nodes),
            'edges' => array_map(fn ($edge) => $edge->toArray(), $this->edges),
            'constructions' => array_map(fn ($node) => $node->toArray(), $this->constructions),
            'statistics' => $this->statistics,
        ];
    }
}

<?php

namespace App\Models\SeqGraph;

/**
 * Container for a unified sequence graph combining all patterns.
 *
 * A unified graph merges multiple pattern graphs into a single structure
 * with one global START node, pattern completion nodes (PATTERN), and
 * explicit cross-pattern edges from PATTERN nodes to CONSTRUCTION_REF
 * listeners in other patterns.
 */
class UnifiedSequenceGraph
{
    /**
     * ID of the global start node.
     */
    public string $globalStartId = 'GLOBAL:START';

    /**
     * All nodes in the unified graph, indexed by namespaced node ID.
     *
     * Node IDs are namespaced as {PATTERN}:{nodeId} (e.g., "REF:det", "CLAUSE:subj").
     *
     * @var array<string, SeqNode>
     */
    public array $nodes = [];

    /**
     * All edges in the unified graph, including cross-pattern links.
     *
     * @var array<SeqEdge>
     */
    public array $edges = [];

    /**
     * Mapping of pattern names to their PATTERN node IDs.
     *
     * Format: ['REF' => 'PATTERN:REF', 'CLAUSE' => 'PATTERN:CLAUSE']
     *
     * @var array<string, string>
     */
    public array $patternNodeIds = [];

    /**
     * Mapping of pattern names to their entry node IDs.
     *
     * Entry nodes are the first element nodes after each pattern's START node.
     * Format: ['REF' => ['REF:det'], 'CLAUSE' => ['CLAUSE:subj']]
     *
     * @var array<string, array<string>>
     */
    public array $patternEntryNodes = [];

    /**
     * Create a new unified sequence graph.
     *
     * @param  array<string, SeqNode>  $nodes  Nodes indexed by namespaced ID
     * @param  array<SeqEdge>  $edges  All edges including cross-pattern links
     * @param  array<string, string>  $patternNodeIds  Pattern name to PATTERN node ID mapping
     * @param  array<string, array<string>>  $patternEntryNodes  Pattern name to entry node IDs mapping
     */
    public function __construct(
        array $nodes = [],
        array $edges = [],
        array $patternNodeIds = [],
        array $patternEntryNodes = []
    ) {
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->patternNodeIds = $patternNodeIds;
        $this->patternEntryNodes = $patternEntryNodes;
    }

    /**
     * Get a node by ID.
     *
     * @param  string  $id  Node ID
     * @return SeqNode|null The node, or null if not found
     */
    public function getNode(string $id): ?SeqNode
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * Get all successor nodes of a given node.
     *
     * @param  string  $nodeId  Source node ID
     * @return array<SeqNode> Array of successor nodes
     */
    public function getSuccessors(string $nodeId): array
    {
        $successors = [];

        foreach ($this->edges as $edge) {
            if ($edge->from === $nodeId) {
                $successor = $this->getNode($edge->to);
                if ($successor !== null) {
                    $successors[] = $successor;
                }
            }
        }

        return $successors;
    }

    /**
     * Get all element nodes in the graph.
     *
     * @return array<SeqNode> Array of element nodes
     */
    public function getElementNodes(): array
    {
        return array_filter($this->nodes, fn (SeqNode $node) => $node->isElement());
    }

    /**
     * Get all currently active element nodes (listeners).
     *
     * @return array<SeqNode> Array of active element nodes
     */
    public function getActiveListeners(): array
    {
        return array_filter(
            $this->nodes,
            fn (SeqNode $node) => $node->isElement() && $node->active
        );
    }

    /**
     * Get all nodes belonging to a specific pattern.
     *
     * @param  string  $patternName  Pattern name
     * @return array<SeqNode> Array of nodes from the pattern
     */
    public function getNodesByPattern(string $patternName): array
    {
        return array_filter(
            $this->nodes,
            fn (SeqNode $node) => $node->patternName === $patternName
        );
    }

    /**
     * Get all unique pattern names in this unified graph.
     *
     * @return array<string> Array of pattern names
     */
    public function getPatternNames(): array
    {
        return array_keys($this->patternNodeIds);
    }

    /**
     * Reset all nodes in the graph to initial state.
     */
    public function reset(): void
    {
        foreach ($this->nodes as $node) {
            $node->reset();
        }
    }
}

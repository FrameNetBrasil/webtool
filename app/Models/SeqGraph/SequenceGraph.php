<?php

namespace App\Models\SeqGraph;

/**
 * Container for a complete sequence graph representing a pattern.
 *
 * A sequence graph transforms a pattern graph into an activation-based
 * representation where nodes can be active (listening) and fire when
 * input elements arrive. The graph tracks start/end nodes and provides
 * methods for querying structure and state.
 */
class SequenceGraph
{
    /**
     * Name of the pattern this graph represents.
     */
    public string $patternName;

    /**
     * All nodes in the graph, indexed by node ID.
     *
     * @var array<string, SeqNode>
     */
    public array $nodes;

    /**
     * All edges in the graph.
     *
     * @var array<SeqEdge>
     */
    public array $edges;

    /**
     * ID of the start node.
     */
    public string $startId;

    /**
     * ID of the end node.
     */
    public string $endId;

    /**
     * Create a new sequence graph.
     *
     * @param  string  $patternName  Pattern name
     * @param  array<string, SeqNode>  $nodes  Nodes indexed by ID
     * @param  array<SeqEdge>  $edges  All edges
     * @param  string  $startId  Start node ID
     * @param  string  $endId  End node ID
     */
    public function __construct(
        string $patternName,
        array $nodes,
        array $edges,
        string $startId,
        string $endId
    ) {
        $this->patternName = $patternName;
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->startId = $startId;
        $this->endId = $endId;
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
     * Returns nodes that are directly reachable via outgoing edges.
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
     * Returns only nodes that represent input elements, excluding
     * routing nodes like start and end.
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
     * These nodes are waiting for input elements to arrive.
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
     * Reset all nodes in the graph to initial state.
     *
     * Clears all timestamps and deactivates all nodes.
     */
    public function reset(): void
    {
        foreach ($this->nodes as $node) {
            $node->reset();
        }
    }
}

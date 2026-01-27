<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\PCParserGraphEdge;
use App\Models\CLN_RNT\PCParserGraphNode;

/**
 * PC Graph Builder
 *
 * Manages the construction of the parser graph during Predictive Coding parsing.
 * Handles node and edge creation, deduplication, and waiting node registry.
 *
 * Key responsibilities:
 * - Create and track token nodes
 * - Create and track construction nodes
 * - Create and track edges
 * - Maintain global waiting nodes registry (NOT position-specific)
 * - Generate statistics
 */
class PCGraphBuilder
{
    /**
     * All nodes in the parser graph
     *
     * @var array<string, PCParserGraphNode>
     */
    private array $nodes = [];

    /**
     * All edges in the parser graph
     *
     * @var PCParserGraphEdge[]
     */
    private array $edges = [];

    /**
     * Global waiting nodes registry
     * Indexed by: type:value (e.g., "construction:HEAD")
     * NOT position-specific!
     *
     * @var array<string, PCParserGraphNode>
     */
    private array $waitingNodes = [];

    /**
     * Statistics
     */
    private array $stats = [
        'nodes_created' => 0,
        'token_nodes' => 0,
        'construction_nodes' => 0,
        'edges_created' => 0,
        'waiting_nodes_created' => 0,
        'waiting_nodes_activated' => 0,
    ];

    /**
     * Edge counter for unique IDs
     */
    private int $edgeCounter = 0;

    /**
     * Find or create a token node
     *
     * @param  int  $position  Token position in sequence
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     * @param  string  $status  Node status (default: 'active')
     */
    public function findOrCreateTokenNode(
        int $position,
        string $word,
        string $pos,
        string $status = 'active'
    ): PCParserGraphNode {
        $id = "p{$position}_{$pos}";

        // Check if node already exists
        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        }

        // Create new token node
        $node = new PCParserGraphNode([
            'id' => $id,
            'position' => $position,
            'status' => $status,
            'nodeType' => 'token',
            'value' => "{$pos}/{$word}",
            'createdFrom' => 'input',
            'metadata' => ['word' => $word, 'pos' => $pos],
        ]);

        $this->nodes[$id] = $node;
        $this->stats['nodes_created']++;
        $this->stats['token_nodes']++;

        return $node;
    }

    /**
     * Find or create a construction node
     *
     * @param  int  $position  Position where construction was recognized
     * @param  string  $constructionName  Construction name
     * @param  string  $status  Node status (default: 'active')
     */
    public function findOrCreateConstructionNode(
        int $position,
        string $constructionName,
        string $status = 'active'
    ): PCParserGraphNode {
        $id = "p{$position}_cxn_".strtolower($constructionName);

        // Check if node already exists
        if (isset($this->nodes[$id])) {
            return $this->nodes[$id];
        }

        // Create new construction node
        $node = new PCParserGraphNode([
            'id' => $id,
            'position' => $position,
            'status' => $status,
            'nodeType' => 'construction',
            'value' => $constructionName,
            'createdFrom' => $status === 'waiting' ? null : 'end_node',
        ]);

        $this->nodes[$id] = $node;
        $this->stats['nodes_created']++;
        $this->stats['construction_nodes']++;

        if ($status === 'waiting') {
            $this->stats['waiting_nodes_created']++;
        }

        return $node;
    }

    /**
     * Create an edge between two nodes
     *
     * @param  PCParserGraphNode  $from  Source node
     * @param  PCParserGraphNode  $to  Target node
     * @param  string  $label  Edge label (from pattern graph)
     * @param  string  $edgeType  Edge type: 'match', 'prediction', 'completion'
     * @param  int|null  $patternId  Pattern ID this edge belongs to
     */
    public function createEdge(
        PCParserGraphNode $from,
        PCParserGraphNode $to,
        string $label,
        string $edgeType,
        ?int $patternId = null
    ): PCParserGraphEdge {
        // Check if identical edge already exists
        $existingEdge = $this->findEdge($from->id, $to->id, $label, $edgeType);
        if ($existingEdge) {
            return $existingEdge;
        }

        $this->edgeCounter++;

        // Set status for prediction edges
        $status = ($edgeType === 'prediction') ? 'expected' : null;

        $edge = new PCParserGraphEdge([
            'id' => "e{$this->edgeCounter}",
            'fromNodeId' => $from->id,
            'toNodeId' => $to->id,
            'label' => $label,
            'edgeType' => $edgeType,
            'status' => $status,
            'patternId' => $patternId,
        ]);

        $this->edges[] = $edge;
        $this->stats['edges_created']++;

        return $edge;
    }

    /**
     * Find an existing edge with matching attributes
     *
     * @param  string  $fromNodeId  Source node ID
     * @param  string  $toNodeId  Target node ID
     * @param  string  $label  Edge label
     * @param  string  $edgeType  Edge type
     */
    private function findEdge(
        string $fromNodeId,
        string $toNodeId,
        string $label,
        string $edgeType
    ): ?PCParserGraphEdge {
        foreach ($this->edges as $edge) {
            if ($edge->fromNodeId === $fromNodeId &&
                $edge->toNodeId === $toNodeId &&
                $edge->label === $label &&
                $edge->edgeType === $edgeType) {
                return $edge;
            }
        }

        return null;
    }

    /**
     * Find a waiting node by type and value (globally, not position-specific)
     *
     * @param  string  $nodeType  Node type ('token' or 'construction')
     * @param  string  $value  Node value to match
     */
    public function findWaitingNode(string $nodeType, string $value): ?PCParserGraphNode
    {
        $key = "{$nodeType}:{$value}";

        return $this->waitingNodes[$key] ?? null;
    }

    /**
     * Find an active/completed non-confirmed node by type and value
     * (Can reuse these nodes before they become confirmed)
     *
     * NEW TEMPORAL LOCALITY RULE:
     * A node can only be reused at position C if:
     * - It's at position C - 1 (immediate previous), OR
     * - It's being used at position C - 1 (continuous chain of reuse)
     *
     * @param  string  $nodeType  Node type ('token' or 'construction')
     * @param  string  $value  Node value to match
     * @param  int  $currentPosition  Current position where we want to reuse the node
     */
    public function findActiveNonConfirmedNode(string $nodeType, string $value, int $currentPosition): ?PCParserGraphNode
    {
        foreach ($this->nodes as $node) {
            // Match type and value
            if ($node->nodeType !== $nodeType || $node->value !== $value) {
                continue;
            }

            // Must NOT be waiting (active or completed only)
            if ($node->isWaiting()) {
                continue;
            }

            // Must NOT be confirmed
            if ($node->isConfirmed()) {
                continue;
            }

            // TEMPORAL LOCALITY CHECK:
            // Node must be at position (current - 1) OR being used at position (current - 1)
            $previousPosition = $currentPosition - 1;

            // Check if node is at immediate previous position
            $isAtPreviousPosition = ($node->position === $previousPosition);

            // Check if node is being used at immediate previous position (chain)
            $isUsedAtPreviousPosition = $node->isUsedAtPosition($previousPosition);

            // Node can be reused if either condition is true
            if (! $isAtPreviousPosition && ! $isUsedAtPreviousPosition) {
                // Skip this node - temporal gap exists
                continue;
            }

            // Found a match that satisfies all constraints!
            return $node;
        }

        return null;
    }

    /**
     * Register a waiting node in the global registry
     */
    public function registerWaitingNode(PCParserGraphNode $node): void
    {
        if (! $node->isWaiting()) {
            return;
        }

        $key = "{$node->nodeType}:{$node->value}";
        $this->waitingNodes[$key] = $node;
    }

    /**
     * Unregister a waiting node (when it gets activated)
     */
    public function unregisterWaitingNode(PCParserGraphNode $node): void
    {
        $key = "{$node->nodeType}:{$node->value}";
        if (isset($this->waitingNodes[$key])) {
            unset($this->waitingNodes[$key]);
            $this->stats['waiting_nodes_activated']++;
        }
    }

    /**
     * Get all waiting nodes
     *
     * @return PCParserGraphNode[]
     */
    public function getAllWaitingNodes(): array
    {
        return array_values($this->waitingNodes);
    }

    /**
     * Confirm all edges (prediction and completion) pointing to a node
     * (Changes status from 'expected' to 'confirmed' and marks source nodes as confirmed)
     * RECURSIVE: When a source node is confirmed, recursively confirm edges pointing to it
     */
    public function confirmEdgesToNode(PCParserGraphNode $node): void
    {
        foreach ($this->edges as $edge) {
            // Confirm both prediction AND completion edges
            // Prediction edges: validate expected predictions
            // Completion edges: confirm nodes that completed this construction
            if ($edge->toNodeId === $node->id && ($edge->isPrediction() || $edge->isCompletion())) {
                // Confirm the edge (only has effect on prediction edges with status)
                if ($edge->isPrediction()) {
                    $edge->confirm();
                }

                // Mark the source node as confirmed (its prediction/completion was validated)
                $sourceNode = $this->getNode($edge->fromNodeId);
                if ($sourceNode) {
                    // Only confirm and recurse if not already confirmed (prevents infinite loops)
                    if (! $sourceNode->isConfirmed()) {
                        $sourceNode->confirm();

                        // RECURSIVE: Confirm edges pointing to this source node
                        // This creates a chain of confirmations flowing backwards through the graph
                        $this->confirmEdgesToNode($sourceNode);
                    }
                }
            }
        }
    }

    /**
     * Get a node by ID
     */
    public function getNode(string $id): ?PCParserGraphNode
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * Get all nodes
     *
     * @return PCParserGraphNode[]
     */
    public function getAllNodes(): array
    {
        return array_values($this->nodes);
    }

    /**
     * Get all edges
     *
     * @return PCParserGraphEdge[]
     */
    public function getAllEdges(): array
    {
        return $this->edges;
    }

    /**
     * Get the complete graph structure
     *
     * @return array ['nodes' => PCParserGraphNode[], 'edges' => PCParserGraphEdge[]]
     */
    public function getGraph(): array
    {
        return [
            'nodes' => $this->getAllNodes(),
            'edges' => $this->getAllEdges(),
        ];
    }

    /**
     * Create a waiting node
     *
     * @param  int  $position  Position hint (for visualization)
     * @param  string  $nodeType  'token' or 'construction'
     * @param  string  $value  Node value
     * @param  array  $metadata  Additional metadata
     */
    public function createWaitingNode(
        int $position,
        string $nodeType,
        string $value,
        array $metadata = []
    ): PCParserGraphNode {
        $id = "wait_{$nodeType}_".strtolower(str_replace(' ', '_', $value));

        // Create waiting node
        $node = new PCParserGraphNode([
            'id' => $id,
            'position' => $position,
            'status' => 'waiting',
            'nodeType' => $nodeType,
            'value' => $value,
            'metadata' => $metadata,
        ]);

        $this->nodes[$id] = $node;
        $this->stats['nodes_created']++;
        $this->stats['waiting_nodes_created']++;

        return $node;
    }

    /**
     * Get parsing statistics
     */
    public function getStatistics(): array
    {
        return array_merge($this->stats, [
            'total_nodes' => count($this->nodes),
            'total_edges' => count($this->edges),
            'waiting_nodes_remaining' => count($this->waitingNodes),
        ]);
    }
}

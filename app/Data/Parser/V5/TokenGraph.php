<?php

namespace App\Data\Parser\V5;

/**
 * Token Graph
 *
 * Runtime graph representation of the parse state in Parser V5.
 * Maintains both real nodes (from input tokens) and ghost nodes (null instantiation).
 *
 * Key Features:
 * - Unified node storage (real + ghost)
 * - Position-based indexing for fast lookup
 * - Edge tracking with ghost support
 * - Node merging for ghost fulfillment
 * - Edge re-linking after merges
 *
 * Structure:
 * - Nodes: Array of real and ghost nodes
 * - Edges: Array of dependency edges
 * - Indexes: Fast lookup by position, ID, state
 */
class TokenGraph
{
    /**
     * All nodes (real + ghost) indexed by node ID
     *
     * @var array<int, array>
     */
    private array $nodes = [];

    /**
     * All edges indexed by edge ID
     *
     * @var array<int, array>
     */
    private array $edges = [];

    /**
     * Position index: position => [node IDs]
     *
     * @var array<int, array<int>>
     */
    private array $positionIndex = [];

    /**
     * Ghost nodes index: ghost ID => node data
     *
     * @var array<int, array>
     */
    private array $ghostNodes = [];

    /**
     * Real nodes index: real ID => node data
     *
     * @var array<int, array>
     */
    private array $realNodes = [];

    /**
     * Edge counter for generating edge IDs
     */
    private int $edgeIdCounter = 1;

    /**
     * Add a real node to the graph
     */
    public function addRealNode(array $node): void
    {
        $nodeId = $node['idNode'] ?? $node['id'];
        $position = $node['positionInSentence'] ?? $node['position'];

        // Store in main nodes array
        $this->nodes[$nodeId] = array_merge($node, ['isGhost' => false]);

        // Store in real nodes index
        $this->realNodes[$nodeId] = $this->nodes[$nodeId];

        // Update position index
        if (! isset($this->positionIndex[$position])) {
            $this->positionIndex[$position] = [];
        }
        $this->positionIndex[$position][] = $nodeId;
    }

    /**
     * Add a ghost node to the graph
     */
    public function addGhostNode(GhostNode $ghost): void
    {
        $ghostData = array_merge($ghost->toArray(), [
            'isGhost' => true,
            'positionInSentence' => $ghost->createdAtPosition,
        ]);

        // Store in main nodes array
        $this->nodes[$ghost->id] = $ghostData;

        // Store in ghost nodes index
        $this->ghostNodes[$ghost->id] = $ghostData;

        // Update position index
        $position = $ghost->createdAtPosition;
        if (! isset($this->positionIndex[$position])) {
            $this->positionIndex[$position] = [];
        }
        $this->positionIndex[$position][] = $ghost->id;
    }

    /**
     * Add an edge to the graph
     */
    public function addEdge(array $edge): int
    {
        $edgeId = $this->edgeIdCounter++;

        $this->edges[$edgeId] = array_merge($edge, [
            'idEdge' => $edgeId,
        ]);

        return $edgeId;
    }

    /**
     * Merge ghost node with real node (ghost fulfillment)
     */
    public function mergeNodes(int $ghostId, int $realNodeId): array
    {
        if (! isset($this->ghostNodes[$ghostId])) {
            throw new \InvalidArgumentException("Ghost node {$ghostId} not found");
        }

        if (! isset($this->realNodes[$realNodeId])) {
            throw new \InvalidArgumentException("Real node {$realNodeId} not found");
        }

        $ghost = $this->ghostNodes[$ghostId];
        $real = $this->realNodes[$realNodeId];

        // Merge properties (real takes precedence)
        $merged = array_merge($ghost, $real, [
            'wasGhost' => true,
            'ghostId' => $ghostId,
            'fulfilledBy' => $realNodeId,
            'isFulfilled' => true,
        ]);

        // Update real node with merged properties
        $this->nodes[$realNodeId] = $merged;
        $this->realNodes[$realNodeId] = $merged;

        // Mark ghost as fulfilled (keep for history)
        $this->nodes[$ghostId]['isFulfilled'] = true;
        $this->nodes[$ghostId]['fulfilledBy'] = $realNodeId;
        $this->ghostNodes[$ghostId]['isFulfilled'] = true;
        $this->ghostNodes[$ghostId]['fulfilledBy'] = $realNodeId;

        return $merged;
    }

    /**
     * Re-link edges from ghost to real node
     */
    public function relinkEdges(int $fromNodeId, int $toNodeId): array
    {
        $relinkedEdges = [];

        foreach ($this->edges as $edgeId => $edge) {
            $modified = false;

            // Update source
            if ($edge['sourceNode'] === $fromNodeId) {
                $this->edges[$edgeId]['sourceNode'] = $toNodeId;
                $modified = true;
            }

            // Update target
            if ($edge['targetNode'] === $fromNodeId) {
                $this->edges[$edgeId]['targetNode'] = $toNodeId;
                $modified = true;
            }

            if ($modified) {
                $relinkedEdges[] = $edgeId;
            }
        }

        return $relinkedEdges;
    }

    /**
     * Get node by ID
     */
    public function getNode(int $nodeId): ?array
    {
        return $this->nodes[$nodeId] ?? null;
    }

    /**
     * Get edge by ID
     */
    public function getEdge(int $edgeId): ?array
    {
        return $this->edges[$edgeId] ?? null;
    }

    /**
     * Get all nodes at a position
     */
    public function getNodesAtPosition(int $position): array
    {
        $nodeIds = $this->positionIndex[$position] ?? [];

        return array_filter(
            array_map(fn ($id) => $this->nodes[$id] ?? null, $nodeIds),
            fn ($node) => $node !== null
        );
    }

    /**
     * Get all nodes
     */
    public function getAllNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get all real nodes
     */
    public function getRealNodes(): array
    {
        return $this->realNodes;
    }

    /**
     * Get all ghost nodes
     */
    public function getGhostNodes(): array
    {
        return $this->ghostNodes;
    }

    /**
     * Get all edges
     */
    public function getAllEdges(): array
    {
        return $this->edges;
    }

    /**
     * Get unfulfilled ghost nodes
     */
    public function getUnfulfilledGhosts(): array
    {
        return array_filter(
            $this->ghostNodes,
            fn ($ghost) => ! ($ghost['isFulfilled'] ?? false)
        );
    }

    /**
     * Get fulfilled ghost nodes
     */
    public function getFulfilledGhosts(): array
    {
        return array_filter(
            $this->ghostNodes,
            fn ($ghost) => $ghost['isFulfilled'] ?? false
        );
    }

    /**
     * Get incoming edges for a node
     */
    public function getIncomingEdges(int $nodeId): array
    {
        return array_filter(
            $this->edges,
            fn ($edge) => $edge['targetNode'] === $nodeId
        );
    }

    /**
     * Get outgoing edges for a node
     */
    public function getOutgoingEdges(int $nodeId): array
    {
        return array_filter(
            $this->edges,
            fn ($edge) => $edge['sourceNode'] === $nodeId
        );
    }

    /**
     * Remove an edge
     */
    public function removeEdge(int $edgeId): bool
    {
        if (isset($this->edges[$edgeId])) {
            unset($this->edges[$edgeId]);

            return true;
        }

        return false;
    }

    /**
     * Remove a node (and its edges)
     */
    public function removeNode(int $nodeId): bool
    {
        if (! isset($this->nodes[$nodeId])) {
            return false;
        }

        $node = $this->nodes[$nodeId];

        // Remove from main nodes
        unset($this->nodes[$nodeId]);

        // Remove from indexes
        if ($node['isGhost'] ?? false) {
            unset($this->ghostNodes[$nodeId]);
        } else {
            unset($this->realNodes[$nodeId]);
        }

        // Remove from position index
        $position = $node['positionInSentence'] ?? $node['position'] ?? null;
        if ($position !== null && isset($this->positionIndex[$position])) {
            $this->positionIndex[$position] = array_filter(
                $this->positionIndex[$position],
                fn ($id) => $id !== $nodeId
            );
        }

        // Remove all edges connected to this node
        $edgesToRemove = [];
        foreach ($this->edges as $edgeId => $edge) {
            if ($edge['sourceNode'] === $nodeId || $edge['targetNode'] === $nodeId) {
                $edgesToRemove[] = $edgeId;
            }
        }

        foreach ($edgesToRemove as $edgeId) {
            $this->removeEdge($edgeId);
        }

        return true;
    }

    /**
     * Check if node exists
     */
    public function hasNode(int $nodeId): bool
    {
        return isset($this->nodes[$nodeId]);
    }

    /**
     * Check if edge exists
     */
    public function hasEdge(int $edgeId): bool
    {
        return isset($this->edges[$edgeId]);
    }

    /**
     * Get node count
     */
    public function getNodeCount(): int
    {
        return count($this->nodes);
    }

    /**
     * Get edge count
     */
    public function getEdgeCount(): int
    {
        return count($this->edges);
    }

    /**
     * Get ghost count
     */
    public function getGhostCount(): int
    {
        return count($this->ghostNodes);
    }

    /**
     * Get real node count
     */
    public function getRealNodeCount(): int
    {
        return count($this->realNodes);
    }

    /**
     * Clear the graph
     */
    public function clear(): void
    {
        $this->nodes = [];
        $this->edges = [];
        $this->positionIndex = [];
        $this->ghostNodes = [];
        $this->realNodes = [];
        $this->edgeIdCounter = 1;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_values($this->nodes),
            'edges' => array_values($this->edges),
            'realNodeCount' => count($this->realNodes),
            'ghostNodeCount' => count($this->ghostNodes),
            'edgeCount' => count($this->edges),
            'unfulfilledGhosts' => count($this->getUnfulfilledGhosts()),
        ];
    }

    /**
     * Load from array
     */
    public function loadFromArray(array $data): void
    {
        $this->clear();

        // Load nodes
        foreach ($data['nodes'] ?? [] as $node) {
            if ($node['isGhost'] ?? false) {
                $ghost = GhostNode::fromArray($node);
                $this->addGhostNode($ghost);
            } else {
                $this->addRealNode($node);
            }
        }

        // Load edges
        foreach ($data['edges'] ?? [] as $edge) {
            $this->edges[$edge['idEdge']] = $edge;
            if ($edge['idEdge'] >= $this->edgeIdCounter) {
                $this->edgeIdCounter = $edge['idEdge'] + 1;
            }
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'totalNodes' => count($this->nodes),
            'realNodes' => count($this->realNodes),
            'ghostNodes' => count($this->ghostNodes),
            'fulfilledGhosts' => count($this->getFulfilledGhosts()),
            'unfulfilledGhosts' => count($this->getUnfulfilledGhosts()),
            'totalEdges' => count($this->edges),
            'positions' => count($this->positionIndex),
            'avgNodesPerPosition' => count($this->positionIndex) > 0
                ? count($this->nodes) / count($this->positionIndex)
                : 0,
        ];
    }

    /**
     * Find nodes by property
     */
    public function findNodes(callable $predicate): array
    {
        return array_filter($this->nodes, $predicate);
    }

    /**
     * Find edges by property
     */
    public function findEdges(callable $predicate): array
    {
        return array_filter($this->edges, $predicate);
    }
}

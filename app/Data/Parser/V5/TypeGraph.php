<?php

namespace App\Data\Parser\V5;

use Spatie\LaravelData\Data;

/**
 * Type Graph - Unified Construction Ontology
 *
 * Represents all construction relationships, CE dependencies,
 * and mandatory element requirements in a single graph structure.
 *
 * The Type Graph serves as a compile-time ontology that guides
 * the runtime Token Graph construction during parsing.
 */
class TypeGraph extends Data
{
    public function __construct(
        /** Grammar graph ID this Type Graph belongs to */
        public int $idGrammarGraph,

        /** All nodes (constructions + CE labels) */
        public array $nodes,

        /** All edges (relationships) */
        public array $edges,

        /** Map: constructionId => [CE labels] that are mandatory */
        public array $mandatoryElements,

        /** CE dependency chains (e.g., ['Head' => ['Mod', 'Adp']]) */
        public array $ceHierarchy,

        /** Fast lookup index: construction name => node */
        public array $constructionIndex,

        /** Fast lookup index: CE label => node */
        public array $ceLabelIndex,
    ) {}

    /**
     * Get all constructions that produce a given CE label
     *
     * @param  string  $ceLabel  CE label (e.g., 'Head', 'Mod', 'Pred')
     * @param  string|null  $level  Optional level filter ('phrasal', 'clausal', 'sentential')
     * @return array Array of TypeGraphNode (constructions)
     */
    public function getConstructionsProducing(string $ceLabel, ?string $level = null): array
    {
        $ceNodeId = $level ? "ce_{$level}_{$ceLabel}" : null;
        $constructions = [];

        foreach ($this->edges as $edge) {
            if ($edge->relationshipType !== 'produces') {
                continue;
            }

            // Match by CE label (with or without level filter)
            $matches = $ceNodeId
                ? $edge->toNodeId === $ceNodeId
                : str_ends_with($edge->toNodeId, "_{$ceLabel}");

            if ($matches) {
                $constructionNode = $this->getNodeById($edge->fromNodeId);
                if ($constructionNode && $constructionNode->isConstruction()) {
                    $constructions[] = $constructionNode;
                }
            }
        }

        return $constructions;
    }

    /**
     * Get all CE labels required by a construction
     *
     * @param  int  $constructionId  Construction ID
     * @return array Array of CE label strings
     */
    public function getRequiredCEs(int $constructionId): array
    {
        $constructionNodeId = "construction_{$constructionId}";
        $requiredCEs = [];

        foreach ($this->edges as $edge) {
            if ($edge->fromNodeId !== $constructionNodeId) {
                continue;
            }

            if ($edge->relationshipType === 'requires') {
                $targetNode = $this->getNodeById($edge->toNodeId);
                if ($targetNode && $targetNode->isCELabel()) {
                    $requiredCEs[] = $targetNode->name;
                }
            }
        }

        return $requiredCEs;
    }

    /**
     * Get mandatory elements for a construction
     *
     * @param  int  $constructionId  Construction ID
     * @return array Array of CE label strings that are mandatory
     */
    public function getMandatoryElements(int $constructionId): array
    {
        return $this->mandatoryElements[$constructionId] ?? [];
    }

    /**
     * Check if a CE label is mandatory for a construction
     *
     * @param  int  $constructionId  Construction ID
     * @param  string  $ceLabel  CE label to check
     * @return bool True if mandatory
     */
    public function isMandatory(int $constructionId, string $ceLabel): bool
    {
        $mandatories = $this->getMandatoryElements($constructionId);

        return in_array($ceLabel, $mandatories, true);
    }

    /**
     * Get CE labels that depend on a given CE label
     *
     * Example: Head -> [Mod, Adp] means Mod and Adp can attach to Head
     *
     * @param  string  $ceLabel  CE label
     * @return array Array of dependent CE labels
     */
    public function getCEDependencies(string $ceLabel): array
    {
        return $this->ceHierarchy[$ceLabel] ?? [];
    }

    /**
     * Get node by ID
     *
     * @param  string  $nodeId  Node ID
     */
    public function getNodeById(string $nodeId): ?TypeGraphNode
    {
        foreach ($this->nodes as $node) {
            if ($node->id === $nodeId) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get construction node by name
     *
     * @param  string  $name  Construction name
     */
    public function getConstructionByName(string $name): ?TypeGraphNode
    {
        return $this->constructionIndex[$name] ?? null;
    }

    /**
     * Get CE label node
     *
     * @param  string  $label  CE label
     * @param  string  $level  Level ('phrasal', 'clausal', 'sentential')
     */
    public function getCELabelNode(string $label, string $level): ?TypeGraphNode
    {
        $key = "{$level}_{$label}";

        return $this->ceLabelIndex[$key] ?? null;
    }

    /**
     * Get all constructions of a specific type
     *
     * @param  string  $type  'mwe', 'phrasal', 'clausal', 'sentential'
     * @return array Array of TypeGraphNode
     */
    public function getConstructionsByType(string $type): array
    {
        return array_filter(
            $this->nodes,
            fn ($node) => $node->isConstruction() && $node->constructionType === $type
        );
    }

    /**
     * Get all CE labels at a specific level
     *
     * @param  string  $level  'phrasal', 'clasal', 'sentential'
     * @return array Array of TypeGraphNode
     */
    public function getCELabelsByLevel(string $level): array
    {
        return array_filter(
            $this->nodes,
            fn ($node) => $node->isCELabel() && $node->getCELevel() === $level
        );
    }

    /**
     * Get edges from a node
     *
     * @param  string  $nodeId  Source node ID
     * @param  string|null  $relationshipType  Optional filter by relationship type
     * @return array Array of TypeGraphEdge
     */
    public function getEdgesFrom(string $nodeId, ?string $relationshipType = null): array
    {
        return array_filter(
            $this->edges,
            fn ($edge) => $edge->fromNodeId === $nodeId &&
                        ($relationshipType === null || $edge->relationshipType === $relationshipType)
        );
    }

    /**
     * Get edges to a node
     *
     * @param  string  $nodeId  Target node ID
     * @param  string|null  $relationshipType  Optional filter by relationship type
     * @return array Array of TypeGraphEdge
     */
    public function getEdgesTo(string $nodeId, ?string $relationshipType = null): array
    {
        return array_filter(
            $this->edges,
            fn ($edge) => $edge->toNodeId === $nodeId &&
                        ($relationshipType === null || $edge->relationshipType === $relationshipType)
        );
    }

    /**
     * Export Type Graph to array for storage
     */
    public function toStorageArray(): array
    {
        return [
            'idGrammarGraph' => $this->idGrammarGraph,
            'graphData' => [
                'nodes' => array_map(fn ($n) => $n->toArray(), $this->nodes),
                'edges' => array_map(fn ($e) => $e->toArray(), $this->edges),
            ],
            'nodes' => array_map(fn ($n) => $n->toArray(), $this->nodes),
            'edges' => array_map(fn ($e) => $e->toArray(), $this->edges),
            'mandatoryElements' => $this->mandatoryElements,
            'ceHierarchy' => $this->ceHierarchy,
        ];
    }

    /**
     * Extract a subgraph centered on a specific construction
     *
     * Returns only nodes and edges within a certain depth from the construction
     *
     * @param  int  $constructionId  Construction ID
     * @param  int  $maxDepth  Maximum traversal depth (default: 2)
     * @return array ['nodes' => TypeGraphNode[], 'edges' => TypeGraphEdge[]]
     */
    public function getSubgraphForConstruction(int $constructionId, int $maxDepth = 2): array
    {
        $centerNodeId = "construction_{$constructionId}";
        $centerNode = $this->getNodeById($centerNodeId);

        if (! $centerNode) {
            return ['nodes' => [], 'edges' => []];
        }

        $visitedNodes = [];
        $visitedEdges = [];
        $queue = [['nodeId' => $centerNodeId, 'depth' => 0]];
        $processedNodes = [];

        // BFS traversal from center node
        while (! empty($queue)) {
            $item = array_shift($queue);
            $nodeId = $item['nodeId'];
            $depth = $item['depth'];

            if (isset($processedNodes[$nodeId])) {
                continue;
            }

            $processedNodes[$nodeId] = true;
            $node = $this->getNodeById($nodeId);

            if ($node) {
                $visitedNodes[$nodeId] = $node;
            }

            if ($depth >= $maxDepth) {
                continue;
            }

            // Get outgoing edges
            foreach ($this->getEdgesFrom($nodeId) as $edge) {
                $visitedEdges[$edge->id] = $edge;
                if (! isset($processedNodes[$edge->toNodeId])) {
                    $queue[] = ['nodeId' => $edge->toNodeId, 'depth' => $depth + 1];
                }
            }

            // Get incoming edges
            foreach ($this->getEdgesTo($nodeId) as $edge) {
                $visitedEdges[$edge->id] = $edge;
                if (! isset($processedNodes[$edge->fromNodeId])) {
                    $queue[] = ['nodeId' => $edge->fromNodeId, 'depth' => $depth + 1];
                }
            }
        }

        return [
            'nodes' => array_values($visitedNodes),
            'edges' => array_values($visitedEdges),
        ];
    }
}

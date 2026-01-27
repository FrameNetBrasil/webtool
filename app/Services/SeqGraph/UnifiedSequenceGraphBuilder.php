<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\SeqEdge;
use App\Models\SeqGraph\SeqNode;
use App\Models\SeqGraph\UnifiedSequenceGraph;

/**
 * Builds a unified sequence graph from multiple pattern graphs.
 *
 * Combines all patterns into a single graph with:
 * - One global START node connecting to all pattern entries
 * - PATTERN nodes replacing END nodes to represent completion
 * - Explicit cross-pattern edges from PATTERN to CONSTRUCTION_REF listeners
 */
class UnifiedSequenceGraphBuilder
{
    /**
     * Renderer for generating DOT files and images.
     */
    private ?SequenceGraphRenderer $renderer = null;

    /**
     * Whether rendering is enabled.
     */
    private bool $renderingEnabled = false;

    /**
     * Enable rendering of the unified graph after building.
     *
     * @param  SequenceGraphRenderer|null  $renderer  Renderer instance (creates default if null)
     * @return $this
     */
    public function withRenderer(?SequenceGraphRenderer $renderer = null): self
    {
        $this->renderer = $renderer ?? new SequenceGraphRenderer;
        $this->renderingEnabled = true;

        return $this;
    }

    /**
     * Disable rendering.
     *
     * @return $this
     */
    public function withoutRenderer(): self
    {
        $this->renderingEnabled = false;

        return $this;
    }

    /**
     * Get the renderer instance.
     */
    public function getRenderer(): ?SequenceGraphRenderer
    {
        return $this->renderer;
    }

    /**
     * Build a unified graph from all patterns in the database.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @return UnifiedSequenceGraph The unified graph
     */
    public function buildAll(PatternGraphLoader $loader): UnifiedSequenceGraph
    {
        $patternGraphs = $loader->loadAll();

        return $this->build($patternGraphs);
    }

    /**
     * Build a unified graph from specific patterns.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @param  array<string>  $names  Pattern names to include
     * @return UnifiedSequenceGraph The unified graph
     */
    public function buildByNames(PatternGraphLoader $loader, array $names): UnifiedSequenceGraph
    {
        $patternGraphs = $loader->loadByNames($names);

        return $this->build($patternGraphs);
    }

    /**
     * Build a unified graph from pattern graph data.
     *
     * @param  array<string, array<string, mixed>>  $patternGraphs  Pattern graphs indexed by name
     * @return UnifiedSequenceGraph The unified graph
     */
    public function build(array $patternGraphs): UnifiedSequenceGraph
    {
        $nodes = [];
        $edges = [];
        $patternNodeIds = [];
        $patternEntryNodes = [];

        // Create global START node
        $globalStart = new SeqNode(
            'GLOBAL:START',
            SeqNode::TYPE_START,
            null,
            null,
            [],
            false
        );
        $globalStart->patternName = null; // Global node
        $nodes[$globalStart->id] = $globalStart;

        // Index to track which element types (construction names) have listeners
        $constructionRefListeners = []; // elementType => [nodeId, ...]

        // First pass: create all nodes and edges per pattern
        foreach ($patternGraphs as $patternName => $patternGraph) {
            $result = $this->processPattern($patternName, $patternGraph);

            // Merge nodes
            foreach ($result['nodes'] as $nodeId => $node) {
                $nodes[$nodeId] = $node;
            }

            // Merge edges
            foreach ($result['edges'] as $edge) {
                $edges[] = $edge;
            }

            // Track pattern node IDs and entry nodes
            $patternNodeIds[$patternName] = $result['patternNodeId'];
            $patternEntryNodes[$patternName] = $result['entryNodes'];

            // Connect global START to pattern entry nodes
            foreach ($result['entryNodes'] as $entryNodeId) {
                $edges[] = new SeqEdge($globalStart->id, $entryNodeId);
            }

            // Connect global START to bypass entry nodes with bypass flag
            foreach ($result['bypassEntryNodes'] as $bypassNodeId) {
                $edges[] = new SeqEdge($globalStart->id, $bypassNodeId, true);
            }

            // Track CONSTRUCTION_REF listeners for cross-pattern linking
            foreach ($result['constructionRefNodes'] as $nodeData) {
                $elementType = $nodeData['elementType'];
                if (! isset($constructionRefListeners[$elementType])) {
                    $constructionRefListeners[$elementType] = [];
                }
                $constructionRefListeners[$elementType][] = $nodeData['nodeId'];
            }
        }

        // Second pass: link PATTERN nodes to CONSTRUCTION_REF listeners
        foreach ($patternNodeIds as $patternName => $patternNodeId) {
            // Find all nodes listening for this pattern name as element type
            if (isset($constructionRefListeners[$patternName])) {
                foreach ($constructionRefListeners[$patternName] as $listenerNodeId) {
                    // Create cross-pattern edge from PATTERN to listener
                    $edges[] = new SeqEdge($patternNodeId, $listenerNodeId, false);
                }
            }
        }

        $graph = new UnifiedSequenceGraph($nodes, $edges, $patternNodeIds, $patternEntryNodes);

        $this->renderGraph($graph);

        return $graph;
    }

    /**
     * Process a single pattern graph into namespaced nodes and edges.
     *
     * @param  string  $patternName  Pattern name
     * @param  array<string, mixed>  $patternGraph  Pattern graph data
     * @return array{nodes: array<string, SeqNode>, edges: array<SeqEdge>, patternNodeId: string, entryNodes: array<string>, bypassEntryNodes: array<string>, constructionRefNodes: array<array{nodeId: string, elementType: string}>}
     */
    private function processPattern(string $patternName, array $patternGraph): array
    {
        $nodes = [];
        $edges = [];
        $patternNodeId = "PATTERN:{$patternName}";
        $entryNodes = [];
        $bypassEntryNodes = [];
        $constructionRefNodes = [];

        $originalStartId = '';
        $originalEndId = '';
        $nodeIdMap = []; // original ID => namespaced ID

        // Process nodes - namespace IDs and transform END to PATTERN
        foreach ($patternGraph['nodes'] as $nodeId => $nodeData) {
            $rawType = $nodeData['type'];
            $normalizedType = strtoupper($rawType);

            // Determine namespaced ID
            if ($normalizedType === 'START') {
                // Don't create individual start nodes - we use global START
                $originalStartId = $nodeId;
                $nodeIdMap[$nodeId] = null; // Will be connected via global START

                continue;
            }

            if ($normalizedType === 'END') {
                // Transform END to PATTERN node
                $originalEndId = $nodeId;
                $namespacedId = $patternNodeId;
                $type = SeqNode::TYPE_PATTERN;
                $elementType = null;
            } else {
                $namespacedId = "{$patternName}:{$nodeId}";
                $type = $this->mapNodeType($rawType);
                $elementType = $this->extractElementType($nodeData);
            }

            $nodeIdMap[$nodeId] = $namespacedId;

            $node = new SeqNode(
                $namespacedId,
                $type,
                $elementType,
                $nodeData['elementValue'] ?? null,
                [],
                false
            );
            $node->patternName = $patternName;
            $nodes[$namespacedId] = $node;

            // Track CONSTRUCTION_REF nodes for cross-pattern linking
            if ($normalizedType === 'CONSTRUCTION_REF' && $elementType !== null) {
                $constructionRefNodes[] = [
                    'nodeId' => $namespacedId,
                    'elementType' => $elementType,
                ];
            }
        }

        // Process edges - remap node IDs and find entry nodes
        foreach ($patternGraph['edges'] as $edgeData) {
            $fromId = $edgeData['from'];
            $toId = $edgeData['to'];

            $fromNamespaced = $nodeIdMap[$fromId] ?? null;
            $toNamespaced = $nodeIdMap[$toId] ?? null;

            // Edge from START: target becomes an entry node
            if ($fromId === $originalStartId && $toNamespaced !== null) {
                if ($edgeData['bypass'] ?? false) {
                    $bypassEntryNodes[] = $toNamespaced;
                } else {
                    $entryNodes[] = $toNamespaced;
                }

                // No edge created here - global START will connect to entry nodes
                continue;
            }

            // Skip if either node was not mapped
            if ($fromNamespaced === null || $toNamespaced === null) {
                continue;
            }

            $edges[] = new SeqEdge(
                $fromNamespaced,
                $toNamespaced,
                $edgeData['bypass'] ?? false
            );
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'patternNodeId' => $patternNodeId,
            'entryNodes' => $entryNodes,
            'bypassEntryNodes' => $bypassEntryNodes,
            'constructionRefNodes' => $constructionRefNodes,
        ];
    }

    /**
     * Map database node type to SeqNode type.
     *
     * @param  string  $rawType  Raw node type from pattern graph
     * @return string Normalized node type
     */
    private function mapNodeType(string $rawType): string
    {
        $normalized = strtoupper($rawType);

        return match ($normalized) {
            'START' => SeqNode::TYPE_START,
            'END' => SeqNode::TYPE_END,
            'PATTERN' => SeqNode::TYPE_PATTERN,
            'INTERMEDIATE' => SeqNode::TYPE_INTERMEDIATE,
            'SLOT', 'CONSTRUCTION_REF' => SeqNode::TYPE_ELEMENT,
            default => strtolower($rawType),
        };
    }

    /**
     * Extract element type from node data.
     *
     * @param  array<string, mixed>  $nodeData  Node data
     * @return string|null Element type or null for routing nodes
     */
    private function extractElementType(array $nodeData): ?string
    {
        // Database format: SLOT nodes use 'pos' field
        if (isset($nodeData['pos'])) {
            return $nodeData['pos'];
        }

        // Database format: CONSTRUCTION_REF nodes use 'construction_name' field
        if (isset($nodeData['construction_name'])) {
            return $nodeData['construction_name'];
        }

        // Test format: uses 'elementType' field directly
        return $nodeData['elementType'] ?? null;
    }

    /**
     * Render the unified graph if rendering is enabled.
     *
     * @param  UnifiedSequenceGraph  $graph  The graph to render
     */
    private function renderGraph(UnifiedSequenceGraph $graph): void
    {
        if ($this->renderingEnabled && $this->renderer !== null) {
            $this->renderer->renderUnified($graph);
        }
    }
}

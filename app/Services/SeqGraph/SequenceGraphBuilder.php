<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\SeqEdge;
use App\Models\SeqGraph\SeqNode;
use App\Models\SeqGraph\SequenceGraph;

/**
 * Builds sequence graphs from pattern graph representations.
 *
 * Transforms pattern graphs (from BNF patterns or database) into sequence graphs
 * that can track activation state. Handles conversion of nodes and edges,
 * including bypass edges for optional elements.
 */
class SequenceGraphBuilder
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
     * Enable rendering of sequence graphs after building.
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
     * Disable rendering of sequence graphs.
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
     * Build sequence graphs for all patterns from the database.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @return array<string, SequenceGraph> Sequence graphs indexed by pattern name
     */
    public function buildAll(PatternGraphLoader $loader): array
    {
        $patternGraphs = $loader->loadAll();
        $sequenceGraphs = [];

        foreach ($patternGraphs as $patternName => $patternGraph) {
            $sequenceGraphs[$patternName] = $this->build($patternName, $patternGraph);
        }

        return $sequenceGraphs;
    }

    /**
     * Build sequence graphs for specific patterns from the database.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @param  array<string>  $names  Pattern names to build
     * @return array<string, SequenceGraph> Sequence graphs indexed by pattern name
     */
    public function buildByNames(PatternGraphLoader $loader, array $names): array
    {
        $patternGraphs = $loader->loadByNames($names);
        $sequenceGraphs = [];

        foreach ($patternGraphs as $patternName => $patternGraph) {
            $sequenceGraphs[$patternName] = $this->build($patternName, $patternGraph);
        }

        return $sequenceGraphs;
    }

    /**
     * Build a sequence graph from a pattern graph.
     *
     * @param  string  $patternName  Name of the pattern
     * @param  array<string, mixed>  $patternGraph  Pattern graph with 'nodes' and 'edges' keys
     * @return SequenceGraph The constructed sequence graph
     */
    public function build(string $patternName, array $patternGraph): SequenceGraph
    {
        $nodes = [];
        $edges = [];
        $startId = '';
        $endId = '';

        // Transform nodes
        foreach ($patternGraph['nodes'] as $nodeId => $nodeData) {
            $node = $this->transformNode($nodeId, $nodeData);
            $nodes[$nodeId] = $node;

            // Track start and end nodes
            if ($node->type === SeqNode::TYPE_START) {
                $startId = $nodeId;
            }
            if ($node->type === SeqNode::TYPE_END) {
                $endId = $nodeId;
            }
        }

        // Transform edges
        foreach ($patternGraph['edges'] as $edgeData) {
            $edge = new SeqEdge(
                $edgeData['from'],
                $edgeData['to'],
                $edgeData['bypass'] ?? false
            );
            $edges[] = $edge;
        }

        $graph = new SequenceGraph($patternName, $nodes, $edges, $startId, $endId);

        $this->renderGraph($graph);

        return $graph;
    }

    /**
     * Render a graph if rendering is enabled.
     *
     * @param  SequenceGraph  $graph  The graph to render
     */
    private function renderGraph(SequenceGraph $graph): void
    {
        if ($this->renderingEnabled && $this->renderer !== null) {
            $this->renderer->render($graph);
        }
    }

    /**
     * Transform a pattern graph node into a sequence graph node.
     *
     * Handles both test format (lowercase types with elementType) and
     * database format (uppercase types with pos/construction_name).
     *
     * @param  string  $id  Node ID
     * @param  array<string, mixed>  $nodeData  Node data with type and optional element info
     * @return SeqNode The transformed sequence node
     */
    private function transformNode(string $id, array $nodeData): SeqNode
    {
        $rawType = $nodeData['type'];
        $type = $this->mapNodeType($rawType);
        $elementType = $this->extractElementType($nodeData);
        $elementValue = $nodeData['elementValue'] ?? null;

        return new SeqNode(
            $id,
            $type,
            $elementType,
            $elementValue,
            [], // timestamps start empty
            false // nodes start inactive
        );
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
            'INTERMEDIATE' => SeqNode::TYPE_INTERMEDIATE,
            'SLOT', 'CONSTRUCTION_REF' => SeqNode::TYPE_ELEMENT,
            default => strtolower($rawType), // fallback for test format
        };
    }

    /**
     * Extract element type from node data.
     *
     * For SLOT nodes: uses 'pos' field
     * For CONSTRUCTION_REF nodes: uses 'construction_name' field
     * For test format: uses 'elementType' field
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
}

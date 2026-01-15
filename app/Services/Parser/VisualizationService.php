<?php

namespace App\Services\Parser;

use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseGraph;
use App\Repositories\Parser\ParseNode;

class VisualizationService
{
    /**
     * Prepare parse graph data for D3.js visualization
     */
    public function prepareD3Data(int $idParserGraph): array
    {
        $nodes = ParseNode::listByParseGraph($idParserGraph);
        $edges = ParseEdge::listWithNodes($idParserGraph);

        return [
            'nodes' => $this->formatNodes($nodes),
            'links' => $this->formatLinks($edges),
            'layout' => config('parser.visualization.layout', 'force'),
        ];
    }

    /**
     * Format nodes for D3.js
     */
    private function formatNodes(array $nodes): array
    {
        $colors = config('parser.visualization.nodeColors', []);
        $sizeConfig = config('parser.visualization.nodeSize', []);

        return array_map(function ($node) use ($colors, $sizeConfig) {
            return [
                'id' => $node->idParserNode,
                'label' => $node->label,
                'type' => $node->type,
                'threshold' => $node->threshold,
                'activation' => $node->activation,
                'isFocus' => $node->isFocus,
                'position' => $node->positionInSentence,
                'color' => $colors[$node->type] ?? '#999999',
                'size' => $this->calculateNodeSize($node, $sizeConfig),
                'isMWE' => $node->type === 'MWE',
                'isComplete' => $node->activation >= $node->threshold,
            ];
        }, $nodes);
    }

    /**
     * Format links for D3.js
     */
    private function formatLinks(array $edges): array
    {
        $colors = config('parser.visualization.edgeColors', []);
        $widthConfig = config('parser.visualization.edgeWidth', []);

        return array_map(function ($edge) use ($colors, $widthConfig) {
            return [
                'id' => $edge->idParserLink,
                'source' => $edge->idSourceNode,
                'target' => $edge->idTargetNode,
                'type' => $edge->linkType,
                'weight' => $edge->weight,
                'color' => $colors[$edge->linkType] ?? '#000000',
                'width' => $this->calculateEdgeWidth($edge, $widthConfig),
                'sourceLabel' => $edge->sourceLabel ?? '',
                'targetLabel' => $edge->targetLabel ?? '',
            ];
        }, $edges);
    }

    /**
     * Calculate node size based on configuration
     */
    private function calculateNodeSize(object $node, array $config): int
    {
        $min = $config['min'] ?? 10;
        $max = $config['max'] ?? 30;
        $scale = $config['scale'] ?? 'threshold';

        switch ($scale) {
            case 'threshold':
                // Scale by threshold (MWEs are larger)
                return $min + (($node->threshold - 1) * 5);

            case 'activation':
                // Scale by activation level
                return $min + ($node->activation * 3);

            case 'constant':
            default:
                return ($min + $max) / 2;
        }
    }

    /**
     * Calculate edge width based on configuration
     */
    private function calculateEdgeWidth(object $edge, array $config): int
    {
        $min = $config['min'] ?? 1;
        $max = $config['max'] ?? 5;
        $scale = $config['scale'] ?? 'weight';

        switch ($scale) {
            case 'weight':
                // Scale by edge weight
                return $min + (int) (($edge->weight ?? 1.0) * ($max - $min));

            case 'constant':
            default:
                return ($min + $max) / 2;
        }
    }

    /**
     * Generate hierarchical layout data
     */
    public function prepareHierarchicalData(int $idParserGraph): array
    {
        $nodes = ParseNode::listByParseGraph($idParserGraph);
        $edges = ParseEdge::listByParseGraph($idParserGraph);

        // Organize nodes by position (sentence order)
        $levels = [];

        foreach ($nodes as $node) {
            $level = $node->positionInSentence;

            if (! isset($levels[$level])) {
                $levels[$level] = [];
            }

            $levels[$level][] = $node;
        }

        return [
            'levels' => $levels,
            'edges' => $edges,
        ];
    }

    /**
     * Generate statistics for visualization
     */
    public function getStatistics(int $idParserGraph): array
    {
        $nodes = ParseNode::listByParseGraph($idParserGraph);
        $edges = ParseEdge::listByParseGraph($idParserGraph);

        // Count by type
        $nodeTypes = [];
        $edgeTypes = [];

        foreach ($nodes as $node) {
            $type = $node->type;
            $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
        }

        foreach ($edges as $edge) {
            $type = $edge->linkType;
            $edgeTypes[$type] = ($edgeTypes[$type] ?? 0) + 1;
        }

        // Calculate metrics
        $focusCount = count(array_filter($nodes, fn ($n) => $n->isFocus));
        $mweCount = count(array_filter($nodes, fn ($n) => $n->type === 'MWE'));
        $incompleteCount = count(array_filter($nodes, fn ($n) => $n->activation < $n->threshold));

        return [
            'totalNodes' => count($nodes),
            'totalEdges' => count($edges),
            'focusNodes' => $focusCount,
            'mweNodes' => $mweCount,
            'incompleteNodes' => $incompleteCount,
            'nodesByType' => $nodeTypes,
            'edgesByType' => $edgeTypes,
            'avgDegree' => count($nodes) > 0 ? (count($edges) * 2) / count($nodes) : 0,
        ];
    }

    /**
     * Export parse graph as GraphML
     */
    public function exportGraphML(int $idParserGraph): string
    {
        $parseGraph = ParseGraph::getComplete($idParserGraph);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><graphml></graphml>');
        $xml->addAttribute('xmlns', 'http://graphml.graphdrawing.org/xmlns');

        $graph = $xml->addChild('graph');
        $graph->addAttribute('id', "parse_{$idParserGraph}");
        $graph->addAttribute('edgedefault', 'directed');

        // Add nodes
        foreach ($parseGraph->nodes as $node) {
            $nodeEl = $graph->addChild('node');
            $nodeEl->addAttribute('id', "n{$node->idParserNode}");

            $data = $nodeEl->addChild('data', htmlspecialchars($node->label));
            $data->addAttribute('key', 'label');
        }

        // Add edges
        foreach ($parseGraph->edges as $edge) {
            $edgeEl = $graph->addChild('edge');
            $edgeEl->addAttribute('source', "n{$edge->idSourceNode}");
            $edgeEl->addAttribute('target', "n{$edge->idTargetNode}");
        }

        return $xml->asXML();
    }

    /**
     * Export parse graph as DOT (Graphviz)
     */
    public function exportDOT(int $idParserGraph): string
    {
        $parseGraph = ParseGraph::getComplete($idParserGraph);

        $dot = "digraph parse_{$idParserGraph} {\n";
        $dot .= "  rankdir=LR;\n";
        $dot .= "  node [shape=circle];\n\n";

        // Add nodes
        foreach ($parseGraph->nodes as $node) {
            $label = addslashes($node->label);
            $color = config("parser.visualization.nodeColors.{$node->type}", 'gray');
            $dot .= "  n{$node->idParserNode} [label=\"{$label}\", fillcolor=\"{$color}\", style=filled];\n";
        }

        $dot .= "\n";

        // Add edges
        foreach ($parseGraph->edges as $edge) {
            $dot .= "  n{$edge->idSourceNode} -> n{$edge->idTargetNode};\n";
        }

        $dot .= "}\n";

        return $dot;
    }
}

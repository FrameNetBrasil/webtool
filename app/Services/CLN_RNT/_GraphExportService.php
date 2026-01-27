<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Graph Export Service
 *
 * Exports RuntimeGraph to various formats for visualization.
 * Currently supports DOT (Graphviz) format with color-coded nodes and edges.
 */
class GraphExportService
{
    /**
     * Node color scheme
     */
    private const NODE_COLORS = [
        'L1' => '#90EE90',        // Light green (input nodes)
        'L1_POS' => '#FFD700',    // Gold (POS tag nodes)
        'L2' => '#87CEEB',        // Sky blue (active constructions)
        'L2_SUPPRESSED' => '#D3D3D3',  // Light gray (suppressed/lost competition)
        'L2_SEQUENCER' => '#00CED1',   // Dark cyan (SEQUENCER nodes)
    ];

    /**
     * Edge color scheme
     */
    private const EDGE_COLORS = [
        'feedforward' => '#228B22',      // Forest green (compositional connections)
        'lateral-inhib' => '#FF6347',    // Tomato red (competitive inhibition)
        'prediction' => '#9370DB',       // Medium purple (future use)
        'completion' => '#4169E1',       // Royal blue (future use)
        'category' => '#FFA500',         // Orange (word-to-POS category connections)
    ];

    /**
     * Export RuntimeGraph to DOT format
     *
     * @param  RuntimeGraph  $graph  The runtime graph to export
     * @param  string  $title  Title for the graph
     * @param  array  $metadata  Optional metadata to include as graph label
     * @return string DOT format representation
     */
    public function exportToDot(RuntimeGraph $graph, string $title = 'CLN v3 Runtime Graph', array $metadata = []): string
    {
        $dot = [];
        $dot[] = 'digraph CLNv3 {';
        $dot[] = '  // Graph styling';
        $dot[] = '  rankdir=LR;';
        $dot[] = '  node [style=filled, fontname="Arial", fontsize=10];';
        $dot[] = '  edge [fontname="Arial", fontsize=8];';
        $dot[] = '';

        // Add title and metadata
        if (! empty($metadata)) {
            $metadataLines = ["Graph: {$title}"];
            foreach ($metadata as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $metadataLines[] = "{$key}: {$value}";
            }
            $label = implode('\n', $metadataLines);
            $dot[] = "  label=\"{$label}\";";
            $dot[] = '  labelloc=top;';
            $dot[] = '  fontsize=12;';
            $dot[] = '';
        }

        // Export nodes grouped by level
        $dot[] = '  // Nodes';
        $nodesByLevel = [
            'L1' => $graph->getNodesByLevel('L1'),
            'L2' => $graph->getNodesByLevel('L2'),
        ];

        foreach ($nodesByLevel as $level => $nodes) {
            if (empty($nodes)) {
                continue;
            }

            $dot[] = "  // {$level} nodes";
            foreach ($nodes as $node) {
                $dot[] = $this->formatNode($node);
            }
            $dot[] = '';
        }

        // Export edges grouped by source node
        $dot[] = '  // Edges';
        $allNodes = $graph->getAllNodes();
        $edgeCount = 0;

        foreach ($allNodes as $node) {
            $edges = $graph->getEdges($node->id);
            if (empty($edges)) {
                continue;
            }

            foreach ($edges as $edge) {
                $dot[] = $this->formatEdge($edge);
                $edgeCount++;
            }
        }

        if ($edgeCount === 0) {
            $dot[] = '  // No edges';
        }

        // Add prediction edges (virtual edges for visualization)
        $dot[] = '';
        $dot[] = '  // Prediction edges';
        $predictionCount = 0;

        foreach ($allNodes as $node) {
            if ($node->predicted_element !== null) {
                $prediction = $node->predicted_element;

                // RNT predictions (partial AND nodes predicting OR nodes)
                if (isset($prediction['or_node_id'])) {
                    $expectedOrNodeId = $prediction['or_node_id'];
                    $expectedPosition = $prediction['position'] ?? null;

                    // Find L2 nodes with matching OR node ID
                    foreach ($allNodes as $candidateNode) {
                        if ($candidateNode->cortical_level === 'L2' &&
                            isset($candidateNode->rnt_or_node_id) &&
                            $candidateNode->rnt_or_node_id === $expectedOrNodeId) {
                            // Check position if specified
                            if ($expectedPosition === null ||
                                ($candidateNode->span[0] <= $expectedPosition && $expectedPosition <= $candidateNode->span[1])) {
                                // Found the predicted OR node
                                $source = $this->escapeId($node->id);
                                $target = $this->escapeId($candidateNode->id);
                                $constructionName = $prediction['construction_name'] ?? "OR_{$expectedOrNodeId}";
                                $dot[] = "  \"{$source}\" -> \"{$target}\" [color=\"#9370DB\", label=\"predicts {$constructionName}\", arrowhead=odot, style=dashed, penwidth=2];";
                                $predictionCount++;
                                break;
                            }
                        }
                    }
                } else {
                    // CLN v3 predictions (L2 nodes predicting next L1 word)
                    $predictedPosition = $node->span[1] + 1;

                    // Try to find the predicted L1 node
                    $nodesAtPosition = $graph->getNodesAtPosition($predictedPosition);
                    foreach ($nodesAtPosition as $predictedNode) {
                        $nodeType = $predictedNode->features['type'] ?? null;
                        $nodeValue = $predictedNode->features['value'] ?? null;

                        if ($nodeType === $prediction['type'] && $nodeValue === $prediction['value']) {
                            // Found the predicted node, create edge
                            $source = $this->escapeId($node->id);
                            $target = $this->escapeId($predictedNode->id);
                            $dot[] = "  \"{$source}\" -> \"{$target}\" [color=\"#9370DB\", label=\"predicts\", arrowhead=odot, style=dashed];";
                            $predictionCount++;
                            break;
                        }
                    }
                }
            }
        }

        if ($predictionCount === 0) {
            $dot[] = '  // No predictions';
        }

        $dot[] = '}';

        return implode("\n", $dot);
    }

    /**
     * Format a node for DOT output
     *
     * @param  mixed  $node  Construction node
     * @return string DOT node definition
     */
    private function formatNode($node): string
    {
        $id = $this->escapeId($node->id);
        $label = $this->buildNodeLabel($node);

        // Choose color based on activation and level
        $color = $this->getNodeColor($node);
        $shape = $this->getNodeShape($node);

        // Build node attributes
        $attrs = [
            "label=\"{$label}\"",
            "fillcolor=\"{$color}\"",
            "shape={$shape}",
        ];

        // Add border for completed nodes
        if ($node->is_completed) {
            $attrs[] = 'penwidth=3';
            $attrs[] = 'color="#228B22"';  // Green border
        }

        // Slightly transparent style for suppressed nodes
        if ($node->cortical_level === 'L2' && $node->activation < 0.01) {
            $attrs[] = 'style="filled,dashed"';
        }

        return "  \"{$id}\" [{$this->joinAttrs($attrs)}];";
    }

    /**
     * Get color for a node based on its state
     *
     * @param  mixed  $node  Construction node
     * @return string Color code
     */
    private function getNodeColor($node): string
    {
        // L1 nodes: check if POS node
        if ($node->cortical_level === 'L1') {
            if ($node->construction_type === 'pos') {
                return self::NODE_COLORS['L1_POS'];
            }

            return self::NODE_COLORS['L1'];
        }

        // L2 nodes: check type and state
        if ($node->cortical_level === 'L2') {
            // SEQUENCER nodes use cyan color
            if (isset($node->rnt_status) &&
                in_array($node->rnt_status, ['sequencer_partial', 'sequencer_ready'])) {
                return self::NODE_COLORS['L2_SEQUENCER'];
            }

            // Activation very close to 0 means suppressed by competition
            if ($node->activation < 0.01) {
                return self::NODE_COLORS['L2_SUPPRESSED'];
            }

            return self::NODE_COLORS['L2'];
        }

        return '#CCCCCC'; // Default gray
    }

    /**
     * Build a descriptive label for a node
     *
     * @param  mixed  $node  Construction node
     * @return string Node label
     */
    private function buildNodeLabel($node): string
    {
        $parts = [];

        // Level and type
        $parts[] = "{$node->cortical_level}: {$node->construction_type}";

        // Span
        $parts[] = "Span: [{$node->span[0]}, {$node->span[1]}]";

        // Activation
        $parts[] = 'Act: '.number_format($node->activation, 3);

        // Value for L1 nodes
        if ($node->cortical_level === 'L1' && isset($node->features['value'])) {
            $parts[] = "Value: '{$node->features['value']}'";
        }

        // Pattern ID for L2 nodes
        if ($node->cortical_level === 'L2' && isset($node->bindings['pattern_id'])) {
            $parts[] = 'Pat: '.$node->bindings['pattern_id'];
        }

        // SEQUENCER-specific information
        if ($node->cortical_level === 'L2' && isset($node->rnt_status) &&
            in_array($node->rnt_status, ['sequencer_partial', 'sequencer_ready'])) {
            $activeInputs = count($node->bindings['active_inputs'] ?? []);
            $mandatoryCount = $node->bindings['mandatory_input_count'] ?? 0;
            $parts[] = "Inputs: {$activeInputs}/{$mandatoryCount}";

            if ($node->bindings['ready_to_propagate'] ?? false) {
                $parts[] = '✓ READY';
            } else {
                $parts[] = 'WAITING';
            }
        }

        // Completion status
        if ($node->is_completed) {
            $parts[] = '✓ COMPLETED';
        }

        // Prediction
        if ($node->predicted_element !== null) {
            $pred = $node->predicted_element;
            $predType = $pred['type'] ?? 'unknown';
            $predValue = $pred['value'] ?? $pred['construction_name'] ?? $pred['or_node_id'] ?? '?';
            $parts[] = "Pred: {$predType} '{$predValue}'";
        }

        return implode('\n', $parts);
    }

    /**
     * Get appropriate shape for a node
     *
     * @param  mixed  $node  Construction node
     * @return string Shape name
     */
    private function getNodeShape($node): string
    {
        // RNT node shapes based on type
        if ($node->cortical_level === 'L2' && isset($node->rnt_status)) {
            // SEQUENCER nodes use hexagon
            if ($node->rnt_status === 'sequencer_partial' || $node->rnt_status === 'sequencer_ready') {
                return 'hexagon';
            }

            // AND nodes use rectangle
            if ($node->rnt_status === 'partial_and' || $node->rnt_status === 'complete_and') {
                return 'rectangle';
            }
        }

        return match ($node->cortical_level) {
            'L1' => 'box',
            'L2' => 'ellipse',  // OR nodes and single-element constructions
            default => 'box',
        };
    }

    /**
     * Format an edge for DOT output
     *
     * @param  mixed  $edge  Connection edge
     * @return string DOT edge definition
     */
    private function formatEdge($edge): string
    {
        $source = $this->escapeId($edge->source);
        $target = $this->escapeId($edge->target);
        $color = self::EDGE_COLORS[$edge->type] ?? '#666666';
        $label = $this->buildEdgeLabel($edge);

        // Build edge attributes
        $attrs = [
            "color=\"{$color}\"",
        ];

        if ($label !== '') {
            $attrs[] = "label=\"{$label}\"";
        }

        // Different arrow styles for different edge types
        $arrowhead = match ($edge->type) {
            'lateral-inhib' => 'tee',     // T-shaped arrow for inhibition
            'prediction' => 'odot',       // Circle arrow for predictions
            default => 'normal',           // Standard arrow for feedforward
        };
        $attrs[] = "arrowhead={$arrowhead}";

        // Dashed line for predictions
        if ($edge->type === 'prediction') {
            $attrs[] = 'style=dashed';
        }

        // Bold line for high-weight edges
        if ($edge->weight > 1.0) {
            $attrs[] = 'penwidth=2';
        }

        return "  \"{$source}\" -> \"{$target}\" [{$this->joinAttrs($attrs)}];";
    }

    /**
     * Build label for an edge
     *
     * @param  mixed  $edge  Connection edge
     * @return string Edge label
     */
    private function buildEdgeLabel($edge): string
    {
        $parts = [];

        // Edge type
        $parts[] = $edge->type;

        // Weight if not 1.0
        if (abs($edge->weight - 1.0) > 0.001) {
            $parts[] = 'w: '.number_format($edge->weight, 2);
        }

        return implode('\n', $parts);
    }

    /**
     * Escape a node ID for DOT format
     *
     * @param  string  $id  Node ID
     * @return string Escaped ID
     */
    private function escapeId(string $id): string
    {
        return str_replace('"', '\\"', $id);
    }

    /**
     * Join attributes for DOT format
     *
     * @param  array  $attrs  Attributes
     * @return string Joined attributes
     */
    private function joinAttrs(array $attrs): string
    {
        return implode(', ', $attrs);
    }

    /**
     * Save DOT string to a file
     *
     * @param  string  $dot  DOT format string
     * @param  string  $filepath  Output file path
     * @return bool Success status
     */
    public function saveDotToFile(string $dot, string $filepath): bool
    {
        $directory = dirname($filepath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($filepath, $dot) !== false;
    }
}

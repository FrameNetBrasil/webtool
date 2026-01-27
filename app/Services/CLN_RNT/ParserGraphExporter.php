<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\LearnGraph;
use App\Models\CLN_RNT\Node;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Parser Graph Exporter
 *
 * Exports the runtime parser graph to DOT format for visualization.
 * Shows activation dynamics results, pattern graph associations, and parse structure.
 */
class ParserGraphExporter
{
    /**
     * Activation-based color gradient
     * Maps activation level to color (green = high, yellow = medium, gray = low)
     */
    private const ACTIVATION_COLORS = [
        'high' => '#32CD32',      // Lime green (activation >= 0.7)
        'medium' => '#FFD700',    // Gold (0.3 <= activation < 0.7)
        'low' => '#FFA500',       // Orange (0.1 <= activation < 0.3)
        'very_low' => '#D3D3D3',  // Light gray (activation < 0.1)
    ];

    /**
     * Node type colors
     */
    private const TYPE_COLORS = [
        'literal' => '#90EE90',   // Light green
        'pos' => '#FFD700',       // Gold
        'construction' => '#87CEEB', // Sky blue
    ];

    /**
     * Edge colors
     */
    private const EDGE_COLORS = [
        'feedforward' => '#228B22',      // Forest green
        'lateral-inhib' => '#FF6347',    // Tomato red
        'category' => '#FFA500',         // Orange
    ];

    /**
     * Export runtime graph to DOT format
     *
     * @param  RuntimeGraph  $graph  Runtime graph after parsing
     * @param  string  $sentence  Original sentence
     * @param  array  $stats  Parsing statistics
     * @return string DOT format content
     */
    public function exportToDot(RuntimeGraph|LearnGraph $graph, string $sentence, array $stats = [], array $activeNodes = []): string
    {
        $dot = [];
        $dot[] = 'digraph ParserGraph {';
        $dot[] = '  rankdir=LR;';
        $dot[] = '  node [style=filled, fontname="Arial", fontsize=10];';
        $dot[] = '  edge [fontname="Arial", fontsize=8];';
        $dot[] = '  compound=true;';
        $dot[] = '';

        // Add metadata
        $metadata = $this->buildMetadata($sentence, $stats);
        $dot[] = "  label=\"{$metadata}\";";
        $dot[] = '  labelloc=top;';
        $dot[] = '  fontsize=12;';
        $dot[] = '';

        // Export nodes
        $nodes = $graph->getAllNodes();
        if (! empty($nodes)) {
            $dot[] = '  // Nodes';
            foreach ($nodes as $node) {
                $dot[] = '    '.$this->formatNode($node, $activeNodes);
            }
            $dot[] = '';
        }
        // Export edges
        $dot[] = '  // Edges';
        $allNodes = $graph->getAllNodes();
        $edgeCount = 0;

        foreach ($allNodes as $node) {
            $edges = $graph->getEdges($node->id);
            foreach ($edges as $edge) {
                $dot[] = '  '.$this->formatEdge($edge);
                $edgeCount++;
            }
        }

        if ($edgeCount === 0) {
            $dot[] = '  // No edges';
        }

        $dot[] = '}';

        return implode("\n", $dot);
    }

    /**
     * Build metadata label for graph
     *
     * @param  string  $sentence  Original sentence
     * @param  array  $stats  Parsing statistics
     * @return string Escaped metadata string
     */
    private function buildMetadata(string $sentence, array $stats): string
    {
        $lines = [
            'Parser Graph Visualization',
            'Sentence: '.$sentence,
        ];

        if (isset($stats['iterations'])) {
            $lines[] = 'Iterations: '.$stats['iterations'];
        }

        if (isset($stats['or_nodes_activated'])) {
            $lines[] = 'OR Nodes Activated: '.$stats['or_nodes_activated'];
        }

        if (isset($stats['and_nodes_activated'])) {
            $lines[] = 'AND Nodes Activated: '.$stats['and_nodes_activated'];
        }

        if (isset($stats['sequencer_nodes_activated'])) {
            $lines[] = 'SEQUENCER Nodes Activated: '.$stats['sequencer_nodes_activated'];
        }

        return implode('\n', array_map(fn ($line) => addslashes($line), $lines));
    }

    /**
     * Format a node for DOT output
     *
     * @param  \App\Models\CLN_RNT\Node  $node  Node to format
     * @return string DOT node definition
     */
    private function formatNode(Node $node, array $activeNodes): string
    {
        $id = $this->escapeId($node->id);
        $label = $this->buildNodeLabel($node);
        $color = $this->getNodeColor($node, $activeNodes);
        $shape = $this->getNodeShape($node);

        $attrs = [
            "label=\"{$label}\"",
            "fillcolor=\"{$color}\"",
            "shape={$shape}",
        ];

        // Add bold border for high activation
        if ($node->activation >= 0.7) {
            $attrs[] = 'penwidth=3';
            $attrs[] = 'color="#228B22"';
        }

        return "\"{$id}\" [{$this->joinAttrs($attrs)}];";
    }

    /**
     * Build node label with activation and details
     *
     * @param  \App\Models\CLN_RNT\Node  $node  Node
     * @return string Escaped label
     */
    private function buildNodeLabel(Node $node): string
    {
        $parts = [];

        // Node type and ID
        $parts[] = $node->getName();//$node->type;

        // Value for literals
        if (isset($node->features['value'])) {
            $value = $node->features['value'];
            $parts[] = "'{$value}'";
        }

        $parts[] = $node->idPatternNode;

        // Span
//        $parts[] = "[{$node->span[0]}-{$node->span[1]}]";

        // Activation level
        $activation = number_format($node->activation, 3);
        $parts[] = "Act: {$activation}";

        // Pattern node ID for L2 nodes
//        if ($node->cortical_level === 'L2' && isset($node->bindings['pattern_node_id'])) {
//            $patternId = $node->bindings['pattern_node_id'];
//            $parts[] = "Pat: {$patternId}";
//        }

        $label = implode('\n', $parts);

        //return addslashes($label);
        return $label;
    }

    /**
     * Get node color based on activation level
     *
     * @param  \App\Models\CLN_RNT\Node  $node  Node
     * @return string Color hex code
     */
    private function getNodeColor(Node $node, array $activeNodes): string
    {
        $activation = $node->activation;

        if (empty($activeNodes)) {
            if ($activation >= 0.5) {
                return self::ACTIVATION_COLORS['high'];
            }

            if ($activation >= 0.25) {
                return self::ACTIVATION_COLORS['medium'];
            }

            if ($activation >= 0.1) {
                return self::ACTIVATION_COLORS['low'];
            }

            return self::ACTIVATION_COLORS['very_low'];
        } else {
            if (in_array($node->id,$activeNodes)) {
                if ($node->activated) {
                    return self::ACTIVATION_COLORS['high'];
                } else {
                    return self::ACTIVATION_COLORS['low'];
                }
            } else {
                return self::ACTIVATION_COLORS['very_low'];
            }
        }
    }

    /**
     * Get node shape based on type
     *
     * @param  \App\Models\CLN_RNT\Node  $node  Node
     * @return string DOT shape name
     */
    private function getNodeShape(Node $node): string
    {
//        if ($node->getLayer() === 'L1') {
//            if ($node->type === 'pos') {
//                return 'hexagon';
//            }
//
//            return 'box';
//        }

        if ($node->type === 'OR') {
            return 'ellipse';
        }
        if ($node->type === 'SEQUENCER') {
            return 'hexagon';
        }
        if ($node->type === 'SOM') {
            return 'diamond';
        }
        if ($node->type === 'VIP') {
            return 'triangle';
        }

        return 'box';
    }

    /**
     * Format an edge for DOT output
     *
     * @param  \App\Models\CLN_RNT\ConnectionEdge  $edge  Edge to format
     * @return string DOT edge definition
     */
    private function formatEdge($edge): string
    {
        $source = $this->escapeId($edge->source);
        $target = $this->escapeId($edge->target);
        //$color = self::EDGE_COLORS[$edge->type] ?? '#999999';
        $color = $edge->active ? 'black' : '#999999';
        $weight = number_format($edge->weight, 2);
        $style = (isset($edge->optional) && ($edge->optional === true)) ? "dashed" : "filled";

        $attrs = [
            "color=\"{$color}\"",
            "label=\"{$edge->type} (w={$weight})\"",
            "style=\"{$style}\"",
            'penwidth='.min(3, max(1, $edge->weight)),
        ];

        return "\"{$source}\" -> \"{$target}\" [{$this->joinAttrs($attrs)}];";
    }

    /**
     * Escape node ID for DOT format
     *
     * @param  string  $id  Node ID
     * @return string Escaped ID
     */
    private function escapeId(string $id): string
    {
        return addslashes($id);
    }

    /**
     * Join attribute array into DOT attribute string
     *
     * @param  array  $attrs  Attributes
     * @return string Joined attributes
     */
    private function joinAttrs(array $attrs): string
    {
        return implode(', ', $attrs);
    }

    /**
     * Save DOT content to file
     *
     * @param  string  $dot  DOT content
     * @param  string  $filepath  Output file path
     * @return bool Success
     */
    public function saveDotToFile(string $dot, string $filepath): bool
    {
        $directory = dirname($filepath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($filepath, $dot) !== false;
    }

    /**
     * Render DOT file to PNG using Graphviz
     *
     * @param  string  $dotFilepath  Path to DOT file
     * @param  string  $outputFilepath  Path for output PNG
     * @return array Result with success status and message
     */
    public function renderToPng(string $dotFilepath, string $outputFilepath): array
    {
        if (! file_exists($dotFilepath)) {
            return [
                'success' => false,
                'message' => "DOT file not found: {$dotFilepath}",
            ];
        }

        // Check if dot command is available
        $dotCheck = shell_exec('which dot 2>&1');
        if (empty($dotCheck)) {
            return [
                'success' => false,
                'message' => 'Graphviz (dot command) not found. Install with: sudo apt-get install graphviz',
            ];
        }

        // Render to PNG
        $command = sprintf(
            'dot -Tpng %s -o %s 2>&1',
            escapeshellarg($dotFilepath),
            escapeshellarg($outputFilepath)
        );

        $output = shell_exec($command);

        if (file_exists($outputFilepath)) {
            return [
                'success' => true,
                'message' => "PNG rendered successfully: {$outputFilepath}",
                'output' => $output,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to render PNG',
            'output' => $output,
        ];
    }
}

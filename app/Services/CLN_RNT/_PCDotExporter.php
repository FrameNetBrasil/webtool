<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\PCParserGraphEdge;
use App\Models\CLN_RNT\PCParserGraphNode;

/**
 * PC DOT Exporter
 *
 * Exports PC parser graphs to GraphViz DOT format for visualization.
 *
 * Visualization features:
 * - Nodes organized by position (subgraphs/columns)
 * - Color-coded by status (active/waiting/completed) and type (token/construction)
 * - Edges styled by type (match/prediction/completion)
 * - Clear labeling of constructions and tokens
 */
class PCDotExporter
{
    /**
     * Node counter for sequential numbering
     */
    private int $nodeCounter = 0;

    /**
     * Export parser graph to DOT format
     *
     * @param  array  $graph  Graph data from PCGraphBuilder::getGraph()
     * @param  string  $sequence  Original input sequence
     * @return string DOT format string
     */
    public function export(array $graph, string $sequence): string
    {
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        // Reset node counter for each export
        $this->nodeCounter = 0;

        $dot = "digraph PCParserGraph {\n";
        $dot .= "  rankdir=LR;\n";
        $dot .= "  compound=true;\n";
        $dot .= "  node [style=filled];\n\n";

        // Title
        $dot .= "  labelloc=\"t\";\n";
        $dot .= "  label=\"PC Parser Graph: {$sequence}\";\n";
        $dot .= "  fontsize=16;\n\n";

        // Group nodes by position for subgraphs
        $nodesByPosition = $this->groupNodesByPosition($nodes);

        // Export nodes organized by position
        foreach ($nodesByPosition as $position => $positionNodes) {
            $dot .= $this->exportPosition($position, $positionNodes);
        }

        // Export edges
        $dot .= "\n  // Edges\n";
        foreach ($edges as $edge) {
            $dot .= $this->exportEdge($edge);
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Group nodes by position
     */
    private function groupNodesByPosition(array $nodes): array
    {
        $grouped = [];

        foreach ($nodes as $node) {
            $position = $node->position;
            if (! isset($grouped[$position])) {
                $grouped[$position] = [];
            }
            $grouped[$position][] = $node;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * Export nodes at a specific position as a subgraph
     */
    private function exportPosition(int $position, array $nodes): string
    {
        $dot = "  subgraph cluster_{$position} {\n";
        $dot .= "    label=\"Position {$position}\";\n";
        $dot .= "    style=dashed;\n";
        $dot .= "    color=gray;\n\n";

        foreach ($nodes as $node) {
            $dot .= $this->exportNode($node);
        }

        $dot .= "  }\n\n";

        return $dot;
    }

    /**
     * Export a single node
     */
    private function exportNode(PCParserGraphNode $node): string
    {
        // Increment and assign sequential number
        $this->nodeCounter++;
        $nodeNumber = $this->nodeCounter;

        $style = $this->getNodeStyle($node);
        $label = $this->getNodeLabel($node, $nodeNumber);

        $attributes = [
            "label=\"{$label}\"",
            "fillcolor={$style['fillcolor']}",
            "style=\"{$style['style']}\"",
        ];

        if (isset($style['penwidth'])) {
            $attributes[] = "penwidth={$style['penwidth']}";
        }

        if (isset($style['shape'])) {
            $attributes[] = "shape={$style['shape']}";
        }

        $attributesStr = implode(', ', $attributes);

        return "    {$node->id} [{$attributesStr}];\n";
    }

    /**
     * Get node label with sequential number
     */
    private function getNodeLabel(PCParserGraphNode $node, int $number): string
    {
        if ($node->isToken()) {
            // For tokens: show number, POS and word
            $parts = explode('/', $node->value);
            if (count($parts) === 2) {
                return "[{$number}] {$parts[0]}\\n{$parts[1]}";
            }

            return "[{$number}] {$node->value}";
        } else {
            // For constructions: show number, type and name
            $prefix = $node->isWaiting() ? 'WAIT' : 'CXN';

            return "[{$number}] {$prefix}\\n{$node->value}";
        }
    }

    /**
     * Get node style based on status and type
     */
    private function getNodeStyle(PCParserGraphNode $node): array
    {
        $style = [
            'style' => 'filled',
        ];

        // Color by status and confirmation state
        // Priority: waiting > confirmed > completed > token > active
        if ($node->isWaiting()) {
            // Waiting nodes: yellow
            $style['fillcolor'] = 'yellow';
        } elseif ($node->isConfirmed()) {
            // Confirmed nodes (predictions were validated): cyan (bold)
            $style['fillcolor'] = 'cyan';
            $style['style'] = 'filled,bold';
        } elseif ($node->isCompleted()) {
            // Completed constructions: green (bold)
            $style['fillcolor'] = 'green';
            $style['style'] = 'filled,bold';
            $style['penwidth'] = 2;
        } elseif ($node->isToken()) {
            // Active token nodes: lightblue
            $style['fillcolor'] = 'lightblue';
        } else {
            // Active construction nodes: lightgreen
            $style['fillcolor'] = 'lightgreen';
        }

        return $style;
    }

    /**
     * Export a single edge
     */
    private function exportEdge(PCParserGraphEdge $edge): string
    {
        $style = $this->getEdgeStyle($edge);

        $attributes = [
            "label=\"{$edge->label}\"",
        ];

        if (isset($style['color'])) {
            $attributes[] = "color={$style['color']}";
        }

        if (isset($style['style'])) {
            $attributes[] = "style=\"{$style['style']}\"";
        }

        if (isset($style['penwidth'])) {
            $attributes[] = "penwidth={$style['penwidth']}";
        }

        $attributesStr = implode(', ', $attributes);

        return "  {$edge->fromNodeId} -> {$edge->toNodeId} [{$attributesStr}];\n";
    }

    /**
     * Get edge style based on type
     */
    private function getEdgeStyle(PCParserGraphEdge $edge): array
    {
        $style = [];

        if ($edge->isPrediction()) {
            // Prediction edges: orange
            $style['color'] = 'orange';

            // Expected: dashed line, Confirmed: solid line
            if ($edge->isConfirmed()) {
                // Confirmed prediction: solid line
                $style['style'] = 'solid';
            } else {
                // Expected prediction: dashed line
                $style['style'] = 'dashed';
            }
        } elseif ($edge->isCompletion()) {
            // Completion edges: green solid (bold for completed constructions)
            $style['color'] = 'green';
            $style['penwidth'] = 2;
        } else {
            // Match edges: blue solid
            $style['color'] = 'blue';
        }

        return $style;
    }

    /**
     * Save DOT string to file
     *
     * @param  string  $dot  DOT format string
     * @param  string  $filepath  File path to save to
     * @return bool Success
     */
    public function saveToFile(string $dot, string $filepath): bool
    {
        // Ensure directory exists
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write file
        return file_put_contents($filepath, $dot) !== false;
    }
}

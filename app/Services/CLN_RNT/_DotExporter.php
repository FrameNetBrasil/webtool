<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\SequenceResult;
use App\Models\CLN\BNode;
use App\Models\CLN\JNode;
use App\Services\CLN\Node;

/**
 * DOT Exporter for CLN Network Visualization
 *
 * Generates GraphViz DOT format output to visualize the CLN parsing network.
 * Shows columns, layers (L23 and L5), nodes, and connections with colors.
 *
 * Node color scheme:
 * - L23 word nodes: lightblue
 * - L23 lemma nodes: lightsteelblue
 * - L23 POS nodes: lightskyblue
 * - L23 feature nodes: lightcyan
 * - L23 construction nodes (from L5 feedback): lavender
 * - L5 partial construction nodes: lightyellow
 * - L5 confirmed construction nodes: lightgreen
 *
 * Connection color scheme (circuits):
 * - L23 → L5 (forward activation): blue solid
 * - L23 → L5 (recursive composition): blue bold "compose"
 * - L5 → L23 (construction feedback - completed): purple bold "feedback"
 * - L5 → L23 (prediction - partial construction): orange dotted "prediction"
 * - L5 → L23 (forward prediction): orange dashed "predict"
 * - L5 → L23 (backward confirmation): purple dotted "feedback"
 * - L23 → L23 (lateral): green solid "lateral"
 * - L5 → L5 (construction linking): red solid
 */
class DotExporter
{
    /**
     * Export sequence result to DOT format
     *
     * @param  SequenceResult  $result  Parse result to export
     * @param  array  $columns  Array of CLNColumn objects
     * @param  array  $options  Export options
     * @return string DOT format string
     */
    public function export(SequenceResult $result, array $columns, array $options = []): string
    {
        $showPartials = $options['showPartials'] ?? true;
        $showPredictions = $options['showPredictions'] ?? true;
        $showL23Connections = $options['showL23Connections'] ?? false;

        $dot = "digraph CLN_Network {\n";
        $dot .= "  rankdir=TB;\n";
        $dot .= "  compound=true;\n";
        $dot .= "  newrank=true;\n";
        $dot .= "  splines=true;\n";
        $dot .= "  node [style=filled];\n\n";

        // Add title
        $sentence = $result->getSentence();
        $dot .= "  labelloc=\"t\";\n";
        $dot .= "  label=\"CLN Network: {$sentence}\";\n";
        $dot .= "  fontsize=16;\n\n";

        // Create subgraphs for each column
        foreach ($columns as $position => $column) {
            $token = $result->tokens[$position] ?? null;
            $tokenForm = $token ? ($token->form ?? '?') : '?';

            $dot .= $this->exportColumn($column, $position, $tokenForm, $showPartials);
        }

        // Add network connections (circuits) - ALWAYS show, they're the core of the network
        $dot .= $this->exportPredictions($columns, $showPartials);

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Export a single column to DOT format
     *
     * @param  \App\Models\CLN_RNT\CLNColumn  $column  Column to export
     * @param  int  $position  Column position
     * @param  string  $tokenForm  Token surface form
     * @param  bool  $showPartials  Whether to show partial constructions
     * @return string DOT format for this column
     */
    private function exportColumn($column, int $position, string $tokenForm, bool $showPartials): string
    {
        $l23 = $column->getL23();
        $l5 = $column->getL5();

        $dot = "  // Column {$position}: {$tokenForm}\n";
        $dot .= "  subgraph cluster_{$position} {\n";
        $dot .= "    label=\"Position {$position}: {$tokenForm}\";\n";
        $dot .= "    style=dashed;\n";
        $dot .= "    color=gray;\n\n";

        // L23 Layer nodes
        $dot .= "    // L23 Layer\n";
        $dot .= "    subgraph cluster_{$position}_l23 {\n";
        $dot .= "      label=\"L23\";\n";
        $dot .= "      style=filled;\n";
        $dot .= "      color=lightgray;\n";
        $dot .= "      rank=same;\n\n";

        foreach ($l23->getAllNodes() as $node) {
            $dot .= $this->exportNode($node, $position, 'l23');
        }

        $dot .= "    }\n\n";

        // L5 Layer nodes
        $dot .= "    // L5 Layer\n";
        $dot .= "    subgraph cluster_{$position}_l5 {\n";
        $dot .= "      label=\"L5\";\n";
        $dot .= "      style=filled;\n";
        $dot .= "      color=lightgray;\n";
        $dot .= "      rank=same;\n\n";

        // Show confirmed constructions
        foreach ($l5->getNodesByType('construction') as $node) {
            $dot .= $this->exportNode($node, $position, 'l5');
        }

        // Show partial constructions if enabled
        if ($showPartials) {
            foreach ($l5->getPartialConstructions() as $node) {
                $dot .= $this->exportNode($node, $position, 'l5');
            }
        }

        $dot .= "    }\n";
        $dot .= "  }\n\n";

        return $dot;
    }

    /**
     * Export a single node to DOT format
     *
     * @param  Node  $node  Node to export
     * @param  int  $position  Column position
     * @param  string  $layer  Layer name (l23 or l5)
     * @return string DOT format for this node
     */
    private function exportNode(Node $node, int $position, string $layer): string
    {
        $nodeType = $node->metadata['node_type'] ?? 'unknown';
        $nodeId = "p{$position}_{$layer}_".str_replace(['-', ' ', ':', '='], '_', $node->id);

        // Get node label
        $label = $this->getNodeLabel($node, $nodeType);

        // Get node color and shape
        [$color, $shape] = $this->getNodeStyle($node, $nodeType, $layer);

        // Check if node is activated
        $isActivated = ($node instanceof JNode) ? $node->isFired() : $node->isActivated();
        $style = $isActivated ? 'filled,bold' : 'filled';

        $dot = "      {$nodeId} [label=\"{$label}\", fillcolor={$color}, shape={$shape}, style=\"{$style}\"];\n";

        return $dot;
    }

    /**
     * Get node label for display
     *
     * @param  Node  $node  Node
     * @param  string  $nodeType  Node type
     * @return string Label text
     */
    private function getNodeLabel(Node $node, string $nodeType): string
    {
        // Check if this is a construction node from L5 feedback in L23
        if ($nodeType === 'construction' && $node->layer->value === 'L23') {
            $isFromL5Feedback = $node->metadata['is_from_l5_feedback'] ?? false;
            $prefix = $isFromL5Feedback ? 'C: ' : 'C: ';

            return $prefix.($node->metadata['name'] ?? 'UNKNOWN');
        }

        return match ($nodeType) {
            'word' => 'W: '.($node->metadata['value'] ?? ''),
            'lemma' => 'L: '.($node->metadata['value'] ?? ''),
            'pos' => 'POS: '.($node->metadata['value'] ?? ''),
            'feature' => ($node->metadata['feature'] ?? '').'='.($node->metadata['value'] ?? ''),
            'construction' => 'C: '.($node->metadata['name'] ?? 'UNKNOWN'),
            'partial_construction' => 'PC: '.($node->metadata['name'] ?? 'UNKNOWN'),
            default => $nodeType,
        };
    }

    /**
     * Get node style (color and shape) based on type
     *
     * @param  Node  $node  Node
     * @param  string  $nodeType  Node type
     * @param  string  $layer  Layer (l23 or l5)
     * @return array [color, shape]
     */
    private function getNodeStyle(Node $node, string $nodeType, string $layer): array
    {
        if ($layer === 'l23') {
            // NEW: Check for predicted nodes first
            $isPredicted = $node->metadata['is_predicted'] ?? false;
            $isConfirmed = $node->metadata['prediction_confirmed'] ?? false;

            if ($isPredicted) {
                if ($isConfirmed) {
                    return ['lightgreen', 'box']; // Confirmed prediction
                } else {
                    return ['lightpink', 'box']; // Unconfirmed prediction
                }
            }

            // Special styling for construction nodes in L23 (from L5 feedback)
            if ($nodeType === 'construction') {
                $isFromL5Feedback = $node->metadata['is_from_l5_feedback'] ?? false;

                return $isFromL5Feedback ? ['lavender', 'box'] : ['white', 'box'];
            }

            return match ($nodeType) {
                'word' => ['lightblue', 'box'],
                'lemma' => ['lightsteelblue', 'box'],
                'pos' => ['lightskyblue', 'ellipse'],
                'feature' => ['lightcyan', 'diamond'],
                default => ['white', 'box'],
            };
        }

        // L5 nodes
        if ($nodeType === 'construction') {
            return ['lightgreen', 'box'];
        }

        if ($nodeType === 'partial_construction') {
            $isPartial = $node->metadata['is_partial'] ?? true;

            return $isPartial ? ['lightyellow', 'box'] : ['lightgreen', 'box'];
        }

        return ['white', 'box'];
    }

    /**
     * Export connections between nodes (the network circuits)
     *
     * Extracts and visualizes all node-to-node connections:
     * - L23 -> L5: Forward activation (blue solid)
     * - L5 -> L23: Predictions (orange dashed)
     * - L23 -> L23: Lateral confirmation (green solid)
     * - L5 -> L5: Construction linking (red solid)
     *
     * @param  array  $columns  Array of CLNColumn objects
     * @return string DOT format for all connection edges
     */
    private function exportPredictions(array $columns, bool $showPartials): string
    {
        $dot = "  // Network Connections (Circuits)\n";

        // Export all connections from node input/output links
        foreach ($columns as $position => $column) {
            $l23 = $column->getL23();
            $l5 = $column->getL5();

            // Export L23 layer connections
            $dot .= $this->exportLayerConnections($l23, $position, 'l23', $columns, $showPartials);

            // Export L5 layer connections
            $dot .= $this->exportLayerConnections($l5, $position, 'l5', $columns, $showPartials);

            // Export L5→L23 feedback connections (not stored as node connections to prevent loops)
            $dot .= $this->exportFeedbackConnections($l5, $l23, $position);
        }

        return $dot;
    }

    /**
     * Export connections from a single layer
     *
     * @param  mixed  $layer  L23Layer or L5Layer
     * @param  int  $position  Column position
     * @param  string  $layerName  Layer name (l23 or l5)
     * @param  array  $columns  All columns for verifying target nodes exist
     * @param  bool  $showPartials  Whether partial constructions are being shown
     * @return string DOT format for layer connections
     */
    private function exportLayerConnections($layer, int $position, string $layerName, array $columns, bool $showPartials): string
    {
        $dot = '';
        $allNodes = $layer->getAllNodes();

        foreach ($allNodes as $node) {
            $sourceId = "p{$position}_{$layerName}_".str_replace(['-', ' ', ':', '='], '_', $node->id);

            // Export output connections
            $outputNodes = ($node instanceof \App\Models\CLN\JNode || $node instanceof \App\Models\CLN\BNode)
                ? $node->getOutputNodes()
                : [];

            foreach ($outputNodes as $outputNode) {

                // Skip connections to nodes that shouldn't be shown
                $targetLayer = $outputNode->layer->value === 'L23' ? 'l23' : 'l5';
                $targetPosition = $this->findNodePosition($outputNode, $position);

                // Skip connections to partial constructions if they're not being shown
                $isPartialConstruction = ($outputNode->metadata['is_partial'] ?? false) === true;
                if ($isPartialConstruction && ! $showPartials) {
                    continue; // Skip connections to hidden partial constructions
                }

                // Verify target node still exists in its layer
                // This prevents orphaned references from showing up in the visualization
                $targetExists = false;
                if (isset($columns[$targetPosition])) {
                    $targetLayerObj = $targetLayer === 'l23'
                        ? $columns[$targetPosition]->getL23()
                        : $columns[$targetPosition]->getL5();
                    $targetExists = $targetLayerObj->getNode($outputNode->id) !== null;
                }

                if (! $targetExists) {
                    continue; // Skip this orphaned connection
                }

                $targetId = "p{$targetPosition}_{$targetLayer}_".str_replace(['-', ' ', ':', '='], '_', $outputNode->id);

                // Determine connection type and style
                [$color, $style, $label] = $this->getConnectionStyle($node, $outputNode, $position, $targetPosition);

                $dot .= "  {$sourceId} -> {$targetId} [color={$color}, style={$style}";
                if ($label) {
                    $dot .= ", label=\"{$label}\"";
                }
                $dot .= "];\n";
            }
        }

        return $dot;
    }

    /**
     * Export L5→L23 feedback connections based on metadata
     *
     * Since we don't store bidirectional links (to prevent infinite loops),
     * we need to detect feedback connections by examining L23 construction nodes
     * and their metadata that points back to the source L5 construction.
     *
     * @param  mixed  $l5  L5Layer
     * @param  mixed  $l23  L23Layer
     * @param  int  $position  Column position
     * @return string DOT format for feedback connections
     */
    private function exportFeedbackConnections($l5, $l23, int $position): string
    {
        $dot = '';

        // Get all L23 construction nodes that were created from L5 feedback
        $l23Nodes = $l23->getAllNodes();

        foreach ($l23Nodes as $l23Node) {
            // Check if this is a construction node from L5 feedback
            $isFromL5Feedback = ($l23Node->metadata['is_from_l5_feedback'] ?? false) === true;
            if (! $isFromL5Feedback) {
                continue;
            }

            // Get the source L5 node ID
            $sourceL5NodeId = $l23Node->metadata['source_l5_node_id'] ?? null;
            if ($sourceL5NodeId === null) {
                continue;
            }

            // Find the L5 source node
            $l5Node = $l5->getNode($sourceL5NodeId);
            if ($l5Node === null) {
                continue;
            }

            // Create connection from L5 to L23
            $sourceId = "p{$position}_l5_".str_replace(['-', ' ', ':', '='], '_', $l5Node->id);
            $targetId = "p{$position}_l23_".str_replace(['-', ' ', ':', '='], '_', $l23Node->id);

            $dot .= "  {$sourceId} -> {$targetId} [color=purple, style=bold, label=\"feedback\"];\n";
        }

        return $dot;
    }

    /**
     * Find which column position a node belongs to
     *
     * @param  mixed  $node  JNode or BNode
     * @param  int  $defaultPosition  Default position if not found
     * @return int Column position
     */
    private function findNodePosition($node, int $defaultPosition): int
    {
        // Try to extract position from node ID
        if (preg_match('/partial_(\d+)_/', $node->id, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/_(\d+)_/', $node->id, $matches)) {
            return (int) $matches[1];
        }

        // Check metadata for position
        if (isset($node->metadata['anchor_position'])) {
            return $node->metadata['anchor_position'];
        }
        if (isset($node->metadata['position'])) {
            return $node->metadata['position'];
        }

        return $defaultPosition;
    }

    /**
     * Get connection style based on connection type
     *
     * @param  mixed  $sourceNode  Source node
     * @param  mixed  $targetNode  Target node
     * @param  int  $sourcePos  Source position
     * @param  int  $targetPos  Target position
     * @return array [color, style, label]
     */
    private function getConnectionStyle($sourceNode, $targetNode, int $sourcePos, int $targetPos): array
    {
        $sourceLayer = $sourceNode->layer->value;
        $targetLayer = $targetNode->layer->value;

        // Check for predicted nodes
        $sourceIsPredicted = $sourceNode->metadata['is_predicted'] ?? false;
        $targetIsPredicted = $targetNode->metadata['is_predicted'] ?? false;

        // L23 -> L5 (forward activation - Circuit 1)
        if ($sourceLayer === 'L23' && $targetLayer === 'L5') {
            // Check if source is a predicted node
            if ($sourceIsPredicted) {
                // Check if prediction was confirmed
                $predictionConfirmed = $sourceNode->metadata['prediction_confirmed'] ?? false;

                if ($predictionConfirmed) {
                    // Confirmed prediction → composition link (completing partial construction)
                    return ['blue', 'bold', 'compose'];
                } else {
                    // Unconfirmed prediction → prediction link (still waiting)
                    return ['orange', 'dotted', 'pred-link'];
                }
            }

            // Check if source is a construction node (recursive composition)
            $sourceIsConstruction = ($sourceNode->metadata['node_type'] ?? '') === 'construction';
            if ($sourceIsConstruction && ($sourceNode->metadata['is_from_l5_feedback'] ?? false)) {
                return ['blue', 'bold', 'compose'];
            }

            return ['blue', 'solid', ''];
        }

        // L5 -> L23 (prediction or feedback - Circuit 2)
        if ($sourceLayer === 'L5' && $targetLayer === 'L23') {
            // Check if target is a construction node from feedback (Circuit 2B)
            $targetIsConstruction = ($targetNode->metadata['node_type'] ?? '') === 'construction';
            if ($targetIsConstruction && ($targetNode->metadata['is_from_l5_feedback'] ?? false)) {
                return ['purple', 'bold', 'feedback'];
            }

            // Forward prediction (Circuit 2A)
            if ($targetPos > $sourcePos) {
                return ['orange', 'dashed', 'predict'];
            }

            // Prediction link: if target is still marked as predicted (unconfirmed),
            // this link was created as a prediction, regardless of source's current state
            if ($targetIsPredicted) {
                return ['orange', 'dotted', 'prediction'];
            }

            // Backward confirmation
            return ['purple', 'dotted', 'feedback'];
        }

        // L23 -> L23 (lateral)
        if ($sourceLayer === 'L23' && $targetLayer === 'L23') {
            // NEW: Backward confirmation (from next column to predicted node in previous)
            if ($targetPos < $sourcePos && $targetIsPredicted) {
                return ['purple', 'bold', 'back-confirm'];
            }

            return ['green', 'solid', 'lateral'];
        }

        // L5 -> L5 (construction linking)
        if ($sourceLayer === 'L5' && $targetLayer === 'L5') {
            return ['red', 'solid', ''];
        }

        // Default
        return ['gray', 'solid', ''];
    }

    /**
     * Save DOT output to file
     *
     * @param  string  $dot  DOT format string
     * @param  string  $filepath  Output file path
     * @return bool Success
     */
    public function saveToFile(string $dot, string $filepath): bool
    {
        return file_put_contents($filepath, $dot) !== false;
    }

    /**
     * Generate and save DOT file, then optionally compile to image
     *
     * @param  SequenceResult  $result  Parse result
     * @param  array  $columns  Array of CLNColumn objects
     * @param  string  $outputPath  Output file path (without extension)
     * @param  array  $options  Export options
     * @return array ['dot_file' => path, 'success' => bool]
     */
    public function exportToFile(SequenceResult $result, array $columns, string $outputPath, array $options = []): array
    {
        $dot = $this->export($result, $columns, $options);
        $dotFile = $outputPath.'.dot';

        $success = $this->saveToFile($dot, $dotFile);

        return [
            'dot_file' => $dotFile,
            'success' => $success,
        ];
    }
}

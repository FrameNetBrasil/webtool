<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Completion Detector for CLN v3
 *
 * Detects when L2 nodes have reached the END of their pattern sequence,
 * marking them as completed constructions and extracting semantic bindings.
 */
class CompletionDetector
{
    public function __construct(
        private PatternGraphQuerier $querier
    ) {}

    /**
     * Detect and mark completed constructions in the graph
     *
     * A construction is completed when:
     * 1. It's an L2 node (composed construction)
     * 2. It has no predicted_element (already reached END)
     * 3. OR its next element in the pattern is END
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of completed construction information
     */
    public function detectCompletions(RuntimeGraph $graph): array
    {
        $completions = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            // Skip if already marked as completed
            if ($l2Node->is_completed) {
                continue;
            }

            // Check if this node is completed
            if ($this->isCompleted($l2Node)) {
                // Mark as completed
                $l2Node->is_completed = true;

                // Extract semantic bindings
                $semanticBindings = $this->extractSemanticBindings($l2Node, $graph);

                $completions[] = [
                    'node_id' => $l2Node->id,
                    'construction_type' => $l2Node->construction_type,
                    'span' => $l2Node->span,
                    'activation' => $l2Node->activation,
                    'pattern_id' => $l2Node->bindings['pattern_id'] ?? null,
                    'bindings' => $semanticBindings,
                    'features' => $l2Node->features,
                ];
            }
        }

        return $completions;
    }

    /**
     * Check if an L2 node has completed its pattern
     *
     * RNT Completion Logic:
     * - Single-element constructions (DATA→OR): Complete immediately
     * - Complete AND compositions (AND→OR): Complete immediately
     * - Partial AND compositions: NOT complete (awaiting right operand)
     *
     * CLN v3 Completion Logic:
     * - No predicted_element: Pattern reached END
     * - Next element is END: Construction complete
     *
     * @param  Column  $l2Node  L2 node to check
     * @return bool True if completed
     */
    public function isCompleted(Column $l2Node): bool
    {
        // Only L2 nodes can be completed constructions
        if ($l2Node->cortical_level !== 'L2') {
            return false;
        }

        // === RNT Completion Logic ===
        if ($l2Node->isRNTConstruction()) {
            // Single-element constructions: complete immediately
            if ($l2Node->isSingleElementRNT()) {
                return true;
            }

            // Complete AND compositions: complete immediately
            if ($l2Node->isCompleteAnd()) {
                return true;
            }

            // Partial AND compositions: NOT complete (awaiting completion)
            if ($l2Node->isPartialAnd()) {
                return false;
            }

            // Unknown RNT status - treat as incomplete
            return false;
        }

        // === CLN v3 Completion Logic (Backward Compatibility) ===

        // Case 1: No predicted_element means pattern already reached END
        if ($l2Node->predicted_element === null) {
            return true;
        }

        // Case 2: Check if next element in pattern is END
        $prediction = $l2Node->predicted_element;
        $nodeId = $prediction['node_id'] ?? null;
        $patternId = $l2Node->bindings['pattern_id'] ?? null;

        if ($nodeId && $patternId) {
            $nextInfo = $this->querier->queryNextInPattern($nodeId, $patternId);

            // If next step is END, this construction is complete
            if ($nextInfo && $nextInfo->node_type === 'END') {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract semantic bindings from a completed construction
     *
     * Traces through the construction's constituent nodes to extract
     * all semantic role bindings (CE labels and their fillers).
     *
     * @param  Column  $l2Node  Completed L2 node
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Semantic bindings array
     */
    private function extractSemanticBindings(Column $l2Node, RuntimeGraph $graph): array
    {
        $bindings = [];

        // Get pattern sequence to understand CE labels
        $patternId = $l2Node->bindings['pattern_id'] ?? null;
        if ($patternId === null) {
            return $bindings;
        }

        // Get full pattern sequence
        $sequence = $this->querier->getPatternSequence($patternId);

        // Build CE label map from pattern
        $ceLabelMap = [];
        foreach ($sequence as $step) {
            if (isset($step->ce_label) && $step->ce_label !== null) {
                $ceLabel = is_string($step->ce_label)
                    ? json_decode($step->ce_label, true)
                    : $step->ce_label;

                if ($ceLabel) {
                    $ceLabelMap[$step->sequence] = $ceLabel;
                }
            }
        }

        // Recursively extract bindings from constituent nodes
        $constituentNodes = $this->getConstituentNodes($l2Node, $graph);

        // Map constituent nodes to CE labels based on sequence order
        $sequenceIndex = 0;
        foreach ($constituentNodes as $node) {
            if (isset($ceLabelMap[$sequenceIndex])) {
                $ceLabel = $ceLabelMap[$sequenceIndex];
                $filler = $this->extractFillerValue($node, $graph);

                $bindings[$ceLabel] = [
                    'filler' => $filler,
                    'node_id' => $node->id,
                    'span' => $node->span,
                ];
            }
            $sequenceIndex++;
        }

        return $bindings;
    }

    /**
     * Get all constituent nodes that compose an L2 node
     *
     * Recursively traverses the construction hierarchy to get all L1 nodes.
     *
     * Supports both CLN v3 and RNT bindings:
     * - CLN v3: 'source', 'first', 'second'
     * - RNT: 'left_operand', 'right_operand'
     *
     * @param  Column  $node  Node to decompose
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of constituent nodes (L1 or L2)
     */
    private function getConstituentNodes(Column $node, RuntimeGraph $graph): array
    {
        $constituents = [];

        // Handle single-element compositions (source binding)
        $sourceId = $node->bindings['source'] ?? null;
        if ($sourceId) {
            $sourceNode = $graph->getNode($sourceId);
            if ($sourceNode) {
                if ($sourceNode->cortical_level === 'L1') {
                    $constituents[] = $sourceNode;
                } else {
                    // Recursively get constituents of L2 node
                    $constituents = array_merge(
                        $constituents,
                        $this->getConstituentNodes($sourceNode, $graph)
                    );
                }
            }

            return $constituents;
        }

        // Handle paired compositions (first + second bindings for CLN v3)
        $firstId = $node->bindings['first'] ?? null;
        $secondId = $node->bindings['second'] ?? null;

        // Handle RNT paired compositions (left_operand + right_operand)
        $leftId = $node->bindings['left_operand'] ?? null;
        $rightId = $node->bindings['right_operand'] ?? null;

        // Determine which binding style to use
        $leftOperandId = $leftId ?? $firstId;
        $rightOperandId = $rightId ?? $secondId;

        if ($leftOperandId) {
            $leftNode = $graph->getNode($leftOperandId);
            if ($leftNode) {
                if ($leftNode->cortical_level === 'L1') {
                    $constituents[] = $leftNode;
                } else {
                    // Recursively get constituents of L2 node
                    $constituents = array_merge(
                        $constituents,
                        $this->getConstituentNodes($leftNode, $graph)
                    );
                }
            }
        }

        if ($rightOperandId) {
            $rightNode = $graph->getNode($rightOperandId);
            if ($rightNode) {
                if ($rightNode->cortical_level === 'L1') {
                    $constituents[] = $rightNode;
                } else {
                    // Recursively get constituents of L2 node
                    $constituents = array_merge(
                        $constituents,
                        $this->getConstituentNodes($rightNode, $graph)
                    );
                }
            }
        }

        return $constituents;
    }

    /**
     * Extract the semantic filler value from a node
     *
     * @param  Column  $node  Node to extract from
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return string|array Filler value
     */
    private function extractFillerValue(Column $node, RuntimeGraph $graph): string|array
    {
        // For L1 literal nodes, return the word
        if ($node->cortical_level === 'L1' && $node->construction_type === 'literal') {
            return $node->features['value'] ?? $node->id;
        }

        // For L2 nodes, return construction type and constituents
        if ($node->cortical_level === 'L2') {
            return [
                'construction' => $node->construction_type,
                'span' => $node->span,
                'constituents' => array_map(
                    fn ($n) => $n->id,
                    $this->getConstituentNodes($node, $graph)
                ),
            ];
        }

        return $node->id;
    }

    /**
     * Get all completed constructions from the graph
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of completed construction nodes
     */
    public function getCompletedConstructions(RuntimeGraph $graph): array
    {
        $completed = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            if ($l2Node->is_completed) {
                $completed[] = $l2Node;
            }
        }

        return $completed;
    }

    /**
     * Check if any completions occurred in this timestep
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $previouslyCompleted  Array of previously completed node IDs
     * @return array New completions that occurred
     */
    public function getNewCompletions(RuntimeGraph $graph, array $previouslyCompleted): array
    {
        $allCompletions = $this->detectCompletions($graph);
        $newCompletions = [];

        foreach ($allCompletions as $completion) {
            if (! in_array($completion['node_id'], $previouslyCompleted)) {
                $newCompletions[] = $completion;
            }
        }

        return $newCompletions;
    }
}

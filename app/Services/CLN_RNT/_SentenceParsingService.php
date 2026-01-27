<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\RuntimeGraph;

/**
 * SentenceParsingService
 *
 * Handles sentence parsing (inference) on a learned pattern graph.
 * Activates patterns without modifying weights (no Hebbian learning).
 *
 * Key constraints:
 * - Head node must be active for SEQUENCER to activate
 * - Only activated nodes are shown in output graphs
 */
class SentenceParsingService
{
    public function __construct(
        private ActivationDynamicsLearning $activationDynamics
    ) {}

    /**
     * Parse a sentence and return only activated nodes/patterns
     *
     * @param  RuntimeGraph  $graph  The learned pattern graph
     * @param  array  $wordData  Parsed word data for the sentence
     * @param  array  $seqColumnsL1  L1 SeqColumns (POS-level)
     * @param  array  $seqColumnsL2  L2 SeqColumns (trigram-level)
     * @param  float  $activationThreshold  Minimum activation to consider a node "active"
     * @return array Parsing result with activated patterns and state
     */
    public function parseSentence(
        RuntimeGraph $graph,
        array $wordData,
        array $seqColumnsL1,
        array $seqColumnsL2 = [],
        float $activationThreshold = 0.5
    ): array {
        // Reset activations
        $this->activationDynamics->resetActivations($graph, $seqColumnsL1, $seqColumnsL2);

        // Process sentence through activation dynamics (without learning)
        $result = $this->activationDynamics->processSentence(
            $graph,
            $wordData,
            $seqColumnsL1,
            $seqColumnsL2,
            false // Do NOT apply Hebbian learning
        );

        // Apply head-gating constraint: if head is not active, deactivate SEQUENCER
        $this->applyHeadGating($seqColumnsL1, $activationThreshold);
        $this->applyHeadGating($seqColumnsL2, $activationThreshold);

        // Collect activated patterns
        $activatedPatterns = $this->collectActivatedPatterns(
            $graph,
            $seqColumnsL1,
            $seqColumnsL2,
            $activationThreshold
        );

        return [
            'activated_words' => $result['active_words'],
            'activated_patterns' => $activatedPatterns,
            'activation_threshold' => $activationThreshold,
        ];
    }

    /**
     * Apply head-gating constraint to SeqColumns
     *
     * Rules:
     * - L1 (POS columns): Head node must be active
     * - L2 (trigram columns): Head AND at least one left AND at least one right must be active
     *
     * @param  array  $seqColumns  SeqColumns to process
     * @param  float  $threshold  Activation threshold
     */
    private function applyHeadGating(array $seqColumns, float $threshold): void
    {
        foreach ($seqColumns as $column) {
            $isL2Column = isset($column->features['level']) && $column->features['level'] === 2;

            if ($isL2Column) {
                // For L2 trigram patterns: require all three positions active
                $headActive = $column->h_node->activation >= $threshold;
                $anyLeftActive = $this->hasAnyNodeActive($column->getLeftNodes(), $threshold);
                $anyRightActive = $this->hasAnyNodeActive($column->getRightNodes(), $threshold);

                // Deactivate SEQUENCER if trigram is incomplete
                if (! $headActive || ! $anyLeftActive || ! $anyRightActive) {
                    $column->s_node->activation = 0.0;
                }
            } else {
                // For L1 POS columns: only head needs to be active
                if ($column->h_node->activation < $threshold) {
                    $column->s_node->activation = 0.0;
                }
            }
        }
    }

    /**
     * Check if any node in the array has activation above threshold
     *
     * @param  array  $nodes  Array of Column objects
     * @param  float  $threshold  Activation threshold
     * @return bool True if at least one node is active
     */
    private function hasAnyNodeActive(array $nodes, float $threshold): bool
    {
        foreach ($nodes as $node) {
            if ($node->activation >= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect all activated patterns (nodes and columns above threshold)
     *
     * @param  RuntimeGraph  $graph  The pattern graph
     * @param  array  $seqColumnsL1  L1 SeqColumns
     * @param  array  $seqColumnsL2  L2 SeqColumns
     * @param  float  $threshold  Activation threshold
     * @return array Activated patterns categorized by level
     */
    private function collectActivatedPatterns(
        RuntimeGraph $graph,
        array $seqColumnsL1,
        array $seqColumnsL2,
        float $threshold
    ): array {
        $patterns = [
            'l1_sequencers' => [],
            'l2_sequencers' => [],
        ];

        // Collect activated L1 SEQUENCER patterns
        foreach ($seqColumnsL1 as $column) {
            if ($column->s_node->activation >= $threshold) {
                $patterns['l1_sequencers'][] = [
                    'pos_tag' => $column->features['pos_tag'] ?? 'unknown',
                    'activation' => $column->s_node->activation,
                ];
            }
        }

        // Collect activated L2 SEQUENCER patterns
        foreach ($seqColumnsL2 as $column) {
            if ($column->s_node->activation >= $threshold) {
                $patterns['l2_sequencers'][] = [
                    'trigram' => $column->features['trigram'] ?? 'unknown',
                    'activation' => $column->s_node->activation,
                ];
            }
        }

        return $patterns;
    }

    /**
     * Generate DOT format graph showing only activated nodes and patterns
     *
     * @param  RuntimeGraph  $graph  The pattern graph
     * @param  array  $seqColumnsL1  L1 SeqColumns
     * @param  array  $seqColumnsL2  L2 SeqColumns
     * @param  array  $metadata  Additional metadata for graph label
     * @param  float  $threshold  Activation threshold
     * @return string DOT format graph content
     */
    public function generateParsingDOT(
        RuntimeGraph $graph,
        array $seqColumnsL1,
        array $seqColumnsL2,
        array $metadata,
        float $threshold
    ): string {
        $dot = [];
        $dot[] = 'digraph ParsingResult {';
        $dot[] = '  rankdir=LR;';
        $dot[] = '  node [shape=box, style=filled];';
        $dot[] = '';

        // Graph label with metadata
        $timestamp = $metadata['timestamp'] ?? 'unknown';
        $sentence = $metadata['sentence'] ?? 'unknown';
        $thresholdValue = $metadata['threshold'] ?? $threshold;
        $dot[] = sprintf(
            '  label="Parsing Result\\nSentence: %s\\nTimestamp: %s\\nThreshold: %.2f\\nShowing: Only activated patterns";',
            addslashes($sentence),
            $timestamp,
            $thresholdValue
        );
        $dot[] = '  labelloc=t;';
        $dot[] = '';

        // Get IDs of activated SeqColumn nodes
        $activatedL1NodeIds = $this->getActivatedColumnNodeIds($seqColumnsL1, $threshold);
        $activatedL2NodeIds = $this->getActivatedColumnNodeIds($seqColumnsL2, $threshold);
        $allActivatedColumnNodeIds = array_merge($activatedL1NodeIds, $activatedL2NodeIds);

        // Export only activated L1 POS nodes
        $dot[] = '  // Activated L1 POS nodes';
        $l1Nodes = $graph->getNodesByLevel('L1');
        foreach ($l1Nodes as $node) {
            // Skip literal word nodes
            if ($node->construction_type === 'literal') {
                continue;
            }

            // Only show if activated
            if ($node->activation >= $threshold) {
                $color = $this->getNodeColor($node->activation, $threshold);
                $dot[] = sprintf(
                    '  "%s" [label="%s\\n(%.2f)", fillcolor="%s"];',
                    $node->id,
                    $node->construction_type,
                    $node->activation,
                    $color
                );
            }
        }
        $dot[] = '';

        // Export activated SeqColumns
        $dot[] = '  // Activated L1 SeqColumns';
        $dot[] = $this->generateActivatedSeqColumnsDOT($seqColumnsL1, $threshold, 'L1');
        $dot[] = '';

        $dot[] = '  // Activated L2 SeqColumns';
        $dot[] = $this->generateActivatedSeqColumnsDOT($seqColumnsL2, $threshold, 'L2');
        $dot[] = '';

        // Export edges (only between activated nodes)
        $dot[] = '  // Edges between activated nodes';
        foreach ($graph->getAllNodes() as $node) {
            // Skip literal nodes
            if ($node->construction_type === 'literal') {
                continue;
            }

            // Skip inactive nodes
            if ($node->activation < $threshold && ! in_array($node->id, $allActivatedColumnNodeIds)) {
                continue;
            }

            $edges = $graph->getEdges($node->id);
            foreach ($edges as $edge) {
                if ($edge->type === 'inhibitory') {
                    continue;
                }

                // Skip edges to literal nodes
                $targetNode = $graph->getNode($edge->target);
                if ($targetNode && $targetNode->construction_type === 'literal') {
                    continue;
                }

                // Check if target is activated (either in graph or in SeqColumns)
                $targetInGraph = $targetNode && $targetNode->activation >= $threshold;
                $targetInColumns = in_array($edge->target, $allActivatedColumnNodeIds);

                if ($targetInGraph || $targetInColumns) {
                    $color = $edge->weight > 1.0 ? 'blue' : 'gray';
                    $penwidth = min(1 + ($edge->weight - 1.0) * 2, 5);
                    $dot[] = sprintf(
                        '  "%s" -> "%s" [label="%.2f", color="%s", penwidth=%.1f];',
                        $node->id,
                        $edge->target,
                        $edge->weight,
                        $color,
                        $penwidth
                    );
                }
            }
        }

        $dot[] = '}';

        return implode("\n", $dot);
    }

    /**
     * Get IDs of all nodes within activated SeqColumns
     *
     * @param  array  $seqColumns  SeqColumns to process
     * @param  float  $threshold  Activation threshold
     * @return array Node IDs
     */
    private function getActivatedColumnNodeIds(array $seqColumns, float $threshold): array
    {
        $nodeIds = [];

        foreach ($seqColumns as $column) {
            if ($column->s_node->activation >= $threshold) {
                // Add all internal node IDs
                $nodeIds[] = $column->h_node->id;
                $nodeIds[] = $column->s_node->id;

                foreach ($column->getLeftNodes() as $leftNode) {
                    $nodeIds[] = $leftNode->id;
                }

                foreach ($column->getRightNodes() as $rightNode) {
                    $nodeIds[] = $rightNode->id;
                }
            }
        }

        return $nodeIds;
    }

    /**
     * Generate DOT representation for activated SeqColumns
     *
     * @param  array  $seqColumns  SeqColumns to render
     * @param  float  $threshold  Activation threshold
     * @param  string  $level  Level label (L1 or L2)
     * @return string DOT content for columns
     */
    private function generateActivatedSeqColumnsDOT(array $seqColumns, float $threshold, string $level): string
    {
        $dot = [];

        foreach ($seqColumns as $column) {
            // Only show activated columns
            if ($column->s_node->activation < $threshold) {
                continue;
            }

            $columnLabel = $column->features['pos_tag'] ?? $column->features['trigram'] ?? 'unknown';

            $dot[] = sprintf('  subgraph cluster_%s {', $column->id);
            $dot[] = sprintf('    label="%s SeqColumn: %s";', $level, $columnLabel);
            $dot[] = '    style=dashed;';
            $dot[] = '    color=blue;';
            $dot[] = '';

            // L23 layer
            $dot[] = '    // L23 (left + head + right)';

            foreach ($column->getLeftNodes() as $leftNode) {
                $color = $this->getNodeColor($leftNode->activation, $threshold);
                $dot[] = sprintf(
                    '    "%s" [label="L\\n(%.2f)", fillcolor="%s"];',
                    $leftNode->id,
                    $leftNode->activation,
                    $color
                );
            }

            $color = $this->getNodeColor($column->h_node->activation, $threshold);
            $dot[] = sprintf(
                '    "%s" [label="H\\n(%.2f)", fillcolor="%s"];',
                $column->h_node->id,
                $column->h_node->activation,
                $color
            );

            foreach ($column->getRightNodes() as $rightNode) {
                $color = $this->getNodeColor($rightNode->activation, $threshold);
                $dot[] = sprintf(
                    '    "%s" [label="R\\n(%.2f)", fillcolor="%s"];',
                    $rightNode->id,
                    $rightNode->activation,
                    $color
                );
            }

            $dot[] = '';

            // L5 SEQUENCER
            $dot[] = '    // L5 SEQUENCER';
            $color = $this->getNodeColor($column->s_node->activation, $threshold);
            $dot[] = sprintf(
                '    "%s" [label="SEQ\\n(%.2f)", fillcolor="%s", shape=ellipse];',
                $column->s_node->id,
                $column->s_node->activation,
                $color
            );

            $dot[] = '  }';
            $dot[] = '';
        }

        return implode("\n", $dot);
    }

    /**
     * Get color for node based on activation level
     *
     * @param  float  $activation  Node activation value
     * @param  float  $threshold  Activation threshold
     * @return string Color name
     */
    private function getNodeColor(float $activation, float $threshold): string
    {
        if ($activation >= $threshold) {
            return 'lightgreen';
        }

        return 'lightgray';
    }
}

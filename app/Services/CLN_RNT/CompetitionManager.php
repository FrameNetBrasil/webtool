<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\ConnectionEdge;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Competition Manager for CLN v3
 *
 * Manages competition between overlapping L2 nodes using lateral inhibition.
 *
 * Key principles:
 * - L2 nodes with overlapping spans compete for activation
 * - Competition implemented via lateral-inhib edges driving PV interneurons
 * - Stronger nodes inhibit weaker overlapping nodes
 * - Winner-take-all dynamics emerge from activation differences
 */
class CompetitionManager
{
    /**
     * Establish competitive connections between overlapping L2 nodes
     *
     * Creates bidirectional lateral-inhib edges between L2 nodes whose
     * spans overlap. The activation dynamics will use these connections
     * to implement competition via PV-mediated inhibition.
     *
     * IMPORTANT: Nodes do NOT compete if they are semantically related:
     * - Parent-child composition relationships (constituents)
     * - Alternative representations (OR→OR chains)
     * - Predictions (partial AND predicting OR)
     * - Same construction at same span (duplicates)
     *
     * Competition is ONLY established between genuinely competing interpretations.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return int Number of competitive edges added
     */
    public function establishCompetition(RuntimeGraph $graph): int
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $edgesAdded = 0;

        // Find all pairs of L2 nodes that represent actual constructions (OR nodes)
        foreach ($l2Nodes as $node1) {
            // Only OR nodes compete (single-element or complete AND compositions)
            // Partial AND and intermediate AND nodes are predictions/building blocks, not constructions
            if (! $this->isActualConstruction($node1)) {
                continue;
            }

            foreach ($l2Nodes as $node2) {
                if ($node1->id === $node2->id) {
                    continue; // Skip self
                }

                // Only compete with other actual constructions
                if (! $this->isActualConstruction($node2)) {
                    continue;
                }

                // Skip if nodes are semantically related (not true competitors)
                if ($this->areRelated($node1, $node2)) {
                    continue;
                }

                // Only compete if spans are EXACTLY the same (not just overlapping)
                // This prevents explosion of competition edges while ensuring
                // that genuinely ambiguous interpretations at the same position compete
                if ($node1->span === $node2->span) {
                    // Check if edge already exists
                    $existingEdges = $graph->getEdges($node1->id);
                    $alreadyConnected = false;

                    foreach ($existingEdges as $edge) {
                        if ($edge->target === $node2->id && $edge->type === 'lateral-inhib') {
                            $alreadyConnected = true;
                            break;
                        }
                    }

                    if (! $alreadyConnected) {
                        // Create lateral inhibitory edge
                        // Since spans are exactly the same, use fixed strong inhibition weight
                        // These are true competitors for the same interpretation
                        $inhibWeight = 2.5;

                        $graph->addEdge(new ConnectionEdge(
                            source: $node1->id,
                            target: $node2->id,
                            type: 'lateral-inhib',
                            weight: $inhibWeight
                        ));

                        $edgesAdded++;
                    }
                }
            }
        }

        return $edgesAdded;
    }

    /**
     * Check if a node represents an actual construction (OR node)
     *
     * Only OR nodes compete. AND nodes (partial, intermediate, or complete)
     * are composition mechanisms and should not participate in competition.
     *
     * In the RNT pattern graph, constructions are identified by OR nodes.
     * Complete AND nodes like MOD_HEAD don't have their own OR nodes
     * (would be redundant), so they should not compete.
     *
     * @param  Column  $node  Node to check
     * @return bool True if node represents an actual construction
     */
    private function isActualConstruction(Column $node): bool
    {
        // RNT constructions: Only single-element constructions have OR nodes
        if ($node->isRNTConstruction()) {
            return $node->isSingleElementRNT();
        }

        // Non-RNT L2 nodes (legacy CLN) are considered constructions
        return $node->cortical_level === 'L2';
    }

    /**
     * Check if node2 is a constituent of node1
     *
     * Nodes should not compete if node2 is a constituent of node1 (composition relationship).
     *
     * @param  Column  $node1  Parent node
     * @param  Column  $node2  Potential constituent
     * @return bool True if node2 is a constituent
     */
    private function isConstituent(Column $node1, Column $node2): bool
    {
        $firstId = $node1->bindings['first'] ?? null;
        $secondId = $node1->bindings['second'] ?? null;
        $sourceId = $node1->bindings['source'] ?? null;
        $leftOperandId = $node1->bindings['left_operand'] ?? null;
        $rightOperandId = $node1->bindings['right_operand'] ?? null;

        return $node2->id === $firstId
            || $node2->id === $secondId
            || $node2->id === $sourceId
            || $node2->id === $leftOperandId
            || $node2->id === $rightOperandId;
    }

    /**
     * Check if two nodes are semantically related (should not compete)
     *
     * Nodes are related if they:
     * 1. Have a constituent-parent relationship
     * 2. Are alternative representations (OR→OR chain)
     * 3. Have a prediction relationship (partial AND predicting OR)
     * 4. Represent the same construction at the same span
     *
     * @param  Column  $node1  First node
     * @param  Column  $node2  Second node
     * @return bool True if nodes are related (should not compete)
     */
    private function areRelated(Column $node1, Column $node2): bool
    {
        // 1. Constituent relationships (bidirectional)
        if ($this->isConstituent($node1, $node2) || $this->isConstituent($node2, $node1)) {
            return true;
        }

        // 2. Alternative representations (OR→OR chains)
        if ($this->areAlternatives($node1, $node2)) {
            return true;
        }

        // 3. Prediction relationships
        if ($this->hasPredictionRelation($node1, $node2)) {
            return true;
        }

        // 4. Same construction at exact same span (duplicates)
        if ($node1->span === $node2->span &&
            $node1->construction_type === $node2->construction_type) {
            return true;
        }

        // 5. RNT-specific: Same OR node at same span
        if ($node1->isRNTConstruction() &&
            $node2->isRNTConstruction() &&
            $node1->span === $node2->span &&
            $node1->rnt_or_node_id !== null &&
            $node1->rnt_or_node_id === $node2->rnt_or_node_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if two nodes are alternative representations
     *
     * Nodes are alternatives if one was created from the other via OR→OR edge.
     *
     * @param  Column  $node1  First node
     * @param  Column  $node2  Second node
     * @return bool True if nodes are alternatives
     */
    private function areAlternatives(Column $node1, Column $node2): bool
    {
        // Direct alternative: node2 created from node1
        $sourceOrNode1 = $node1->bindings['source_or_node'] ?? null;
        $sourceOrNode2 = $node2->bindings['source_or_node'] ?? null;

        if ($sourceOrNode1 === $node2->id || $sourceOrNode2 === $node1->id) {
            return true;
        }

        // Transitive alternatives: both created from same source
        if ($sourceOrNode1 !== null && $sourceOrNode1 === $sourceOrNode2) {
            return true;
        }

        // Check if they share the same alternative_of value
        $alternativeOf1 = $node1->bindings['alternative_of'] ?? null;
        $alternativeOf2 = $node2->bindings['alternative_of'] ?? null;

        if ($alternativeOf1 !== null && $alternativeOf1 === $alternativeOf2) {
            return true;
        }

        // Check if one is alternative_of the other's OR node
        if ($alternativeOf1 !== null && $alternativeOf1 === $node2->rnt_or_node_id) {
            return true;
        }

        if ($alternativeOf2 !== null && $alternativeOf2 === $node1->rnt_or_node_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if two nodes have a prediction relationship
     *
     * Nodes have prediction relationship if one is a partial AND predicting
     * the OR that the other represents.
     *
     * @param  Column  $node1  First node
     * @param  Column  $node2  Second node
     * @return bool True if nodes have prediction relationship
     */
    private function hasPredictionRelation(Column $node1, Column $node2): bool
    {
        // Check if node1 is partial AND predicting node2's OR
        if ($node1->isPartialAnd()) {
            $expectedRight = $node1->rnt_expected_right;
            $expectedLeft = $node1->rnt_expected_left;

            if ($expectedRight !== null) {
                $expectedOrId = $expectedRight['or_node_id'] ?? null;
                if ($expectedOrId !== null && $node2->rnt_or_node_id === $expectedOrId) {
                    return true;
                }
            }

            if ($expectedLeft !== null) {
                $expectedOrId = $expectedLeft['or_node_id'] ?? null;
                if ($expectedOrId !== null && $node2->rnt_or_node_id === $expectedOrId) {
                    return true;
                }
            }
        }

        // Check reverse: node2 predicting node1
        if ($node2->isPartialAnd()) {
            $expectedRight = $node2->rnt_expected_right;
            $expectedLeft = $node2->rnt_expected_left;

            if ($expectedRight !== null) {
                $expectedOrId = $expectedRight['or_node_id'] ?? null;
                if ($expectedOrId !== null && $node1->rnt_or_node_id === $expectedOrId) {
                    return true;
                }
            }

            if ($expectedLeft !== null) {
                $expectedOrId = $expectedLeft['or_node_id'] ?? null;
                if ($expectedOrId !== null && $node1->rnt_or_node_id === $expectedOrId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if two spans overlap
     *
     * Spans overlap if they share any positions.
     *
     * @param  array  $span1  First span [start, end]
     * @param  array  $span2  Second span [start, end]
     * @return bool True if spans overlap
     */
    private function spansOverlap(array $span1, array $span2): bool
    {
        // No overlap if one span ends before the other starts
        if ($span1[1] < $span2[0] || $span2[1] < $span1[0]) {
            return false;
        }

        return true;
    }

    /**
     * Calculate overlap size between two spans
     *
     * Returns the number of positions that overlap between two spans.
     *
     * @param  array  $span1  First span [start, end]
     * @param  array  $span2  Second span [start, end]
     * @return int Number of overlapping positions
     */
    private function calculateOverlap(array $span1, array $span2): int
    {
        $overlapStart = max($span1[0], $span2[0]);
        $overlapEnd = min($span1[1], $span2[1]);

        if ($overlapStart > $overlapEnd) {
            return 0;
        }

        return $overlapEnd - $overlapStart + 1;
    }

    /**
     * Get all L2 nodes competing for a given span
     *
     * Returns all L2 nodes whose spans overlap with the given span,
     * sorted by activation (strongest first).
     *
     * IMPORTANT: Only returns true competitors - excludes constituent-parent relationships.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $span  Span to check [start, end]
     * @return array Array of competing Column objects
     */
    public function getCompetitors(RuntimeGraph $graph, array $span): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $competitors = [];

        // First, find the node with this exact span (if any)
        $targetNode = null;
        foreach ($l2Nodes as $node) {
            if ($node->span === $span) {
                $targetNode = $node;
                break;
            }
        }

        foreach ($l2Nodes as $node) {
            if ($this->spansOverlap($node->span, $span)) {
                // Exclude constituent-parent relationships
                if ($targetNode !== null) {
                    if ($this->isConstituent($targetNode, $node) || $this->isConstituent($node, $targetNode)) {
                        continue;
                    }
                }

                $competitors[] = $node;
            }
        }

        // Sort by activation (strongest first)
        usort($competitors, fn ($a, $b) => $b->activation <=> $a->activation);

        return $competitors;
    }

    /**
     * Get winning node for a given span
     *
     * Returns the L2 node with highest activation that covers the span.
     * Returns null if no L2 nodes cover the span.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $span  Span to check [start, end]
     * @return Column|null Winning node or null
     */
    public function getWinner(RuntimeGraph $graph, array $span): ?Column
    {
        $competitors = $this->getCompetitors($graph, $span);

        return $competitors[0] ?? null;
    }

    /**
     * Prune losing nodes below activation threshold
     *
     * Removes L2 nodes whose activation has fallen below a threshold,
     * indicating they lost the competition.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  float  $threshold  Activation threshold (default 0.1)
     * @return int Number of nodes pruned
     */
    public function pruneLosers(RuntimeGraph $graph, float $threshold = 0.1): int
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $pruned = 0;

        foreach ($l2Nodes as $node) {
            if ($node->activation < $threshold) {
                $graph->removeNode($node->id);
                $pruned++;
            }
        }

        return $pruned;
    }

    /**
     * Prune L2 nodes that clearly lost in competition
     *
     * More sophisticated pruning that considers:
     * 1. Activation threshold (absolute minimum)
     * 2. Competitive context (activation gap from winner)
     * 3. Completion status (preserve completed constructions above minimum)
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  float  $absoluteThreshold  Absolute minimum activation (default 0.05)
     * @param  float  $competitiveGap  Required gap from winner to prune (default 0.3)
     * @param  bool  $preserveCompleted  Keep completed constructions (default true)
     * @return array Pruning statistics
     */
    public function pruneCompetitionLosers(
        RuntimeGraph $graph,
        float $absoluteThreshold = 0.05,
        float $competitiveGap = 0.3,
        bool $preserveCompleted = true
    ): array {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $prunedByThreshold = 0;
        $prunedByCompetition = 0;
        $preservedCompleted = 0;

        foreach ($l2Nodes as $node) {
            // Never prune if below absolute minimum (clearly failed)
            if ($node->activation < $absoluteThreshold) {
                // Exception: preserve if completed and flag is set
                if ($preserveCompleted && $node->is_completed && $node->activation > 0.01) {
                    $preservedCompleted++;

                    continue;
                }

                $graph->removeNode($node->id);
                $prunedByThreshold++;

                continue;
            }

            // Check if node lost in competition
            $competitors = $this->getCompetitors($graph, $node->span);

            if (count($competitors) > 1) {
                $winner = $competitors[0]; // Highest activation

                // If this node is not the winner and gap is significant, prune it
                if ($winner->id !== $node->id) {
                    $gap = $winner->activation - $node->activation;

                    if ($gap >= $competitiveGap) {
                        // Exception: preserve if completed and flag is set
                        if ($preserveCompleted && $node->is_completed) {
                            $preservedCompleted++;

                            continue;
                        }

                        $graph->removeNode($node->id);
                        $prunedByCompetition++;
                    }
                }
            }
        }

        return [
            'pruned_by_threshold' => $prunedByThreshold,
            'pruned_by_competition' => $prunedByCompetition,
            'preserved_completed' => $preservedCompleted,
            'total_pruned' => $prunedByThreshold + $prunedByCompetition,
        ];
    }

    /**
     * Prune all losing competitors, keeping only winners
     *
     * Aggressive pruning that keeps only the highest-activation node
     * in each competitive group. Use this after competition has stabilized.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  bool  $preserveCompleted  Keep all completed constructions (default true)
     * @return array Pruning statistics
     */
    public function pruneAllLosers(RuntimeGraph $graph, bool $preserveCompleted = true): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $pruned = 0;
        $preserved = 0;
        $processedGroups = [];

        foreach ($l2Nodes as $node) {
            // Skip if already processed as part of a competitive group
            $spanKey = $node->getSpanString();
            if (isset($processedGroups[$spanKey])) {
                continue;
            }

            $competitors = $this->getCompetitors($graph, $node->span);

            if (count($competitors) > 1) {
                $winner = $competitors[0]; // Highest activation

                // Mark this competitive group as processed
                foreach ($competitors as $comp) {
                    $processedGroups[$comp->getSpanString()] = true;
                }

                // Prune all losers
                foreach ($competitors as $competitor) {
                    if ($competitor->id !== $winner->id) {
                        // Exception: preserve completed constructions
                        if ($preserveCompleted && $competitor->is_completed) {
                            $preserved++;

                            continue;
                        }

                        $graph->removeNode($competitor->id);
                        $pruned++;
                    }
                }
            }
        }

        return [
            'total_pruned' => $pruned,
            'preserved_completed' => $preserved,
        ];
    }

    /**
     * Get pruning candidates based on various criteria
     *
     * Returns information about nodes that could be pruned without
     * returning the full pruning decision. Useful for analysis.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  float  $threshold  Activation threshold
     * @return array Pruning candidate information
     */
    public function getPruningCandidates(RuntimeGraph $graph, float $threshold = 0.1): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $candidates = [];

        foreach ($l2Nodes as $node) {
            if ($node->activation < $threshold) {
                $competitors = $this->getCompetitors($graph, $node->span);
                $winner = $competitors[0] ?? null;

                $candidates[] = [
                    'node_id' => $node->id,
                    'activation' => $node->activation,
                    'span' => $node->span,
                    'is_completed' => $node->is_completed,
                    'num_competitors' => count($competitors),
                    'winner_id' => $winner?->id,
                    'gap_from_winner' => $winner ? ($winner->activation - $node->activation) : 0.0,
                ];
            }
        }

        return $candidates;
    }
}

<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\LearnGraph;
use App\Models\CLN_RNT\Node;
use App\Models\CLN_RNT\RuntimeGraph;
use App\Models\CLN_RNT\SeqColumn;

/**
 * ActivationDynamicsLearning
 *
 * Handles activation dynamics during learning time for the CLN architecture.
 * Unlike parsing-time activation (ActivationDynamics), this operates on the learned
 * pattern graph structure with SeqColumns and propagates activation including
 * inhibitory links for Winner-Take-All dynamics.
 *
 * Key differences from parsing-time:
 * - Works with SeqColumn structures instead of individual ConstructionNodes
 * - Activates words in a sentence and propagates through the pattern graph
 * - Handles PV inhibitory links for competitive dynamics
 * - Generates before/after visualizations showing activation flow
 */
class ActivationDynamicsLearning
{
    private const ACTIVATION_THRESHOLD = 0.5;

    private const MAX_ITERATIONS = 10;

    // Sequential processing parameters
    private const SEQUENTIAL_ITERATIONS = 5; // Fewer iterations per word

    private const ACTIVATION_DECAY = 0.6; // Decay factor for temporal context (0.6 = 40% decay)

    private const DT = 1.0;

    private const INHIBITION_STRENGTH = 0.5;

    // Hebbian learning parameters
    private const HEBBIAN_LEARNING_RATE = 0.05; // 0.1;

    private const HEBBIAN_COACTIVATION_THRESHOLD = 0.5;

    private const MAX_WEIGHT = 3.0;

    private LearnGraph $graph;

    private array $activeNodes = [];

    public function __construct(LearnGraph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Process a sentence through the learned graph
     *
     * Processes words sequentially (left to right), activating one word at a time.
     * Previous activations decay but remain, providing temporal context.
     * This allows patterns that appear multiple times to be reinforced proportionally.
     *
     * @param RuntimeGraph $graph The learned pattern graph
     * @param array $wordData Parsed word data for the sentence
     * @param array $seqColumnsL1 L1 SeqColumns (POS-level)
     * @param array $seqColumnsL2 L2 SeqColumns (trigram-level)
     * @param bool $applyLearning Whether to apply Hebbian learning (true for training, false for testing)
     * @return array State information before and after activation
     */
    public function processSentence(
//        RuntimeGraph $graph,
        array $wordData,
//        array $seqColumnsL1,
//        array $seqColumnsL2 = [],
//        array $seqColumnsL3 = [],
//        bool $applyLearning = true
    ): array
    {
        // Capture initial state (before activation)
        //$stateBefore = $this->captureState($graph, $seqColumnsL1, $seqColumnsL2);

        // Reset all graph nodes
//        foreach ($this->graph->getAllNodes() as $node) {
//            $node->activation = 0.0;
//        }

        // Process words sequentially (one at a time, left to right)
        $this->processWordsSequentially(
//            $graph,
            $wordData,
//            $seqColumnsL1,
//            $seqColumnsL2,
//            $seqColumnsL3,
//            $applyLearning
        );

        // Capture final state (after activation)
//        $stateAfter = $this->captureState($graph, $seqColumnsL1, $seqColumnsL2);

        return [
            'before' => null,//$stateBefore,
            'after' => null,//$stateAfter,
            'active_words' => [], // All words were processed
        ];
    }

    /**
     * Process words sequentially with temporal context
     *
     * Each word is activated one at a time (left to right).
     * After each word:
     * 1. Activate the current word
     * 2. Propagate activation through the network
     * 3. Apply Hebbian learning (if enabled)
     * 4. Apply decay to maintain temporal context
     *
     * This ensures patterns that appear multiple times get reinforced multiple times.
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $wordData Parsed word data for the sentence
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     * @param bool $applyLearning Whether to apply Hebbian learning
     */
    private function processWordsSequentially(
//        RuntimeGraph $graph,
        array $wordData,
//        array $seqColumnsL1,
//        array $seqColumnsL2,
//        array $seqColumnsL3,
//        bool $applyLearning
    ): void
    {
        $applyLearning = true;
        // Process each word in sequence
        //foreach ($wordData as $index => $data) {
        // 1. Activate the current word
        $activeNodes = $this->activateL1Nodes();

        // 2. Propagate activation through the network (shorter iterations for sequential processing)
        $this->propagateActivation(
//                $graph,
            $activeNodes, // Pass the activated word nodes
//                $seqColumnsL1,
//                $seqColumnsL2,
            false, // Don't apply learning during propagation
            self::SEQUENTIAL_ITERATIONS // Use fewer iterations per word
        );

        // 3. Apply Hebbian learning after each word (if enabled)
//            if ($applyLearning) {
//                //$this->applyHebbianLearning($graph, $seqColumnsL1, $seqColumnsL2, $seqColumnsL3);
//                $this->applyHebbianLearning();
//            }

        // 4. Apply temporal decay to all activations (but don't reset completely)
        // This maintains temporal context - previous words remain partially active
        //if ($index < count($wordData) - 1) { // Don't decay after the last word
        //$this->applyTemporalDecay($graph, $seqColumnsL1, $seqColumnsL2, $seqColumnsL3);
        //$this->applyTemporalDecay();
        //}
        //}
    }

    /**
     * Activate a single word node
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $data Single word data
     * @return array Array of activated nodes
     */
    private function activateL1Nodes(): array
    {
        $activatedNodes = [];
        // Find matching L1 literal nodes in the graph
        $l1Nodes = $this->graph->getL1Nodes();
        foreach ($l1Nodes as $node) {
            if ($node->metadata['type'] == 'LITERAL') {
                // Match by word or lemma
                $node->activation = 0.95;
                $activatedNodes[] = $node;
            }
        }
        return $activatedNodes;
    }

    /**
     * Apply temporal decay to all activations
     *
     * Reduces activation levels to maintain temporal context.
     * Previous words remain partially active, allowing sequential patterns
     * to be detected while newer activations dominate.
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     */
    private function applyTemporalDecay(
//        RuntimeGraph $graph,
//        array $seqColumnsL1,
//        array $seqColumnsL2,
//        array $seqColumnsL3
    ): void
    {
        // Decay all graph nodes
        foreach ($this->graph->getAllNodes() as $node) {
            $node->activation *= self::ACTIVATION_DECAY;
        }

//        // Decay all SeqColumn nodes
//        $allColumns = array_merge($seqColumnsL1, $seqColumnsL2, $seqColumnsL3);
//        foreach ($allColumns as $column) {
//            $column->h_node->activation *= self::ACTIVATION_DECAY;
//            $column->s_node->activation *= self::ACTIVATION_DECAY;
//
//            foreach ($column->getLeftNodes() as $leftNode) {
//                $leftNode->activation *= self::ACTIVATION_DECAY;
//            }
//
//            foreach ($column->getRightNodes() as $rightNode) {
//                $rightNode->activation *= self::ACTIVATION_DECAY;
//            }
//
//            foreach ($column->getPVLeftNodes() as $pvNode) {
//                $pvNode->activation *= self::ACTIVATION_DECAY;
//            }
//
//            foreach ($column->getPVRightNodes() as $pvNode) {
//                $pvNode->activation *= self::ACTIVATION_DECAY;
//            }
//        }
    }

    /**
     * Activate input word nodes for the given sentence
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $wordData Parsed word data
     * @return array Array of activated L1 nodes
     */
    private function activateInputWords(RuntimeGraph $graph, array $wordData): array
    {
        $activeNodes = [];

        foreach ($wordData as $data) {
            $word = $data['word'];
            $lemma = $data['lemma'];
            $pos = $data['pos'];

            // Find matching L1 literal nodes in the graph
            $l1Nodes = $graph->getNodesByLevel('L1');
            foreach ($l1Nodes as $node) {
                if ($node->construction_type === 'literal') {
                    $nodeWord = $node->features['value'] ?? '';

                    // Match by word or lemma
                    if ($nodeWord === $word || $nodeWord === $lemma) {
                        $node->activation = 0.95;
                        $activeNodes[] = $node;
                    }
                }
            }
        }

        return $activeNodes;
    }

    /**
     * Propagate activation through the network
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $activeWordNodes Initial active word nodes
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     * @param bool $applyLearning Whether to apply Hebbian learning
     * @param int $maxIterations Maximum iterations for convergence (default: MAX_ITERATIONS)
     */
    private function propagateActivation(
//        RuntimeGraph $graph,
        array $activeWordNodes,
//        array $seqColumnsL1,
//        array $seqColumnsL2,
        bool  $applyLearning = true,
        int   $maxIterations = self::MAX_ITERATIONS
    ): void
    {
        // Merge all columns for node lookup
        //$allColumns = array_merge($seqColumnsL1, $seqColumnsL2);


        // Iterate until convergence or max iterations
        $activeNodes = $activeWordNodes;
        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $this->activeNodes = [];
            foreach ($activeNodes as $node) {
                $this->propagateActivationFromNode($node);
            }
            if (empty($this->activeNodes)) {
                break;
            }
            $activeNodes = $this->activeNodes;

//            // 1. Propagate word → POS (via category edges)
//            $this->propagateWordToPOS($graph, $activeWordNodes);
//
//            // 2. Propagate POS → SeqColumn head nodes (via feedforward edges)
//            $this->propagatePOSToColumns($graph, $seqColumnsL1);
//
//            // 3. Update internal SeqColumn dynamics (L23 → L5)
//            $this->updateColumnDynamics($seqColumnsL1);
//            $this->updateColumnDynamics($seqColumnsL2);
//
//            // 4. Propagate sequential connections (between columns)
//            $this->propagateSequentialConnections($graph, $seqColumnsL1, $allColumns);
//            $this->propagateSequentialConnections($graph, $seqColumnsL2, $allColumns);
//
//            // 5. Apply inhibitory dynamics (Winner-Take-All)
//            $this->applyInhibitoryDynamics($graph, $seqColumnsL1);
//            $this->applyInhibitoryDynamics($graph, $seqColumnsL2);
        }

        // 6. Apply Hebbian learning (only during training, not testing)
//        if ($applyLearning) {
//            //$this->applyHebbianLearning($graph, $seqColumnsL1, $seqColumnsL2);
//            $this->applyHebbianLearning();
//        }
    }

    private function propagateActivationFromNode(Node $source): void
    {

        $edges = $this->graph->getEdges($source->id);

        foreach ($edges as $edge) {
            $target = $this->graph->getNode($edge->target);
            // Transfer activation from source to target
            $inputActivation = $source->activation * $edge->weight;
            $target->activation = max($target->activation, $inputActivation * 0.9);
            // Only strengthen if both source and target are co-active
            if ($target->activation >= self::HEBBIAN_COACTIVATION_THRESHOLD) {
                // Hebbian learning rule: Δw = η * a_source * a_target
                //$deltaWeight = self::HEBBIAN_LEARNING_RATE * $source->activation * $target->activation;
                $deltaWeight = self::HEBBIAN_LEARNING_RATE;

                // Update weight (capped at MAX_WEIGHT)
                $edge->weight = min(self::MAX_WEIGHT, $edge->weight + ($edge->weight * $deltaWeight));
            }
            $this->activeNodes[] = $target;
        }
    }


    /**
     * Propagate activation from word nodes to POS nodes via category edges
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $activeWordNodes Active word nodes
     */
    private function propagateWordToPOS(RuntimeGraph $graph, array $activeWordNodes): void
    {
        foreach ($activeWordNodes as $wordNode) {
            $edges = $graph->getEdges($wordNode->id);

            foreach ($edges as $edge) {
                if ($edge->type === 'category') {
                    $posNode = $graph->getNode($edge->target);

                    if ($posNode && $posNode->construction_type === 'pos') {
                        // Transfer activation from word to POS
                        $inputActivation = $wordNode->activation * $edge->weight;
                        $posNode->activation = max($posNode->activation, $inputActivation * 0.9);
                    }
                }
            }
        }
    }

    /**
     * Propagate activation from POS nodes to SeqColumn head nodes
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumns SeqColumns to update
     */
    private function propagatePOSToColumns(RuntimeGraph $graph, array $seqColumns): void
    {
        foreach ($seqColumns as $column) {
            $headNode = $column->h_node;

            // Find incoming feedforward edges to head node
            $edges = $graph->getIncomingEdges($headNode->id);

            foreach ($edges as $edge) {
                if ($edge->type === 'feedforward') {
                    $sourceNode = $graph->getNode($edge->source);

                    if ($sourceNode && $sourceNode->activation > self::ACTIVATION_THRESHOLD) {
                        // Transfer activation to head node
                        $inputActivation = $sourceNode->activation * $edge->weight;
                        $headNode->activation = max($headNode->activation, $inputActivation * 0.85);
                    }
                }
            }
        }
    }

    /**
     * Update internal dynamics for all SeqColumns
     *
     * @param array $seqColumns SeqColumns to update
     */
    private function updateColumnDynamics(array $seqColumns): void
    {
        foreach ($seqColumns as $column) {
            $column->updateInternalDynamics(self::DT);
        }
    }

    /**
     * Propagate activation through sequential connections between columns
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumns SeqColumns to process
     * @param array $allColumns All SeqColumns for node lookup
     */
    private function propagateSequentialConnections(RuntimeGraph $graph, array $seqColumns, array $allColumns): void
    {
        foreach ($seqColumns as $column) {
            $sequencerNode = $column->s_node;

            // If sequencer is active, propagate to left/right nodes of other columns
            if ($sequencerNode->activation > self::ACTIVATION_THRESHOLD) {
                $edges = $graph->getEdges($sequencerNode->id);

                foreach ($edges as $edge) {
                    if ($edge->type === 'feedforward') {
                        // Try to find target in main graph first
                        $targetNode = $graph->getNode($edge->target);

                        // If not in graph, look in SeqColumns
                        if (!$targetNode) {
                            $targetNode = $this->findNodeInColumns($edge->target, $allColumns);
                        }

                        if ($targetNode && $targetNode->cortical_level === 'L23') {
                            // Transfer activation to target L23 node
                            $inputActivation = $sequencerNode->activation * $edge->weight;
                            $targetNode->activation = max($targetNode->activation, $inputActivation * 0.8);
                        }
                    }
                }
            }

            // Also propagate from incoming left/right nodes
            foreach ($column->getLeftNodes() as $leftNode) {
                if ($leftNode->activation > self::ACTIVATION_THRESHOLD) {
                    $edges = $graph->getIncomingEdges($leftNode->id);

                    foreach ($edges as $edge) {
                        if ($edge->type === 'feedforward') {
                            // Try to find source in main graph first
                            $sourceNode = $graph->getNode($edge->source);

                            // If not in graph, look in SeqColumns
                            if (!$sourceNode) {
                                $sourceNode = $this->findNodeInColumns($edge->source, $allColumns);
                            }

                            if ($sourceNode && $sourceNode->cortical_level === 'L5') {
                                $inputActivation = $sourceNode->activation * $edge->weight;
                                $leftNode->activation = max($leftNode->activation, $inputActivation * 0.75);
                            }
                        }
                    }
                }
            }

            foreach ($column->getRightNodes() as $rightNode) {
                if ($rightNode->activation > self::ACTIVATION_THRESHOLD) {
                    $edges = $graph->getIncomingEdges($rightNode->id);

                    foreach ($edges as $edge) {
                        if ($edge->type === 'feedforward') {
                            // Try to find source in main graph first
                            $sourceNode = $graph->getNode($edge->source);

                            // If not in graph, look in SeqColumns
                            if (!$sourceNode) {
                                $sourceNode = $this->findNodeInColumns($edge->source, $allColumns);
                            }

                            if ($sourceNode && $sourceNode->cortical_level === 'L5') {
                                $inputActivation = $sourceNode->activation * $edge->weight;
                                $rightNode->activation = max($rightNode->activation, $inputActivation * 0.75);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Apply inhibitory dynamics (Winner-Take-All) via PV interneurons
     *
     * PV nodes mediate competition between columns with the same source.
     * Active PV nodes inhibit competing pathways.
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumns SeqColumns to process
     */
    private function applyInhibitoryDynamics(RuntimeGraph $graph, array $seqColumns): void
    {
        // Collect PV nodes by source for competition
        $pvNodesBySource = [
            'left' => [],
            'right' => [],
        ];

        foreach ($seqColumns as $column) {
            // Update PV activation based on corresponding L23 node activation
            foreach ($column->getPVLeftNodes() as $sourceId => $pvNode) {
                $leftNode = $column->l_nodes[$sourceId] ?? null;
                if ($leftNode) {
                    // PV activation follows L23 activation
                    $pvNode->update($leftNode->activation, self::DT);

                    // Group by source for inhibition
                    if (!isset($pvNodesBySource['left'][$sourceId])) {
                        $pvNodesBySource['left'][$sourceId] = [];
                    }
                    $pvNodesBySource['left'][$sourceId][] = [
                        'column' => $column,
                        'pv_node' => $pvNode,
                        'l23_node' => $leftNode,
                    ];
                }
            }

            foreach ($column->getPVRightNodes() as $sourceId => $pvNode) {
                $rightNode = $column->r_nodes[$sourceId] ?? null;
                if ($rightNode) {
                    // PV activation follows L23 activation
                    $pvNode->update($rightNode->activation, self::DT);

                    // Group by source for inhibition
                    if (!isset($pvNodesBySource['right'][$sourceId])) {
                        $pvNodesBySource['right'][$sourceId] = [];
                    }
                    $pvNodesBySource['right'][$sourceId][] = [
                        'column' => $column,
                        'pv_node' => $pvNode,
                        'l23_node' => $rightNode,
                    ];
                }
            }
        }

        // Apply Winner-Take-All inhibition for each source
        foreach (['left', 'right'] as $position) {
            foreach ($pvNodesBySource[$position] as $sourceId => $pvNodesInfo) {
                if (count($pvNodesInfo) < 2) {
                    continue; // No competition
                }

                // Find the most active PV (winner)
                $maxActivation = 0.0;
                $winnerIndex = 0;

                foreach ($pvNodesInfo as $index => $info) {
                    if ($info['pv_node']->activation > $maxActivation) {
                        $maxActivation = $info['pv_node']->activation;
                        $winnerIndex = $index;
                    }
                }

                // Inhibit losing nodes
                foreach ($pvNodesInfo as $index => $info) {
                    if ($index === $winnerIndex) {
                        continue; // Don't inhibit winner
                    }

                    // Apply inhibition to L23 node
                    $inhibition = $maxActivation * self::INHIBITION_STRENGTH;
                    $info['l23_node']->activation = max(0.0, $info['l23_node']->activation - $inhibition);
                }
            }
        }
    }

    /**
     * Apply Hebbian learning to strengthen co-active connections
     *
     * "Neurons that fire together, wire together"
     * Strengthens weights of edges where both source and target are active.
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     */
    private function applyHebbianLearning(RuntimeGraph $graph, array $seqColumnsL1, array $seqColumnsL2, array $seqColumnsL3): void
    {
        $allColumns = array_merge($seqColumnsL1, $seqColumnsL2, $seqColumnsL3);

        // Apply Hebbian learning to all edges in the graph
        foreach ($graph->getAllNodes() as $node) {
            $this->applyHebbianToNode($graph, $node, $allColumns);
        }

        // Apply Hebbian learning to edges from SeqColumn SEQUENCER nodes
        foreach ($allColumns as $column) {
            // Apply to SEQUENCER outgoing edges (sequential connections)
            $this->applyHebbianToNode($graph, $column->s_node, $allColumns);

            // Apply to L23 nodes outgoing edges
            $this->applyHebbianToNode($graph, $column->h_node, $allColumns);
            foreach ($column->getLeftNodes() as $leftNode) {
                $this->applyHebbianToNode($graph, $leftNode, $allColumns);
            }
            foreach ($column->getRightNodes() as $rightNode) {
                $this->applyHebbianToNode($graph, $rightNode, $allColumns);
            }
        }

        // Apply Hebbian learning to internal SeqColumn edges
        $this->applyHebbianToColumns($seqColumnsL1);
        $this->applyHebbianToColumns($seqColumnsL2);
    }

    /**
     * Apply Hebbian learning to outgoing edges from a single node
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param Column $node The source node
     * @param array $allColumns All SeqColumns for node lookup
     */
    private function applyHebbianToNode(RuntimeGraph $graph, Column $node, array $allColumns): void
    {
        $sourceActivation = $node->activation;

        // Only apply learning if source is sufficiently active
        if ($sourceActivation < self::HEBBIAN_COACTIVATION_THRESHOLD) {
            return;
        }

        $edges = $graph->getEdges($node->id);

        foreach ($edges as $edge) {
            // Skip inhibitory edges - they don't use Hebbian learning
            if ($edge->type === 'inhibitory') {
                continue;
            }

            $targetNode = $graph->getNode($edge->target);

            // Check if target is a SeqColumn internal node
            if (!$targetNode) {
                // Try to find in SeqColumns
                $targetNode = $this->findNodeInColumns($edge->target, $allColumns);
            }

            if (!$targetNode) {
                continue;
            }

            $targetActivation = $targetNode->activation;

            // Only strengthen if both source and target are co-active
            if ($targetActivation >= self::HEBBIAN_COACTIVATION_THRESHOLD) {
                // Hebbian learning rule: Δw = η * a_source * a_target
                $deltaWeight = self::HEBBIAN_LEARNING_RATE * $sourceActivation * $targetActivation;

                // Update weight (capped at MAX_WEIGHT)
                $edge->weight = min(self::MAX_WEIGHT, $edge->weight + $deltaWeight);
            }
        }
    }

    /**
     * Find a node in SeqColumns by ID
     *
     * @param string $nodeId Node ID to find
     * @param array $seqColumns SeqColumns to search
     * @return Column|null The node if found
     */
    private function findNodeInColumns(string $nodeId, array $seqColumns): ?Column
    {
        foreach ($seqColumns as $column) {
            if ($column->h_node->id === $nodeId) {
                return $column->h_node;
            }
            if ($column->s_node->id === $nodeId) {
                return $column->s_node;
            }
            foreach ($column->getLeftNodes() as $leftNode) {
                if ($leftNode->id === $nodeId) {
                    return $leftNode;
                }
            }
            foreach ($column->getRightNodes() as $rightNode) {
                if ($rightNode->id === $nodeId) {
                    return $rightNode;
                }
            }
        }

        return null;
    }

    /**
     * Apply Hebbian learning to internal SeqColumn connections
     *
     * @param array $seqColumns SeqColumns to process
     */
    private function applyHebbianToColumns(array $seqColumns): void
    {
        foreach ($seqColumns as $column) {
            foreach ($column->getInternalEdges() as $edge) {
                // Get source and target nodes from column internal structure
                $sourceNode = null;
                $targetNode = null;

                // Find source node (could be l, h, or r node)
                if ($edge->source === $column->h_node->id) {
                    $sourceNode = $column->h_node;
                } else {
                    foreach ($column->getLeftNodes() as $leftNode) {
                        if ($edge->source === $leftNode->id) {
                            $sourceNode = $leftNode;
                            break;
                        }
                    }
                    if (!$sourceNode) {
                        foreach ($column->getRightNodes() as $rightNode) {
                            if ($edge->source === $rightNode->id) {
                                $sourceNode = $rightNode;
                                break;
                            }
                        }
                    }
                }

                // Find target node (usually the SEQUENCER)
                if ($edge->target === $column->s_node->id) {
                    $targetNode = $column->s_node;
                }

                if (!$sourceNode || !$targetNode) {
                    continue;
                }

                // Apply Hebbian learning if both are co-active
                if ($sourceNode->activation >= self::HEBBIAN_COACTIVATION_THRESHOLD
                    && $targetNode->activation >= self::HEBBIAN_COACTIVATION_THRESHOLD) {
                    $deltaWeight = self::HEBBIAN_LEARNING_RATE * $sourceNode->activation * $targetNode->activation;
                    $edge->weight = min(self::MAX_WEIGHT, $edge->weight + $deltaWeight);
                }
            }
        }
    }

    /**
     * Capture the current state of the graph for visualization
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     * @return array State snapshot with node activations
     */
    private function captureState(RuntimeGraph $graph, array $seqColumnsL1, array $seqColumnsL2): array
    {
        $state = [
            'nodes' => [],
            'columns_l1' => [],
            'columns_l2' => [],
        ];

        // Capture all graph nodes
        foreach ($graph->getAllNodes() as $node) {
            $state['nodes'][$node->id] = [
                'id' => $node->id,
                'activation' => $node->activation,
                'cortical_level' => $node->cortical_level,
                'construction_type' => $node->construction_type,
                'features' => $node->features,
            ];
        }

        // Capture L1 SeqColumn internal states
        foreach ($seqColumnsL1 as $posTag => $column) {
            $state['columns_l1'][$posTag] = $this->captureColumnState($column);
        }

        // Capture L2 SeqColumn internal states
        foreach ($seqColumnsL2 as $trigramId => $column) {
            $state['columns_l2'][$trigramId] = $this->captureColumnState($column);
        }

        return $state;
    }

    /**
     * Capture the state of a single SeqColumn
     *
     * @param SeqColumn $column The column to capture
     * @return array Column state snapshot
     */
    private function captureColumnState(SeqColumn $column): array
    {
        $state = [
            'id' => $column->id,
            'h_node' => $column->h_node->activation,
            's_node' => $column->s_node->activation,
            'l_nodes' => [],
            'r_nodes' => [],
            'pv_l_nodes' => [],
            'pv_r_nodes' => [],
        ];

        foreach ($column->getLeftNodes() as $sourceId => $node) {
            $state['l_nodes'][$sourceId] = $node->activation;
        }

        foreach ($column->getRightNodes() as $sourceId => $node) {
            $state['r_nodes'][$sourceId] = $node->activation;
        }

        foreach ($column->getPVLeftNodes() as $sourceId => $pvNode) {
            $state['pv_l_nodes'][$sourceId] = $pvNode->activation;
        }

        foreach ($column->getPVRightNodes() as $sourceId => $pvNode) {
            $state['pv_r_nodes'][$sourceId] = $pvNode->activation;
        }

        return $state;
    }

    /**
     * Reset all activations in the graph and columns
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     */
    public function resetActivations(RuntimeGraph $graph, array $seqColumnsL1, array $seqColumnsL2 = []): void
    {
        // Reset all graph nodes
        foreach ($graph->getAllNodes() as $node) {
            $node->activation = 0.0;
        }

        // Reset all L1 SeqColumn nodes
        foreach ($seqColumnsL1 as $column) {
            $this->resetColumnActivations($column);
        }

        // Reset all L2 SeqColumn nodes
        foreach ($seqColumnsL2 as $column) {
            $this->resetColumnActivations($column);
        }
    }

    /**
     * Reset activations for a single SeqColumn
     *
     * @param SeqColumn $column The column to reset
     */
    private function resetColumnActivations(SeqColumn $column): void
    {
        $column->h_node->activation = 0.0;
        $column->s_node->activation = 0.0;

        foreach ($column->getLeftNodes() as $node) {
            $node->activation = 0.0;
        }

        foreach ($column->getRightNodes() as $node) {
            $node->activation = 0.0;
        }

        foreach ($column->getPVLeftNodes() as $pvNode) {
            $pvNode->reset();
        }

        foreach ($column->getPVRightNodes() as $pvNode) {
            $pvNode->reset();
        }

        $column->activation = 0.0;
    }

    /**
     * Generate DOT graph with activation state
     *
     * Similar to the learning graph generation, but colors nodes based on activation.
     * Deactivated nodes (activation < threshold) are colored gray.
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     * @param array $metadata Metadata for the graph
     * @param bool $isAfter Whether this is the "after" state (shows deactivated nodes)
     * @return string DOT content
     */
    public function generateActivationDOT(
        RuntimeGraph $graph,
        array        $seqColumnsL1,
        array        $seqColumnsL2,
        array        $seqColumnsL3,
        array        $metadata,
        bool         $isAfter = false
    ): string
    {
        $dot = [];
        $dot[] = 'digraph CLNActivation {';
        $dot[] = '  // Graph styling';
        $dot[] = '  rankdir=LR;';
        $dot[] = '  node [style=filled, fontname="Arial", fontsize=10];';
        $dot[] = '  edge [fontname="Arial", fontsize=8];';
        $dot[] = '  compound=true;';
        $dot[] = '';

        // Add metadata
        $stateLabel = $isAfter ? 'After Activation' : 'Before Activation';
        $metadataLines = ["Graph: Learning Activation - {$stateLabel}"];
        foreach ($metadata as $key => $value) {
            $metadataLines[] = "{$key}: {$value}";
        }
        $label = implode('\n', $metadataLines);
        $dot[] = "  label=\"{$label}\";";
        $dot[] = '  labelloc=top;';
        $dot[] = '  fontsize=12;';
        $dot[] = '';

        // Export L1 POS nodes only (skip literal word nodes)
        $dot[] = '  // L1 POS nodes';
        $l1Nodes = $graph->getNodesByLevel('L1');
        foreach ($l1Nodes as $node) {
            // Skip literal word nodes - only show POS pattern nodes
            if ($node->construction_type === 'literal') {
                continue;
            }

            $color = $this->getNodeColor($node, $isAfter);
            $value = $node->features['value'] ?? '';
            $dot[] = "  \"{$node->id}\" [label=\"L1: {$node->construction_type}\\nAct: " .
                number_format($node->activation, 2) . "\\nValue: '{$value}'\", fillcolor=\"{$color}\", shape=box];";
        }
        $dot[] = '';

        // Export edges (excluding inhibitory and edges to/from literal nodes)
        $dot[] = '  // Edges';
        $exportedEdges = [];
        foreach ($graph->getAllNodes() as $node) {
            // Skip edges from literal nodes
            if ($node->construction_type === 'literal') {
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

                $edgeKey = "{$edge->source}:{$edge->target}";
                if (isset($exportedEdges[$edgeKey])) {
                    continue;
                }
                $exportedEdges[$edgeKey] = true;

                $color = $edge->type === 'category' ? '#FFA500' : '#228B22';
                $penwidth = $this->getEdgeWidth($edge->weight);
                $label = "{$edge->type} (w=" . number_format($edge->weight, 2) . ')';
                $dot[] = "  \"{$edge->source}\" -> \"{$edge->target}\" [color=\"{$color}\", label=\"{$label}\", penwidth={$penwidth}];";
            }
        }

        // Export SeqColumn edges
        foreach (array_merge($seqColumnsL1, $seqColumnsL2) as $column) {
            $columnNodeIds = [$column->h_node->id, $column->s_node->id];

            foreach ($column->getLeftNodes() as $leftNode) {
                $columnNodeIds[] = $leftNode->id;
            }

            foreach ($column->getRightNodes() as $rightNode) {
                $columnNodeIds[] = $rightNode->id;
            }

            foreach ($columnNodeIds as $nodeId) {
                $edges = $graph->getEdges($nodeId);
                foreach ($edges as $edge) {
                    if ($edge->type === 'inhibitory') {
                        continue;
                    }

                    $edgeKey = "{$edge->source}:{$edge->target}";
                    if (isset($exportedEdges[$edgeKey])) {
                        continue;
                    }
                    $exportedEdges[$edgeKey] = true;

                    $color = '#4169E1';
                    $penwidth = $this->getEdgeWidth($edge->weight);
                    $label = 'sequential (w=' . number_format($edge->weight, 2) . ')';
                    $dot[] = "  \"{$edge->source}\" -> \"{$edge->target}\" [color=\"{$color}\", label=\"{$label}\", penwidth={$penwidth}];";
                }
            }
        }

        $dot[] = '';

        // Add SeqColumns with activation-based coloring
        $dot = array_merge($dot, $this->generateSeqColumnsDOT($seqColumnsL1, 'L1', $isAfter));
        $dot = array_merge($dot, $this->generateSeqColumnsDOT($seqColumnsL2, 'L2', $isAfter));
        $dot = array_merge($dot, $this->generateSeqColumnsDOT($seqColumnsL3, 'L3', $isAfter));

        $dot[] = '}';

        return implode("\n", $dot);
    }

    /**
     * Get node color based on activation level
     *
     * @param Column $node The node
     * @param bool $isAfter Whether this is the "after" state
     * @return string Hex color code
     */
    private function getNodeColor(Column $node, bool $isAfter): string
    {
        // If "after" state and node is deactivated, return gray
        if ($isAfter && $node->activation < self::ACTIVATION_THRESHOLD) {
            return '#D3D3D3'; // Light gray for deactivated
        }

        // Default colors based on construction type
        if ($node->construction_type === 'pos') {
            return '#FFD700'; // Gold for POS
        }

        return '#90EE90'; // Light green for literals
    }

    /**
     * Generate DOT for SeqColumns with activation-based coloring
     *
     * @param array $seqColumns SeqColumns to render
     * @param string $level Level label (L1 or L2)
     * @param bool $isAfter Whether this is the "after" state
     * @return array DOT lines
     */
    private function generateSeqColumnsDOT(array $seqColumns, string $level, bool $isAfter): array
    {
        $dot = [];

        if (empty($seqColumns)) {
            return $dot;
        }

        $dot[] = "  // {$level} SeqColumns";

        $clusterColor = $level === 'L1' ? '#DAA520' : '#DC143C';
        $fillColor = $level === 'L1' ? '#FFF8DC' : '#FFE4E1';
        $nodeColor = $level === 'L1' ? '#90EE90' : '#FFA07A';
        $sequencerColor = $level === 'L1' ? '#4169E1' : '#DC143C';

        foreach ($seqColumns as $key => $column) {
            $label = $level === 'L1' ? "SeqColumn {$level}: {$key}" : "SeqColumn {$level}: {$key}";

            $dot[] = '';
            $dot[] = "  subgraph cluster_{$column->id} {";
            $dot[] = "    label=\"{$label}\";";
            $dot[] = '    style=filled;';
            $dot[] = "    fillcolor=\"{$fillColor}\";";
            $dot[] = "    color=\"{$clusterColor}\";";
            $dot[] = '    penwidth=2;';
            $dot[] = '';

            // L23 nodes
            foreach ($column->getLeftNodes() as $sourceId => $leftNode) {
                $sourceName = $this->getShortSourceName($sourceId);
                $color = $this->getActivationColor($leftNode->activation, $nodeColor, $isAfter);
                $dot[] = "    \"{$leftNode->id}\" [label=\"l:{$sourceName}\\nAct: " .
                    number_format($leftNode->activation, 2) . "\", fillcolor=\"{$color}\", shape=ellipse];";
            }

            $color = $this->getActivationColor($column->h_node->activation, $nodeColor, $isAfter);
            $dot[] = "    \"{$column->h_node->id}\" [label=\"h\\nAct: " .
                number_format($column->h_node->activation, 2) . "\", fillcolor=\"{$color}\", shape=ellipse];";

            foreach ($column->getRightNodes() as $sourceId => $rightNode) {
                $sourceName = $this->getShortSourceName($sourceId);
                $color = $this->getActivationColor($rightNode->activation, $nodeColor, $isAfter);
                $dot[] = "    \"{$rightNode->id}\" [label=\"r:{$sourceName}\\nAct: " .
                    number_format($rightNode->activation, 2) . "\", fillcolor=\"{$color}\", shape=ellipse];";
            }

            // L5 sequencer
            $color = $this->getActivationColor($column->s_node->activation, $sequencerColor, $isAfter);
            $dot[] = "    \"{$column->s_node->id}\" [label=\"{$column->s_node->id}\\nAct: " .
                number_format($column->s_node->activation, 2) . "\", fillcolor=\"{$color}\", fontcolor=\"white\", shape=hexagon];";

            // Internal edges
            foreach ($column->getInternalEdges() as $edge) {
                $penwidth = $this->getEdgeWidth($edge->weight);
                $label = 'internal (w=' . number_format($edge->weight, 2) . ')';
                $dot[] = "    \"{$edge->source}\" -> \"{$edge->target}\" [color=\"#228B22\", label=\"{$label}\", penwidth={$penwidth}];";
            }

            $dot[] = '  }';
        }

        return $dot;
    }

    /**
     * Get edge width based on weight (for visualization)
     *
     * Maps edge weight to line thickness:
     * - Weight 1.0: penwidth 1
     * - Weight 2.0: penwidth 2
     * - Weight 3.0: penwidth 3
     *
     * @param float $weight Edge weight
     * @return float Pen width for visualization
     */
    private function getEdgeWidth(float $weight): float
    {
        // Linear mapping: weight 1.0 → 1.0, weight 3.0 → 3.0
        return min(3.0, max(1.0, $weight));
    }

    /**
     * Get activation-based color for a node
     *
     * @param float $activation Activation level
     * @param string $baseColor Base color for active nodes
     * @param bool $isAfter Whether this is the "after" state
     * @return string Hex color code
     */
    private function getActivationColor(float $activation, string $baseColor, bool $isAfter): string
    {
        if ($isAfter && $activation < self::ACTIVATION_THRESHOLD) {
            return '#D3D3D3'; // Gray for deactivated
        }

        return $baseColor;
    }

    /**
     * Extract short source name from node ID
     *
     * @param string $sourceId Full source ID
     * @return string Short name
     */
    private function getShortSourceName(string $sourceId): string
    {
        if (preg_match('/^SeqCol_(.+?)_L5_S$/', $sourceId, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^L1_P-?\d+_pos_(.+)$/', $sourceId, $matches)) {
            return $matches[1];
        }

        $parts = explode('_', $sourceId);

        return end($parts);
    }
}

<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\BinaryErrorCalculator;
use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\RuntimeGraph;

class ActivationDynamics
{
    public function __construct(
        private BinaryErrorCalculator $errorCalculator
    ) {}

    public function updateNode(Column $node, RuntimeGraph $graph, float $dt): void
    {
        // Different dynamics for L1 and L2 nodes
        if ($node->cortical_level === 'L2') {
            $this->updateL2Node($node, $graph, $dt);
        } else {
            $this->updateL1Node($node, $graph, $dt);
        }
    }

    /**
     * Update L1 node (single element)
     */
    private function updateL1Node(Column $node, RuntimeGraph $graph, float $dt): void
    {
        $bottomUp = $this->collectBottomUpInput($node, $graph);
        $topDownPrediction = $this->collectTopDownPrediction($node, $graph);

        $binaryError = $this->errorCalculator->calculateError($bottomUp, $topDownPrediction);

        $this->updateInterneurons($node, $binaryError, $dt);
        $this->updateCompetitivePV($node, $graph, $dt);

        $gatingFactor = $this->computeSOMGating($node->SOM->activation);
        $gatedTopDown = $topDownPrediction !== null ? $gatingFactor : 0.0;

        $L23Input = ($bottomUp !== null ? 1.0 : 0.0) - $gatedTopDown;
        $node->L23->update($L23Input, $dt);

        $errorModulation = $binaryError === 0.0 ? 1.5 : 0.1;

        $lateralExcite = $this->collectLateralExcitatory($node, $graph);
        $lateralInhib = $this->collectLateralInhibitory($node, $graph);

        $L5Input = ($node->L23->activation + $lateralExcite) / (1 + $lateralInhib);
        $L5Input *= $errorModulation;

        $node->L5->update($L5Input, $dt);

        $node->activation = $node->L5->activation;
    }

    /**
     * Update L2 node (paired composition)
     *
     * L2 nodes:
     * - Receive bottom-up from TWO L1 nodes
     * - Generate predictions for next element
     * - Check if prediction matches incoming L1 node at predicted position
     * - Compete with overlapping L2 nodes via lateral inhibition
     *
     * RNT L2 nodes:
     * - Single-element: Bottom-up from one L1 source
     * - Partial AND: Weak bottom-up, strong prediction error signal
     * - Complete AND: Strong bottom-up from both operands
     */
    private function updateL2Node(Column $node, RuntimeGraph $graph, float $dt): void
    {
        // Check if this is an RNT construction
        if ($node->isRNTConstruction()) {
            $this->updateRNTNode($node, $graph, $dt);

            return;
        }

        // === CLN v3 Dynamics (Backward Compatibility) ===

        // Collect bottom-up from both constituent L1 nodes
        $bottomUp = $this->collectBottomUpInput($node, $graph);

        // Check if L2 prediction matches actual next element
        $predictionError = $this->checkL2Prediction($node, $graph);

        $this->updateInterneurons($node, $predictionError, $dt);
        $this->updateCompetitivePV($node, $graph, $dt);

        // L23 computes prediction error
        $L23Input = 0.0;

        if ($bottomUp !== null) {
            // Strong bottom-up signal from both L1 nodes
            $L23Input = $bottomUp['combined_activation'];
        }

        // If prediction was wrong, increase L23 error signal
        if ($predictionError > 0.0) {
            $L23Input += $predictionError;
        }

        $node->L23->update($L23Input, $dt);

        // L5 maintains state and generates predictions
        $errorModulation = $predictionError === 0.0 ? 1.8 : 0.3;

        // L2 nodes strengthen when both constituents are active
        if ($bottomUp !== null && $bottomUp['combined_activation'] > 0.8) {
            $errorModulation *= 1.2;
        }

        $lateralExcite = $this->collectLateralExcitatory($node, $graph);
        $lateralInhib = $this->collectLateralInhibitory($node, $graph);

        $L5Input = ($node->L23->activation + $lateralExcite) / (1 + $lateralInhib);
        $L5Input *= $errorModulation;

        $node->L5->update($L5Input, $dt);

        $node->activation = $node->L5->activation;
    }

    /**
     * Check if L2 node's prediction matches actual next element
     *
     * @param  Column  $node  L2 node
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return float Binary error (0.0 = match, 1.0 = no match)
     */
    private function checkL2Prediction(Column $node, RuntimeGraph $graph): float
    {
        // If L2 node has no prediction, no error
        if ($node->predicted_element === null) {
            return 0.0;
        }

        $prediction = $node->predicted_element;

        // Predicted position is after the L2 span
        $predictedPosition = $node->span[1] + 1;

        // Get L1 nodes at predicted position
        $nodesAtPosition = $graph->getNodesAtPosition($predictedPosition);

        // Check if any L1 node at that position matches prediction
        foreach ($nodesAtPosition as $l1Node) {
            if ($this->nodeMatchesPrediction($l1Node, $prediction)) {
                return 0.0; // Match! No error
            }
        }

        // No match found - prediction error
        return 1.0;
    }

    /**
     * Check if node matches prediction
     *
     * Supports multiple prediction modes:
     * - LITERAL: exact word match (L1 nodes)
     * - SLOT: POS-based match (L1 nodes)
     * - CONSTRUCTION_REF: construction type match (L2+ nodes)
     */
    private function nodeMatchesPrediction(Column $node, array $prediction): bool
    {
        $nodeValue = $node->features['value'] ?? null;
        $nodePOS = $node->features['pos'] ?? null;
        $nodeConstructionType = $node->construction_type;

        $predType = $prediction['type'] ?? null;
        $predValue = $prediction['value'] ?? null;
        $predSpec = $prediction['spec'] ?? null;

        // LITERAL prediction: exact word match
        if ($predType === 'LITERAL') {
            return $nodeValue === $predValue;
        }

        // SLOT prediction: POS-based match
        if ($predType === 'SLOT') {
            if ($predSpec) {
                $spec = is_string($predSpec) ? json_decode($predSpec, true) : $predSpec;
                $requiredPOS = $spec['pos'] ?? null;

                return $nodePOS === $requiredPOS;
            }

            return false;
        }

        // CONSTRUCTION_REF prediction: construction type match
        if ($predType === 'CONSTRUCTION_REF') {
            if ($predSpec) {
                $spec = is_string($predSpec) ? json_decode($predSpec, true) : $predSpec;
                $requiredConstructionName = $spec['construction_name'] ?? null;

                return $nodeConstructionType === $requiredConstructionName;
            }

            return false;
        }

        // For other types, no match
        return false;
    }

    private function collectBottomUpInput(Column $node, RuntimeGraph $graph): mixed
    {
        // For L2 nodes with paired composition (two constituents)
        if ($node->cortical_level === 'L2' && isset($node->bindings['first'], $node->bindings['second'])) {
            $firstNode = $graph->getNode($node->bindings['first']);
            $secondNode = $graph->getNode($node->bindings['second']);

            if ($firstNode && $secondNode) {
                // Both L1 nodes must be highly active
                $minActivation = min($firstNode->activation, $secondNode->activation);
                if ($minActivation > 0.7) {
                    return [
                        'type' => 'L2_COMPOSITION',
                        'first' => $firstNode->features,
                        'second' => $secondNode->features,
                        'combined_activation' => $minActivation,
                    ];
                }
            }

            return null;
        }

        // For L2 nodes with single-element composition (one constituent)
        if ($node->cortical_level === 'L2' && isset($node->bindings['source'])) {
            $sourceNode = $graph->getNode($node->bindings['source']);

            if ($sourceNode && $sourceNode->activation > 0.7) {
                return [
                    'type' => 'L2_SINGLE_ELEMENT',
                    'source' => $sourceNode->features,
                    'combined_activation' => $sourceNode->activation,
                ];
            }

            return null;
        }

        // For L1 nodes, collect from single feedforward edge
        $edges = $graph->getIncomingEdges($node->id);
        foreach ($edges as $edge) {
            if ($edge->type === 'feedforward') {
                $sourceNode = $graph->getNode($edge->source);
                if ($sourceNode && $sourceNode->activation > 0.8) {
                    return $sourceNode->features;
                }
            }
        }

        return null;
    }

    private function collectTopDownPrediction(Column $node, RuntimeGraph $graph): mixed
    {
        $edges = $graph->getIncomingEdges($node->id);
        foreach ($edges as $edge) {
            if ($edge->type === 'feedback') {
                $sourceNode = $graph->getNode($edge->source);
                if ($sourceNode && $sourceNode->predicted_element !== null) {
                    return $sourceNode->predicted_element;
                }
            }
        }

        return null;
    }

    private function updateInterneurons(Column $node, float $binaryError, float $dt): void
    {
        if ($binaryError === 1.0) {
            $node->VIP->update(1.0, $dt);
        } else {
            $node->VIP->update(0.0, $dt);
        }

        $node->SOM->activation *= (1 - $node->VIP->activation * 0.9);

        $node->PV->activation *= (1 - $node->VIP->activation * 0.3);
    }

    private function computeSOMGating(float $somActivation): float
    {
        if ($somActivation < 0.3) {
            return 1.0;
        } elseif ($somActivation > 0.7) {
            return 0.2;
        } else {
            return 1.0 - 0.8 * (($somActivation - 0.3) / 0.4);
        }
    }

    private function collectLateralExcitatory(Column $node, RuntimeGraph $graph): float
    {
        $sum = 0.0;
        $edges = $graph->getIncomingEdges($node->id);
        foreach ($edges as $edge) {
            if ($edge->type === 'lateral-excite') {
                $sourceNode = $graph->getNode($edge->source);
                if ($sourceNode) {
                    $sum += $sourceNode->L5->activation * $edge->weight;
                }
            }
        }

        return $sum;
    }

    private function collectLateralInhibitory(Column $node, RuntimeGraph $graph): float
    {
        $sum = 0.0;
        $edges = $graph->getIncomingEdges($node->id);
        foreach ($edges as $edge) {
            if ($edge->type === 'lateral-inhib') {
                $sourceNode = $graph->getNode($edge->source);
                if ($sourceNode) {
                    // Lateral inhibition driven by source L5 activation
                    // Competing nodes directly inhibit via their output strength
                    $sum += $sourceNode->L5->activation * $edge->weight;
                }
            }
        }

        return $sum;
    }

    /**
     * Update PV interneurons based on competitive input
     *
     * PV receives input from competing nodes via lateral-inhib edges.
     * This drives inhibitory competition between overlapping L2 nodes.
     */
    private function updateCompetitivePV(Column $node, RuntimeGraph $graph, float $dt): void
    {
        $competitiveInput = 0.0;
        $edges = $graph->getIncomingEdges($node->id);

        foreach ($edges as $edge) {
            if ($edge->type === 'lateral-inhib') {
                $sourceNode = $graph->getNode($edge->source);
                if ($sourceNode) {
                    // PV driven by competing node's activation
                    $competitiveInput += $sourceNode->activation * $edge->weight;
                }
            }
        }

        // Update PV with competitive input
        if ($competitiveInput > 0.0) {
            $node->PV->update($competitiveInput, $dt);
        }
    }

    // === RNT Neural Dynamics ===

    /**
     * Update RNT construction node dynamics
     *
     * RNT nodes have three activation modes based on rnt_status:
     * - 'single': Strong bottom-up from L1, no prediction (1.5x modulation)
     * - 'partial_and': Weak bottom-up from one operand, strong prediction error (0.3x or 1.8x)
     * - 'complete_and': Very strong bottom-up from both operands (2.0x modulation)
     *
     * @param  Column  $node  RNT L2 node
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  float  $dt  Time step
     */
    private function updateRNTNode(Column $node, RuntimeGraph $graph, float $dt): void
    {
        // Collect bottom-up input based on RNT status
        $bottomUp = $this->collectRNTBottomUp($node, $graph);

        // Check prediction satisfaction (for partial AND nodes)
        $predictionError = $this->checkRNTPrediction($node, $graph);

        // Update interneurons and competitive PV
        $this->updateInterneurons($node, $predictionError, $dt);
        $this->updateCompetitivePV($node, $graph, $dt);

        // L23: Prediction error + bottom-up evidence
        $L23Input = match ($node->rnt_status) {
            'single' => $bottomUp['strength'] ?? 0.0,
            'partial_and' => ($bottomUp['strength'] ?? 0.0) * 0.5 + $predictionError * 1.5,
            'complete_and' => $bottomUp['strength'] ?? 0.0,
            default => 0.0
        };

        $node->L23->update($L23Input, $dt);

        // L5: State maintenance with error modulation
        $errorModulation = match ($node->rnt_status) {
            'single' => 1.5,           // Favor single-element constructions
            'partial_and' => ($predictionError === 0.0 ? 1.8 : 0.3), // Strong if prediction matches
            'complete_and' => 2.0,      // Strongly favor completed compositions
            default => 1.0
        };

        // Collect lateral connections
        $lateralExcite = $this->collectLateralExcitatory($node, $graph);
        $lateralInhib = $this->collectLateralInhibitory($node, $graph);

        // L5 input with competition
        $L5Input = ($node->L23->activation + $lateralExcite) / (1 + $lateralInhib);
        $L5Input *= $errorModulation;

        $node->L5->update($L5Input, $dt);

        // Node activation follows L5
        $node->activation = $node->L5->activation;
    }

    /**
     * Collect bottom-up input for RNT construction nodes
     *
     * Different collection strategies based on rnt_status:
     * - 'single': Bottom-up from single L1 source node
     * - 'partial_and': Weak bottom-up from one operand (left or right)
     * - 'complete_and': Strong bottom-up from both left and right operands
     *
     * @param  Column  $node  RNT L2 node
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Bottom-up info with 'strength' key
     */
    private function collectRNTBottomUp(Column $node, RuntimeGraph $graph): array
    {
        if ($node->rnt_status === 'single') {
            // Single-element: bottom-up from matching L1 node
            $sourceId = $node->bindings['source'] ?? null;
            if ($sourceId) {
                $sourceNode = $graph->getNode($sourceId);
                if ($sourceNode && $sourceNode->activation > 0.7) {
                    return ['strength' => $sourceNode->activation];
                }
            }

            return ['strength' => 0.0];
        }

        if ($node->rnt_status === 'partial_and') {
            // Partial AND: bottom-up from one operand (left or right)
            $operandId = $node->bindings['left_operand'] ?? $node->bindings['right_operand'] ?? null;
            if ($operandId) {
                $operandNode = $graph->getNode($operandId);
                if ($operandNode && $operandNode->activation > 0.6) {
                    // Weaker signal from partial composition
                    return ['strength' => $operandNode->activation * 0.6];
                }
            }

            return ['strength' => 0.0];
        }

        if ($node->rnt_status === 'complete_and') {
            // Complete AND: bottom-up from both left and right operands
            $leftId = $node->bindings['left_operand'] ?? null;
            $rightId = $node->bindings['right_operand'] ?? null;

            if ($leftId && $rightId) {
                $leftNode = $graph->getNode($leftId);
                $rightNode = $graph->getNode($rightId);

                if ($leftNode && $rightNode) {
                    // Take minimum of both operands (both must be active)
                    $minActivation = min($leftNode->activation, $rightNode->activation);
                    if ($minActivation > 0.7) {
                        // Boost signal for complete composition
                        return ['strength' => $minActivation * 1.2];
                    }
                }
            }

            return ['strength' => 0.0];
        }

        // Unknown RNT status
        return ['strength' => 0.0];
    }

    /**
     * Check if RNT prediction is satisfied
     *
     * Only partial AND nodes have predictions (expecting right or left operand).
     * Returns:
     * - 0.0 if prediction satisfied (expected OR node is active)
     * - 1.0 if prediction not satisfied (expected OR node missing or inactive)
     *
     * @param  Column  $node  RNT L2 node
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return float Binary error (0.0 = satisfied, 1.0 = not satisfied)
     */
    private function checkRNTPrediction(Column $node, RuntimeGraph $graph): float
    {
        // Only partial AND nodes have predictions
        if ($node->rnt_status !== 'partial_and') {
            return 0.0;
        }

        // Get expected operand (right or left)
        $expected = $node->rnt_expected_right ?? $node->rnt_expected_left ?? null;
        if ($expected === null) {
            return 0.0;
        }

        // Extract expected OR node ID
        $expectedOrNodeId = $expected['or_node_id'] ?? null;
        if ($expectedOrNodeId === null) {
            return 0.0;
        }

        // Check if expected OR node is active in the graph
        $l2Nodes = $graph->getNodesByLevel('L2');
        foreach ($l2Nodes as $l2) {
            if ($l2->rnt_or_node_id === $expectedOrNodeId && $l2->activation > 0.5) {
                return 0.0;  // Prediction satisfied! Expected OR node is active
            }
        }

        // Expected OR node not found or inactive - prediction error
        return 1.0;
    }
}

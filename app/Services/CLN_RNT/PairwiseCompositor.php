<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\ConnectionEdge;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Pairwise Compositor for CLN v3
 *
 * Creates L2 nodes (paired element compositions) when two L1 nodes successfully combine.
 *
 * Key principles:
 * - L2 nodes represent the combination of TWO L1 nodes
 * - Each L2 node predicts the next element in a construction sequence
 * - Multiple L2 nodes can exist for a sentence (one per confirmed element pair)
 * - L2 is a compositional stage, not a hierarchical level
 */
class PairwiseCompositor
{
    public function __construct(
        private PatternGraphQuerier $querier,
        private ?RNTGraphQuerier $rntQuerier = null
    ) {
        // RNT querier is injected by CLNParser if RNT is enabled
    }

    /**
     * Attempt to create L2 nodes from adjacent nodes via pair-wise composition
     *
     * CLN v3 uses LATERAL composition with only two stages:
     * - L1 + L1 → L2 (initial pair via pattern matching)
     * - L2 + L1 → L2 (lateral expansion when prediction matches)
     * - L1 + L2 → L2 (lateral expansion, reverse order)
     *
     * There is NO L3 level - composition spreads laterally across L2.
     *
     * RNT uses a three-strategy approach:
     * - DATA→OR: Single-element constructions
     * - OR→AND (partial): Partial AND nodes awaiting completion
     * - AND→OR (complete): Complete AND compositions
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created L2 nodes
     */
    public function composePairs(RuntimeGraph $graph): array
    {
        // Use RNT composition if RNT querier is available
//        if ($this->rntQuerier !== null) {
            return $this->composeWithRNT($graph);
//        }

        // === CLN v3 Composition (Backward Compatibility) ===

//        $createdNodes = [];
//
//        // Strategy 0: Single-element patterns (START→X→END)
//        // Create L2 nodes from single L1 nodes that match single-element patterns
//        $singleElementNodes = $this->composeSingleElements($graph);
//        $createdNodes = array_merge($createdNodes, $singleElementNodes);
//
//        // Strategy 1: Pattern-driven composition (mainly L1+L1→L2)
//        // Try all pairs of nodes where spans are adjacent
//        // Re-fetch all nodes after creating single-element constructions
//        $allNodes = $graph->getAllNodes();
//
//        foreach ($allNodes as $node1) {
//            foreach ($allNodes as $node2) {
//                // Check if spans are adjacent: node1 ends and node2 begins right after
//                if ($node2->span[0] === $node1->span[1] + 1) {
//                    $l2Node = $this->tryCompose($graph, $node1, $node2);
//                    if ($l2Node !== null) {
//                        $createdNodes[] = $l2Node;
//                    }
//                }
//            }
//        }
//
//        // Strategy 2: Prediction-driven lateral expansion (L2+L1→L2)
//        // For each L2 node with a prediction, check if next element matches
//        $lateralNodes = $this->tryLateralExpansion($graph);
//        $createdNodes = array_merge($createdNodes, $lateralNodes);
//
//        return $createdNodes;
    }

    /**
     * Compose using RNT (Relational Network Type) pattern graph
     *
     * Three-strategy approach:
     * 1. DATA→OR matches (single-element constructions)
     * 2. Create partial AND nodes (one operand active)
     * 3. Complete AND nodes (both operands active)
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created L2 nodes
     */
    private function composeWithRNT(RuntimeGraph $graph): array
    {
        $createdNodes = [];

        // Strategy 1: DATA→OR matches (single-element constructions)
        $dataMatches = $this->composeFromDataMatches($graph);
        $createdNodes = array_merge($createdNodes, $dataMatches);

        // Strategy 1b: DATA+DATA→AND intermediate compositions (for MWEs)
        $intermediateNodes = $this->createIntermediateAndNodes($graph);
        $createdNodes = array_merge($createdNodes, $intermediateNodes);

        // Strategy 2: Create partial AND nodes (one operand active)
        $partialNodes = $this->createPartialAndNodes($graph);
        $createdNodes = array_merge($createdNodes, $partialNodes);

        // Strategy 3: Complete AND nodes (both operands active)
        $completedNodes = $this->completeAndNodes($graph);
        $createdNodes = array_merge($createdNodes, $completedNodes);

        // Strategy 4: Create alternative OR representations (OR→OR edges)
        $alternativeNodes = $this->createAlternativeOrNodes($graph);
        $createdNodes = array_merge($createdNodes, $alternativeNodes);

        // Strategy 4b: SEQUENCER→OR compositions (SEQUENCER can feed into OR nodes)
        $sequencerOrNodes = $this->createOrNodesFromSequencers($graph);
        $createdNodes = array_merge($createdNodes, $sequencerOrNodes);

        // Strategy 5: Create SEQUENCER nodes (accumulate activation, propagate when ready)
        $sequencerNodes = $this->createSequencerNodes($graph);
        $createdNodes = array_merge($createdNodes, $sequencerNodes);

        return $createdNodes;
    }

    /**
     * Compose single-element patterns (START→X→END)
     *
     * Creates L2 nodes from single L1 nodes that match single-element patterns.
     * For example: DET → "MOD-" construction, NOUN → "HEAD" construction.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created L2 nodes from single elements
     */
    private function composeSingleElements(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l1Nodes = $graph->getNodesByLevel('L1');

        foreach ($l1Nodes as $l1Node) {
            // Query patterns starting from START that match this L1 node
            $matches = $this->findSingleElementPatterns($l1Node);

            foreach ($matches as $match) {
                // Check if L2 node already exists for this span and pattern
                $existing = $graph->getNodesInSpan($l1Node->span[0], $l1Node->span[1]);
                $alreadyExists = false;
                foreach ($existing as $existingNode) {
                    if ($existingNode->cortical_level === 'L2' &&
                        ($existingNode->bindings['pattern_id'] ?? null) === $match['pattern_id']) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if ($alreadyExists) {
                    continue;
                }

                // Create L2 node for single-element construction
                $l2Node = $graph->addL2Node(
                    startPos: $l1Node->span[0],
                    endPos: $l1Node->span[1],
                    constructionType: $match['construction_type'],
                    bindings: [
                        'source' => $l1Node->id,
                        'pattern_id' => $match['pattern_id'],
                        'single_element' => true,
                    ],
                    features: [
                        'pattern_id' => $match['pattern_id'],
                        'ce_label' => $match['ce_label'] ?? null,
                    ]
                );

                // Set activation from L1 node
                $initialActivation = $l1Node->activation * 0.95;
                $l2Node->activation = $initialActivation;
                $l2Node->L5->activation = $initialActivation;
                $l2Node->L23->activation = $initialActivation * 0.8;

                // Create edge from L1 to L2
                $graph->addEdge(new ConnectionEdge(
                    source: $l1Node->id,
                    target: $l2Node->id,
                    type: 'feedforward',
                    weight: 1.0
                ));

                $createdNodes[] = $l2Node;
            }
        }

        return $createdNodes;
    }

    /**
     * Find single-element patterns (START→X→END) for a node
     *
     * @param  Column  $node  L1 node to match
     * @return array Array of matching patterns
     */
    private function findSingleElementPatterns(Column $node): array
    {
        // Get START node
        $startNode = $this->querier->getStartNode();
        if (! $startNode) {
            return [];
        }

        // Build element for querying (from START's perspective, we look for patterns)
        // Query for edges from START that match this node's characteristics
        $elementPOS = $node->features['pos'] ?? null;
        $elementValue = $node->features['value'] ?? null;

        $matches = [];

        // Query for LITERAL patterns: START → LITERAL(value) → END
        if ($elementValue !== null) {
            $literalPatterns = $this->querier->queryPatternsFromStart('LITERAL', $elementValue);
            $matches = array_merge($matches, $literalPatterns);
        }

        // Query for SLOT patterns: START → SLOT(POS) → END
        if ($elementPOS !== null) {
            $slotPatterns = $this->querier->queryPatternsFromStart('SLOT', null, $elementPOS);
            $matches = array_merge($matches, $slotPatterns);
        }

        // Filter to only single-element patterns (those that go to END)
        $singleElementMatches = [];
        foreach ($matches as $match) {
            if ($match['target_type'] === 'END') {
                $singleElementMatches[] = [
                    'pattern_id' => $match['pattern_id'],
                    'construction_type' => $this->extractConstructionTypeName($match['pattern_id']),
                    'ce_label' => $match['ce_label'] ?? null,
                ];
            }
        }

        return $singleElementMatches;
    }

    /**
     * Extract construction type name from pattern ID
     *
     * @param  int  $patternId  Pattern ID
     * @return string Construction type name
     */
    private function extractConstructionTypeName(int $patternId): string
    {
        // Query the construction table for the name
        $construction = $this->querier->getConstructionByPatternId($patternId);

        if ($construction && ! empty($construction->name)) {
            return $construction->name;
        }

        // Fallback to pattern_id
        return "pattern_{$patternId}";
    }

    /**
     * Lateral expansion: L2 nodes predict next element, create new L2 when matched
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created L2 nodes via lateral expansion
     */
    private function tryLateralExpansion(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            // Skip if no prediction
            if ($l2Node->predicted_element === null) {
                continue;
            }

            $prediction = $l2Node->predicted_element;
            $predictedPosition = $l2Node->span[1] + 1;

            // Get L1 nodes at predicted position
            $nodesAtPosition = $graph->getNodesAtPosition($predictedPosition);

            foreach ($nodesAtPosition as $l1Node) {
                // Check if L1 node matches prediction
                if (! $this->nodeMatchesPrediction($l1Node, $prediction)) {
                    continue;
                }

                // Create new L2 node via lateral expansion
                $newSpan = [$l2Node->span[0], $l1Node->span[1]];

                // Check if this span already exists
                $existing = $graph->getNodesInSpan($newSpan[0], $newSpan[1]);
                if (! empty($existing)) {
                    continue; // Already expanded
                }

                // Get pattern info from the L2 node
                $patternId = $l2Node->bindings['pattern_id'] ?? null;
                if ($patternId === null) {
                    continue;
                }

                // Create expanded L2 node
                $expandedL2 = $graph->addL2Node(
                    startPos: $newSpan[0],
                    endPos: $newSpan[1],
                    constructionType: $l2Node->construction_type,
                    bindings: [
                        'first' => $l2Node->id,
                        'second' => $l1Node->id,
                        'pattern_id' => $patternId,
                        'lateral_expansion' => true,
                    ],
                    features: [
                        'pattern_id' => $patternId,
                        'ce_label' => $l2Node->features['ce_label'] ?? null,
                    ]
                );

                // Set activation from constituent nodes
                $initialActivation = min($l2Node->activation, $l1Node->activation) * 0.95;
                $expandedL2->activation = $initialActivation;
                $expandedL2->L5->activation = $initialActivation;
                $expandedL2->L23->activation = $initialActivation * 0.8;

                // Check if pattern continues (query next element from matched node)
                $nextInfo = $this->querier->queryNextInPattern(
                    $prediction['node_id'],
                    $patternId
                );

                if ($nextInfo && $nextInfo->node_type !== 'END') {
                    $expandedL2->predicted_element = [
                        'type' => $nextInfo->node_type,
                        'value' => $nextInfo->element_value,
                        'node_id' => $nextInfo->target_node_id,
                        'spec' => $nextInfo->target_spec ?? null,
                    ];
                }

                $createdNodes[] = $expandedL2;
            }
        }

        return $createdNodes;
    }

    /**
     * Check if node matches prediction
     *
     * @param  Column  $node  Node to check
     * @param  array  $prediction  Prediction array
     * @return bool True if matches
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

        return false;
    }

    /**
     * Try to compose two nodes into an L2 node
     *
     * Supports lateral composition:
     * - L1 + L1 → L2 (initial pair)
     * - L2 + L1 → L2 (lateral expansion)
     * - L1 + L2 → L2 (lateral expansion)
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  Column  $node1  First node (L1 or L2)
     * @param  Column  $node2  Second node (L1 or L2)
     * @return Column|null Created L2 node or null if composition failed
     */
    private function tryCompose(
        RuntimeGraph $graph,
        Column $node1,
        Column $node2
    ): ?Column {
        // Check if this pair already composed
        $existingL2 = $graph->getNodesInSpan($node1->span[0], $node2->span[1]);
        if (! empty($existingL2)) {
            return null; // Already composed
        }

        // Query pattern graph for valid transitions from node1 to node2
        $matches = $this->findPairPatterns($node1, $node2);

        if (empty($matches)) {
            return null; // No valid pattern
        }

        // Use first matching pattern (could implement competition later)
        $match = $matches[0];

        // ALWAYS create L2 node - lateral composition, not hierarchical
        $l2Node = $graph->addL2Node(
            startPos: $node1->span[0],
            endPos: $node2->span[1],
            constructionType: $match['construction_type'],
            bindings: [
                'first' => $node1->id,
                'second' => $node2->id,
                'pattern_id' => $match['pattern_id'],
            ],
            features: [
                'pattern_id' => $match['pattern_id'],
                'ce_label' => $match['ce_label'] ?? null,
            ]
        );

        // Set activation and initialize neural populations
        $initialActivation = min($node1->activation, $node2->activation) * 0.9;
        $l2Node->activation = $initialActivation;

        // Initialize L5 with the combined activation to maintain state
        $l2Node->L5->activation = $initialActivation;

        // Initialize L23 with moderate activation (bottom-up evidence)
        $l2Node->L23->activation = $initialActivation * 0.8;

        // Check if pattern continues (predicts next element)
        $nextInfo = $this->querier->queryNextInPattern(
            $match['to_node_id'],
            $match['pattern_id']
        );

        if ($nextInfo && $nextInfo->node_type !== 'END') {
            // L2 node predicts next element (LITERAL, SLOT, or CONSTRUCTION_REF)
            $l2Node->predicted_element = [
                'type' => $nextInfo->node_type,
                'value' => $nextInfo->element_value,
                'node_id' => $nextInfo->target_node_id,
                'spec' => $nextInfo->target_spec ?? null,
            ];
        }

        // Create edges from constituent nodes to L2 node
        $graph->addEdge(new ConnectionEdge(
            source: $node1->id,
            target: $l2Node->id,
            type: 'feedforward',
            weight: 1.0
        ));

        $graph->addEdge(new ConnectionEdge(
            source: $node2->id,
            target: $l2Node->id,
            type: 'feedforward',
            weight: 1.0
        ));

        return $l2Node;
    }

    /**
     * Find patterns that match a pair of nodes
     *
     * Queries for patterns starting from node1:
     * - LITERAL and SLOT matches for word-based L1 nodes
     * - CONSTRUCTION_REF matches for single-element construction L1 nodes
     *
     * Then checks if node2 matches the pattern's target.
     *
     * Supports lateral composition:
     * - L1(word) + L1(word) → L2
     * - L1(construction) + L1(word) → L2
     * - L2 + L1 → L2 (lateral expansion)
     *
     * @param  Column  $node1  First node (L1 or L2)
     * @param  Column  $node2  Second node (L1 or L2)
     * @return array Array of matching patterns
     */
    private function findPairPatterns(Column $node1, Column $node2): array
    {
        // Build query element for node1
        $element = [
            'type' => $node1->features['type'] ?? null,
            'value' => $node1->features['value'] ?? null,
            'pos' => $node1->features['pos'] ?? null,
        ];

        // L1 single-element constructions or L2 nodes use construction_type for matching
        // This enables CONSTRUCTION_REF pattern matching
        if ($node1->cortical_level === 'L2' || $node1->construction_type !== 'literal') {
            $element['construction_type'] = $node1->construction_type;
        }

        // Get patterns starting from node1's element
        $patternsFromNode1 = $this->querier->queryPatternsForElement($element);

        $matches = [];

        foreach ($patternsFromNode1 as $pattern) {
            // Check if this pattern's next node matches node2
            if ($this->nodeMatchesTarget($node2, $pattern)) {
                $matches[] = [
                    'pattern_id' => $pattern->pattern_id,
                    'to_node_id' => $pattern->to_node_id,
                    'construction_type' => $this->extractConstructionType($pattern),
                    'ce_label' => $pattern->ce_label ?? null,
                ];
            }
        }

        return $matches;
    }

    /**
     * Check if a node matches a pattern target
     *
     * Supports multiple matching modes:
     * - LITERAL: exact word match (L1 word nodes)
     * - SLOT: POS-based match (L1 word nodes)
     * - CONSTRUCTION_REF: construction type match (L1 single-element constructions or L2 nodes)
     *
     * @param  Column  $node  Node to check (L1 or L2)
     * @param  object  $pattern  Pattern from query result
     * @return bool True if node matches pattern target
     */
    private function nodeMatchesTarget(Column $node, object $pattern): bool
    {
        $nodeValue = $node->features['value'] ?? null;
        $nodePOS = $node->features['pos'] ?? null;
        $nodeConstructionType = $node->construction_type;

        $targetType = $pattern->target_type ?? null;
        $targetValue = $pattern->target_value ?? null;

        // LITERAL match: exact word value
        if ($targetType === 'LITERAL') {
            return $nodeValue === $targetValue;
        }

        // SLOT match: POS-based matching
        if ($targetType === 'SLOT') {
            // Parse target specification to get POS requirement
            $targetSpec = $pattern->target_spec ?? null;
            if ($targetSpec) {
                $spec = is_string($targetSpec) ? json_decode($targetSpec, true) : $targetSpec;
                $requiredPOS = $spec['pos'] ?? null;

                return $nodePOS === $requiredPOS;
            }

            return false;
        }

        // CONSTRUCTION_REF match: construction type matching
        if ($targetType === 'CONSTRUCTION_REF') {
            // Parse target specification to get construction name
            $targetSpec = $pattern->target_spec ?? null;
            if ($targetSpec) {
                $spec = is_string($targetSpec) ? json_decode($targetSpec, true) : $targetSpec;
                $requiredConstructionName = $spec['construction_name'] ?? null;

                return $nodeConstructionType === $requiredConstructionName;
            }

            return false;
        }

        // Other types (END, etc.)
        return false;
    }

    /**
     * Extract construction type from pattern
     *
     * @param  object  $pattern  Pattern object
     * @return string Construction type
     */
    private function extractConstructionType(object $pattern): string
    {
        // Query the construction table for the name
        $construction = $this->querier->getConstructionByPatternId($pattern->pattern_id);

        if ($construction && ! empty($construction->name)) {
            return $construction->name;
        }

        // Fallback to pattern_id
        return "pattern_{$pattern->pattern_id}";
    }

    // === RNT Composition Strategies ===

    /**
     * Strategy 1: Create L2 nodes from DATA→OR matches (single-element constructions)
     *
     * For each L1 node:
     *   1. Find matching DATA nodes (LITERAL word or SLOT POS)
     *   2. Get OR nodes via 'alternative'/'single' edges
     *   3. Create L2 node for each OR node
     *   4. Mark as rnt_status='single'
     *   5. Store rnt_or_node_id and rnt_data_node_id
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created L2 nodes
     */
    private function composeFromDataMatches(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l1Nodes = $graph->getNodesByLevel('L1');

        foreach ($l1Nodes as $l1Node) {
            // Find matching DATA nodes in RNT graph
            $dataNodes = $this->rntQuerier->findMatchingDataNodes($l1Node);

            if (empty($dataNodes)) {
                continue;
            }

            // Get OR nodes reachable from these DATA nodes
            $dataNodeIds = array_column($dataNodes, 'node_id');
            $orNodes = $this->rntQuerier->getOrNodesFromData($dataNodeIds);

            foreach ($orNodes as $orNode) {
                // Check if L2 node already exists for this OR node at this span
                $existingNodes = $graph->getNodesInSpan($l1Node->span[0], $l1Node->span[1]);
                $alreadyExists = false;

                foreach ($existingNodes as $existing) {
                    if ($existing->cortical_level === 'L2' &&
                        $existing->rnt_or_node_id === $orNode['or_node_id']) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if ($alreadyExists) {
                    continue;
                }

                // Create L2 node for single-element construction
                $constructionName = $orNode['construction_name'] ?? "or_node_{$orNode['or_node_id']}";

                $l2Node = $graph->addL2Node(
                    startPos: $l1Node->span[0],
                    endPos: $l1Node->span[1],
                    constructionType: $constructionName,
                    bindings: [
                        'source' => $l1Node->id,
                        'pattern_id' => $orNode['pattern_id'],
                        'single_element' => true,
                    ],
                    features: [
                        'pattern_id' => $orNode['pattern_id'],
                        'construction_name' => $constructionName,
                    ]
                );

                // Set RNT properties
                $l2Node->rnt_or_node_id = $orNode['or_node_id'];
                $l2Node->rnt_data_node_id = $dataNodes[0]['node_id']; // Use first matching DATA node
                $l2Node->rnt_status = 'single';

                // Set activation from L1 node
                $initialActivation = $l1Node->activation * 0.95;
                $l2Node->activation = $initialActivation;
                $l2Node->L5->activation = $initialActivation;
                $l2Node->L23->activation = $initialActivation * 0.8;

                // Create feedforward edge from L1 to L2
                $graph->addEdge(new ConnectionEdge(
                    source: $l1Node->id,
                    target: $l2Node->id,
                    type: 'feedforward',
                    weight: 1.0
                ));

                $createdNodes[] = $l2Node;
            }
        }

        return $createdNodes;
    }

    /**
     * Strategy 1b: Create intermediate AND nodes from DATA+DATA (for MWEs)
     *
     * For L1 nodes that match DATA nodes:
     *   1. Find AND nodes expecting these DATA nodes as left/right operands
     *   2. Check if both operands are active (adjacent L1 nodes)
     *   3. Create intermediate AND L2 node (no OR composition yet)
     *   4. Mark as rnt_status='intermediate_and'
     *   5. These will later be used to complete higher-level AND→OR compositions
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created intermediate AND nodes
     */
    private function createIntermediateAndNodes(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l1Nodes = $graph->getNodesByLevel('L1');

        foreach ($l1Nodes as $l1Node) {
            // Find matching DATA nodes in RNT graph
            $dataNodes = $this->rntQuerier->findMatchingDataNodes($l1Node);

            if (empty($dataNodes)) {
                continue;
            }

            foreach ($dataNodes as $dataNode) {
                $dataNodeId = $dataNode['node_id'];

                // Find AND nodes expecting this DATA as left operand
                $andNodesLeft = $this->rntQuerier->getAndNodesExpectingDataLeft($dataNodeId);

                foreach ($andNodesLeft as $andNode) {
                    $expectedRightNodeId = $andNode['expected_right_node_id'];
                    $expectedRightType = $andNode['expected_right_type'];

                    if ($expectedRightType !== 'DATA') {
                        continue; // Only handle DATA+DATA for intermediate compositions
                    }

                    // Check if there's an L1 node at the next position matching the expected right DATA
                    $nextPosition = $l1Node->span[1] + 1;
                    $rightL1Nodes = array_filter($l1Nodes, fn ($n) => $n->span[0] === $nextPosition);

                    foreach ($rightL1Nodes as $rightL1Node) {
                        // Check if this L1 matches the expected DATA node
                        $rightDataNodes = $this->rntQuerier->findMatchingDataNodes($rightL1Node);
                        $rightMatches = array_filter($rightDataNodes, fn ($d) => $d['node_id'] === $expectedRightNodeId);

                        if (empty($rightMatches)) {
                            continue; // Right L1 doesn't match expected DATA
                        }

                        // Check if intermediate AND node already exists
                        $existingNodes = $graph->getNodesInSpan($l1Node->span[0], $rightL1Node->span[1]);
                        $alreadyExists = false;

                        foreach ($existingNodes as $existing) {
                            if ($existing->cortical_level === 'L2' &&
                                $existing->rnt_and_node_id === $andNode['and_node_id']) {
                                $alreadyExists = true;
                                break;
                            }
                        }

                        if ($alreadyExists) {
                            continue;
                        }

                        // Create intermediate AND node (no OR composition yet)
                        $intermediateNode = $graph->addL2Node(
                            startPos: $l1Node->span[0],
                            endPos: $rightL1Node->span[1],
                            constructionType: "intermediate_and_{$andNode['and_node_id']}",
                            bindings: [
                                'left_data_source' => $l1Node->id,
                                'right_data_source' => $rightL1Node->id,
                                'pattern_id' => $andNode['pattern_id'],
                                'intermediate_and' => true,
                            ],
                            features: [
                                'pattern_id' => $andNode['pattern_id'],
                                'and_node_id' => $andNode['and_node_id'],
                            ]
                        );

                        // Set RNT properties
                        $intermediateNode->rnt_and_node_id = $andNode['and_node_id'];
                        $intermediateNode->rnt_status = 'intermediate_and';

                        // Medium activation for intermediate nodes
                        $intermediateNode->activation = 0.6;
                        $intermediateNode->L5->activation = 0.6;
                        $intermediateNode->L23->activation = 0.5;

                        // Create feedforward edges from both L1 nodes
                        $graph->addEdge(new ConnectionEdge(
                            source: $l1Node->id,
                            target: $intermediateNode->id,
                            type: 'feedforward',
                            weight: 1.0
                        ));

                        $graph->addEdge(new ConnectionEdge(
                            source: $rightL1Node->id,
                            target: $intermediateNode->id,
                            type: 'feedforward',
                            weight: 1.0
                        ));

                        $createdNodes[] = $intermediateNode;
                    }
                }
            }
        }

        return $createdNodes;
    }

    /**
     * Strategy 2: Create partial AND nodes when one operand (left or right) is active
     *
     * For each L2 with rnt_or_node_id:
     *   1. Find AND nodes expecting this OR as left operand
     *   2. Create partial L2 for each AND node
     *   3. Store expected right operand in rnt_expected_right
     *   4. Mark as rnt_status='partial_and'
     *   5. Low initial activation (0.3)
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created partial AND L2 nodes
     */
    private function createPartialAndNodes(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            // Only process RNT OR nodes or intermediate AND nodes
            if (! $l2Node->isRNTConstruction() || $l2Node->isPartialAnd()) {
                continue;
            }

            $andNodesLeft = [];

            // Case 1: OR nodes expecting to compose with other nodes
            if ($l2Node->rnt_or_node_id !== null) {
                $orNodeId = $l2Node->rnt_or_node_id;
                $andNodesLeft = $this->rntQuerier->getAndNodesExpectingLeft($orNodeId);
            }

            // Case 2: Intermediate AND nodes that can be left operands for higher-level ANDs
            if ($l2Node->rnt_status === 'intermediate_and' && $l2Node->rnt_and_node_id !== null) {
                $andNodeId = $l2Node->rnt_and_node_id;
                $patternId = $l2Node->bindings['pattern_id'] ?? null;

                if ($patternId !== null) {
                    $higherAndNodes = $this->rntQuerier->getAndNodesExpectingAndLeft($andNodeId, $patternId);
                    $andNodesLeft = array_merge($andNodesLeft, $higherAndNodes);
                }
            }

            foreach ($andNodesLeft as $andNode) {
                // Check if partial AND node already exists for this AND node at this position
                $existingPartial = null;
                foreach ($l2Nodes as $existing) {
                    if ($existing->rnt_and_node_id === $andNode['and_node_id'] &&
                        $existing->isPartialAnd() &&
                        $existing->span[0] === $l2Node->span[0] &&
                        $existing->span[1] === $l2Node->span[1]) {
                        $existingPartial = $existing;
                        break;
                    }
                }

                if ($existingPartial !== null) {
                    continue; // Already have partial for this AND node at this position
                }

                // Create partial AND node
                $partialNode = $graph->addL2Node(
                    startPos: $l2Node->span[0],
                    endPos: $l2Node->span[1],
                    constructionType: "partial_and_{$andNode['and_node_id']}",
                    bindings: [
                        'left_operand' => $l2Node->id,
                        'pattern_id' => $andNode['pattern_id'],
                        'partial_and' => true,
                    ],
                    features: [
                        'pattern_id' => $andNode['pattern_id'],
                        'and_node_id' => $andNode['and_node_id'],
                    ]
                );

                // Set RNT properties
                $partialNode->rnt_and_node_id = $andNode['and_node_id'];
                $partialNode->rnt_status = 'partial_and';

                // Store expected right operand
                $expectedRightNodeId = $andNode['expected_right_node_id'] ?? $andNode['expected_right_or_node'] ?? null;
                $expectedRightType = $andNode['expected_right_type'] ?? 'OR';

                if ($expectedRightNodeId !== null) {
                    if ($expectedRightType === 'OR') {
                        $rightConstructionName = $this->rntQuerier->getConstructionName($expectedRightNodeId);
                        $partialNode->rnt_expected_right = [
                            'type' => 'OR',
                            'or_node_id' => $expectedRightNodeId,
                            'construction_name' => $rightConstructionName,
                            'pattern_id' => $andNode['pattern_id'],
                            'position_after' => $l2Node->span[1], // Must appear after this position
                            'max_distance' => 999, // No limit by default (allow intervening material)
                        ];
                    } elseif ($expectedRightType === 'DATA') {
                        $expectedRightSpec = $andNode['expected_right_spec'] ?? [];
                        $partialNode->rnt_expected_right = [
                            'type' => 'DATA',
                            'data_node_id' => $expectedRightNodeId,
                            'specification' => $expectedRightSpec,
                            'pattern_id' => $andNode['pattern_id'],
                            'position_after' => $l2Node->span[1], // Must appear after this position
                            'max_distance' => 999, // No limit by default (allow intervening material)
                        ];
                    }

                    // Store as predicted_element for compatibility with existing dynamics
                    $partialNode->predicted_element = $partialNode->rnt_expected_right;
                }

                // Low initial activation for partial nodes
                $partialNode->activation = 0.3;
                $partialNode->L5->activation = 0.3;
                $partialNode->L23->activation = 0.2;

                // Create feedforward edge from left operand
                $graph->addEdge(new ConnectionEdge(
                    source: $l2Node->id,
                    target: $partialNode->id,
                    type: 'feedforward',
                    weight: 1.0
                ));

                $createdNodes[] = $partialNode;
            }
        }

        return $createdNodes;
    }

    /**
     * Strategy 3: Complete AND nodes when both operands are active
     *
     * NEW APPROACH: Active completion search with position-flexible predictions
     *
     * Instead of checking if partial AND nodes have their expected element at a specific position,
     * we use ACTIVE SEARCH: When an OR construction completes, we search for partial AND nodes
     * waiting for it, allowing for variable-length intervening material.
     *
     * For each completed OR construction:
     *   1. Find partial AND nodes waiting for this OR
     *   2. Check position constraints (OR must come after partial's span)
     *   3. Check distance constraints (within max_distance if specified)
     *   4. Complete the AND composition if constraints satisfied
     *   5. Follow 'composition' edge to target OR node
     *   6. Create new L2 for composed construction
     *   7. Mark as rnt_status='complete_and'
     *   8. Higher initial activation (0.7)
     *
     * This implements "Working Memory via Persistent Error" - partial AND nodes remain
     * active with high error state until their prediction is satisfied, even with
     * intervening material.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of completed AND L2 nodes
     */
    private function completeAndNodes(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        // Get all completed OR constructions (single-element RNT with activation > 0.5)
        $completedOrNodes = array_filter($l2Nodes, function ($node) {
            return $node->isSingleElementRNT() && $node->activation > 0.5;
        });

        // For each completed OR, find partial ANDs waiting for it
        foreach ($completedOrNodes as $orNode) {
            $waitingPartials = $this->findWaitingPartialAnds($graph, $orNode);

            foreach ($waitingPartials as $partial) {
                // Check if this partial can be completed with this OR node
                if (! $this->canComplete($partial, $orNode)) {
                    continue; // Position constraints not satisfied
                }

                // Complete the partial AND composition
                $completed = $this->completePartialAnd($graph, $partial, $orNode);
                if ($completed !== null) {
                    $createdNodes[] = $completed;
                }
            }
        }

        return $createdNodes;
    }

    /**
     * Find partial AND nodes waiting for a specific OR node
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  Column  $orNode  Completed OR node to search for
     * @return array Array of partial AND nodes waiting for this OR
     */
    private function findWaitingPartialAnds(RuntimeGraph $graph, Column $orNode): array
    {
        $waiting = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $node) {
            if (! $node->isPartialAnd()) {
                continue;
            }

            // Check if this partial AND is waiting for the OR node
            $expectedRight = $node->rnt_expected_right;
            $expectedLeft = $node->rnt_expected_left;

            if ($expectedRight) {
                $expectedOrId = $expectedRight['or_node_id'] ?? null;
                if ($expectedOrId === $orNode->rnt_or_node_id) {
                    $waiting[] = $node;
                }
            } elseif ($expectedLeft) {
                $expectedOrId = $expectedLeft['or_node_id'] ?? null;
                if ($expectedOrId === $orNode->rnt_or_node_id) {
                    $waiting[] = $node;
                }
            }
        }

        return $waiting;
    }

    /**
     * Check if a partial AND can be completed with an OR node
     *
     * Verifies position constraints:
     * - OR must appear after the partial AND's span
     * - OR must be within max_distance if specified
     *
     * @param  Column  $partial  Partial AND node
     * @param  Column  $orNode  Candidate OR node for completion
     * @return bool True if can complete
     */
    private function canComplete(Column $partial, Column $orNode): bool
    {
        // Get expected operand info
        $expected = $partial->rnt_expected_right ?? $partial->rnt_expected_left;
        if (! $expected) {
            return false;
        }

        // Position constraints
        $positionAfter = $expected['position_after'] ?? $partial->span[1];
        $maxDistance = $expected['max_distance'] ?? 999;

        // OR must appear after the partial AND's span
        if ($orNode->span[0] <= $positionAfter) {
            return false; // OR is not after partial's span
        }

        // OR must be within max distance
        $distance = $orNode->span[0] - $positionAfter;
        if ($distance > $maxDistance) {
            return false; // OR is too far away
        }

        return true;
    }

    /**
     * Complete a partial AND node with an OR node
     *
     * Creates a complete AND composition by combining the partial AND's left operand
     * with the OR node as right operand, then following the composition edge to
     * create the resulting OR construction.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  Column  $partial  Partial AND node
     * @param  Column  $orNode  OR node to complete with
     * @return Column|null Complete AND node or null if failed
     */
    private function completePartialAnd(
        RuntimeGraph $graph,
        Column $partial,
        Column $orNode
    ): ?Column {
        $andNodeId = $partial->rnt_and_node_id;
        $patternId = $partial->bindings['pattern_id'] ?? null;

        if ($andNodeId === null || $patternId === null) {
            return null;
        }

        // Get composition target (AND→OR)
        $compositionTarget = $this->rntQuerier->getCompositionTarget($andNodeId, $patternId);

        if ($compositionTarget === null) {
            return null;
        }

        // Check if complete AND node already exists
        $newSpan = [$partial->span[0], $orNode->span[1]];
        $l2Nodes = $graph->getNodesByLevel('L2');
        $existingComplete = null;

        foreach ($l2Nodes as $existing) {
            if ($existing->span === $newSpan &&
                $existing->rnt_or_node_id === $compositionTarget['or_node_id'] &&
                $existing->isCompleteAnd()) {
                $existingComplete = $existing;
                break;
            }
        }

        if ($existingComplete !== null) {
            return null; // Already completed
        }

        // Create complete AND composition
        $constructionName = $compositionTarget['construction_name'] ?? "or_node_{$compositionTarget['or_node_id']}";

        $completeNode = $graph->addL2Node(
            startPos: $newSpan[0],
            endPos: $newSpan[1],
            constructionType: $constructionName,
            bindings: [
                'left_operand' => $partial->bindings['left_operand'] ?? $partial->id,
                'right_operand' => $orNode->id,
                'pattern_id' => $patternId,
                'complete_and' => true,
                'completed_from_partial' => $partial->id, // Track which partial was completed
            ],
            features: [
                'pattern_id' => $patternId,
                'construction_name' => $constructionName,
                'and_node_id' => $andNodeId,
            ]
        );

        // Set RNT properties
        $completeNode->rnt_or_node_id = $compositionTarget['or_node_id'];
        $completeNode->rnt_and_node_id = $andNodeId;
        $completeNode->rnt_status = 'complete_and';

        // Higher activation for completed compositions
        $leftOperand = $graph->getNode($partial->bindings['left_operand'] ?? '');
        $rightActivation = $orNode->activation;
        $leftActivation = $leftOperand ? $leftOperand->activation : 0.5;

        $combinedActivation = min($leftActivation, $rightActivation) * 0.9;
        $completeNode->activation = max($combinedActivation, 0.7);
        $completeNode->L5->activation = $completeNode->activation;
        $completeNode->L23->activation = $completeNode->activation * 0.9;

        // Create feedforward edges from both operands
        if ($leftOperand) {
            $graph->addEdge(new ConnectionEdge(
                source: $leftOperand->id,
                target: $completeNode->id,
                type: 'feedforward',
                weight: 1.0
            ));
        }

        $graph->addEdge(new ConnectionEdge(
            source: $orNode->id,
            target: $completeNode->id,
            type: 'feedforward',
            weight: 1.0
        ));

        return $completeNode;
    }

    /**
     * Strategy 4: Create alternative OR representations (OR→OR edges)
     *
     * For each L2 OR node:
     *   1. Find alternative OR nodes reachable via OR→OR edges
     *   2. Create L2 node for each alternative OR construction
     *   3. These represent the same span but with different construction labels
     *   4. Examples: HEAD_MOD → HEAD, HEAD → ARG, MOD_HEAD → HEAD
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created alternative OR L2 nodes
     */
    private function createAlternativeOrNodes(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            // Only process OR nodes (single-element or complete AND compositions)
            if (! $l2Node->isRNTConstruction() || $l2Node->rnt_or_node_id === null) {
                continue;
            }

            // Skip partial AND and intermediate AND nodes
            if ($l2Node->isPartialAnd() || $l2Node->isIntermediateAnd()) {
                continue;
            }

            $orNodeId = $l2Node->rnt_or_node_id;

            // Find alternative OR nodes reachable from this OR node
            $alternativeOrNodes = $this->rntQuerier->getAlternativeOrNodes($orNodeId);

            foreach ($alternativeOrNodes as $altOrNode) {
                // Check if alternative OR node already exists at this span
                $existingAlt = null;
                foreach ($l2Nodes as $existing) {
                    if ($existing->span === $l2Node->span &&
                        $existing->rnt_or_node_id === $altOrNode['or_node_id']) {
                        $existingAlt = $existing;
                        break;
                    }
                }

                if ($existingAlt !== null) {
                    continue; // Alternative already exists
                }

                // Create alternative OR node
                $altConstructionName = $altOrNode['construction_name'] ?? "or_node_{$altOrNode['or_node_id']}";

                $altNode = $graph->addL2Node(
                    startPos: $l2Node->span[0],
                    endPos: $l2Node->span[1],
                    constructionType: $altConstructionName,
                    bindings: [
                        'source_or_node' => $l2Node->id,
                        'pattern_id' => $altOrNode['pattern_id'],
                        'alternative_of' => $orNodeId,
                    ],
                    features: [
                        'pattern_id' => $altOrNode['pattern_id'],
                        'construction_name' => $altConstructionName,
                    ]
                );

                // Set RNT properties
                $altNode->rnt_or_node_id = $altOrNode['or_node_id'];
                $altNode->rnt_status = 'single'; // Treat as single-element for the alternative construction

                // Inherit activation from source OR node (slightly reduced)
                $altNode->activation = $l2Node->activation * 0.95;
                $altNode->L5->activation = $altNode->activation;
                $altNode->L23->activation = $altNode->activation * 0.9;

                // Create feedforward edge from source OR node
                $graph->addEdge(new ConnectionEdge(
                    source: $l2Node->id,
                    target: $altNode->id,
                    type: 'feedforward',
                    weight: 0.95
                ));

                $createdNodes[] = $altNode;
            }
        }

        return $createdNodes;
    }

    /**
     * Create OR nodes from SEQUENCER sources
     *
     * SEQUENCER nodes can feed into OR nodes (e.g., ARG SEQUENCER → PRED_INTERMEDIATE_n2 OR)
     * This enables SEQUENCER outputs to participate in further compositions
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created OR nodes
     */
    private function createOrNodesFromSequencers(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        foreach ($l2Nodes as $l2Node) {
            // Only process SEQUENCER nodes that are READY to propagate
            // Partial SEQUENCERs should not feed into OR nodes yet
            if (! $l2Node->isSequencer() || ! $l2Node->isSequencerReady()) {
                continue;
            }

            $sequencerNodeId = $l2Node->rnt_sequencer_node_id;

            // Find OR nodes reachable from this SEQUENCER
            $orNodes = $this->rntQuerier->getOrNodesFromSource($sequencerNodeId, 'SEQUENCER');

            foreach ($orNodes as $orNode) {
                // Check if OR node already exists at this span
                $existingOr = null;
                foreach ($l2Nodes as $existing) {
                    if ($existing->span === $l2Node->span &&
                        $existing->rnt_or_node_id === $orNode['or_node_id']) {
                        $existingOr = $existing;
                        break;
                    }
                }

                if ($existingOr !== null) {
                    continue; // OR node already exists
                }

                // Create OR node from SEQUENCER source
                $constructionName = $orNode['construction_name'] ?? "or_node_{$orNode['or_node_id']}";

                $orL2Node = $graph->addL2Node(
                    startPos: $l2Node->span[0],
                    endPos: $l2Node->span[1],
                    constructionType: $constructionName,
                    bindings: [
                        'source_sequencer_node' => $l2Node->id,
                        'pattern_id' => $orNode['pattern_id'],
                    ],
                    features: [
                        'pattern_id' => $orNode['pattern_id'],
                        'construction_name' => $constructionName,
                    ]
                );

                // Set RNT properties
                $orL2Node->rnt_or_node_id = $orNode['or_node_id'];
                $orL2Node->rnt_status = 'single'; // Treat as single-element construction

                // Inherit activation from source SEQUENCER (slightly reduced)
                $orL2Node->activation = $l2Node->activation * 0.95;
                $orL2Node->L5->activation = $orL2Node->activation;
                $orL2Node->L23->activation = $orL2Node->activation * 0.9;

                // Create feedforward edge from SEQUENCER to OR
                $graph->addEdge(new ConnectionEdge(
                    source: $l2Node->id,
                    target: $orL2Node->id,
                    type: 'feedforward',
                    weight: 0.95
                ));

                $createdNodes[] = $orL2Node;
            }
        }

        return $createdNodes;
    }

    /**
     * Check if a SEQUENCER has a HEAD input
     *
     * @param  Column  $sequencerNode  The SEQUENCER node to check
     * @param  int  $sequencerNodeId  Database ID of the SEQUENCER pattern node
     * @param  int  $patternId  Pattern ID
     * @return bool True if SEQUENCER has a head input
     */
    private function sequencerHasHeadInput(Column $sequencerNode, int $sequencerNodeId, int $patternId): bool
    {
        // Get all inputs for this SEQUENCER
        $inputs = $this->rntQuerier->getSequencerInputs($sequencerNodeId, $patternId);
        $headInputs = array_filter($inputs['all'], fn ($input) => ($input['position'] ?? null) === 'head');

        if (empty($headInputs)) {
            return false; // No head inputs defined
        }

        // Check if any active input matches a head input source
        $activeInputs = $sequencerNode->bindings['active_inputs'] ?? [];
        foreach ($activeInputs as $active) {
            foreach ($headInputs as $head) {
                if ($active['source_node_id'] === $head['source_node_id']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the span of the HEAD input in a SEQUENCER
     *
     * @param  Column  $sequencerNode  The SEQUENCER node
     * @param  RuntimeGraph  $graph  Runtime graph to look up source nodes
     * @param  int  $sequencerNodeId  Database ID of the SEQUENCER pattern node
     * @param  int  $patternId  Pattern ID
     * @return array|null The [start, end] span of the head input, or null if no head
     */
    private function getSequencerHeadSpan(Column $sequencerNode, RuntimeGraph $graph, int $sequencerNodeId, int $patternId): ?array
    {
        // Get all inputs for this SEQUENCER
        $inputs = $this->rntQuerier->getSequencerInputs($sequencerNodeId, $patternId);
        $headInputs = array_filter($inputs['all'], fn ($input) => ($input['position'] ?? null) === 'head');

        if (empty($headInputs)) {
            return null; // No head inputs defined
        }

        // Find the active head input's span
        $activeInputs = $sequencerNode->bindings['active_inputs'] ?? [];
        foreach ($activeInputs as $active) {
            foreach ($headInputs as $head) {
                if ($active['source_node_id'] === $head['source_node_id']) {
                    // Get the source L2 node to find its span
                    $sourceL2 = $graph->getNode($active['source_id']);
                    if ($sourceL2) {
                        return $sourceL2->span;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Prune invalid SEQUENCER links after dynamics
     *
     * After dynamics complete, some SEQUENCER nodes may have accumulated inputs
     * that violate position rules. This happens because inputs can arrive before
     * the HEAD, making it impossible to validate position constraints at link time.
     *
     * This method removes invalid inputs and updates activations accordingly.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     */
    public function pruneSequencerLinks(RuntimeGraph $graph): void
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $pruneCount = 0;
        $sequencerCount = 0;

        foreach ($l2Nodes as $seqNode) {
            if (! $seqNode->isSequencer()) {
                continue;
            }

            $sequencerCount++;

            $sequencerNodeId = $seqNode->rnt_sequencer_node_id;
            $patternId = $seqNode->bindings['pattern_id'] ?? null;

            if (! $sequencerNodeId || ! $patternId) {
                continue;
            }

            // Check if this SEQUENCER has a HEAD input
            if (! $this->sequencerHasHeadInput($seqNode, $sequencerNodeId, $patternId)) {
                continue; // No head yet, can't validate position rules
            }

            // Get the HEAD's span
            $headSpan = $this->getSequencerHeadSpan($seqNode, $graph, $sequencerNodeId, $patternId);
            if (! $headSpan) {
                continue;
            }

            // Get all input definitions
            $inputs = $this->rntQuerier->getSequencerInputs($sequencerNodeId, $patternId);
            $inputsBySourceId = [];
            foreach ($inputs['all'] as $input) {
                $inputsBySourceId[$input['source_node_id']] = $input;
            }

            // Check each active input for position validity
            $validInputs = [];
            $invalidInputIds = [];

            foreach ($seqNode->bindings['active_inputs'] ?? [] as $active) {
                $inputDef = $inputsBySourceId[$active['source_node_id']] ?? null;
                if (! $inputDef) {
                    continue; // Skip if we can't find input definition
                }

                $position = $inputDef['position'] ?? null;
                if (! $position) {
                    $validInputs[] = $active; // No position constraint

                    continue;
                }

                // Get source node span
                $sourceL2 = $graph->getNode($active['source_id']);
                if (! $sourceL2) {
                    continue;
                }

                // Validate position based on HEAD span
                $isValid = match ($position) {
                    'head' => true, // HEAD is always valid
                    'left' => $sourceL2->span[1] < $headSpan[0], // LEFT must be before HEAD
                    'right' => $headSpan[1] < $sourceL2->span[0], // RIGHT must be after HEAD
                    default => true, // Unknown positions pass through
                };

                if ($isValid) {
                    $validInputs[] = $active;
                } else {
                    $invalidInputIds[] = $active['source_id'];
                }
            }

            // If we found invalid inputs, update the SEQUENCER
            if (! empty($invalidInputIds)) {
                $pruneCount += count($invalidInputIds);

                // Update active inputs list
                $seqNode->bindings['active_inputs'] = $validInputs;

                // Recalculate activation based on valid inputs only
                $newActivation = 0.0;
                foreach ($validInputs as $input) {
                    $newActivation += $input['activation'] * 0.5;
                }

                // Apply ready boost if applicable
                if ($seqNode->bindings['ready_to_propagate'] ?? false) {
                    $newActivation = min(1.0, $newActivation * 1.2);
                }

                $seqNode->activation = $newActivation;
                $seqNode->L5->activation = $newActivation;

                // Remove edges from invalid inputs
                foreach ($invalidInputIds as $invalidSourceId) {
                    $graph->removeEdge($invalidSourceId, $seqNode->id);
                }

                // Recalculate span based on valid inputs only
                if (! empty($validInputs)) {
                    $minStart = PHP_INT_MAX;
                    $maxEnd = PHP_INT_MIN;

                    foreach ($validInputs as $input) {
                        $sourceL2 = $graph->getNode($input['source_id']);
                        if ($sourceL2) {
                            $minStart = min($minStart, $sourceL2->span[0]);
                            $maxEnd = max($maxEnd, $sourceL2->span[1]);
                        }
                    }

                    $seqNode->span = [$minStart, $maxEnd];
                }
            }
        }

        if ($pruneCount > 0 && config('app.debug')) {
            logger()->debug("Pruned {$pruneCount} invalid SEQUENCER link(s)");
        }
    }

    /**
     * Check if a SEQUENCER position link is allowed based on positional rules
     *
     * Rules:
     * 1. HEAD links: Always allowed
     * 2. LEFT links: Allowed if (a) no head link yet OR (b) have head AND source.span[1] < sequencer.span[0]
     * 3. RIGHT links: Allowed only if sequencer.span[1] < source.span[0]
     *
     * @param  string  $position  Position of the link ('left', 'head', 'right')
     * @param  array  $sourceSpan  Span of the source node [start, end]
     * @param  array  $sequencerSpan  Span of the SEQUENCER node [start, end]
     * @param  bool  $hasHeadInput  Whether SEQUENCER already has a head input
     * @return bool True if link is allowed
     */
    private function canLinkToSequencerPosition(
        string $position,
        array $sourceSpan,
        array $sequencerSpan,
        bool $hasHeadInput
    ): bool {
        return match ($position) {
            'head' => true, // HEAD links always allowed
            'left' => ! $hasHeadInput || $sourceSpan[1] < $sequencerSpan[0], // LEFT: no head yet OR comes before sequencer
            'right' => $sequencerSpan[1] < $sourceSpan[0], // RIGHT: must come after sequencer
            default => false, // Unknown positions not allowed
        };
    }

    /**
     * Create SEQUENCER nodes from OR/SEQUENCER inputs
     *
     * SEQUENCER nodes:
     * - Accumulate activation from input OR/SEQUENCER nodes
     * - Only propagate when ALL mandatory (non-optional) inputs are activated
     * - Do NOT make predictions (no prediction mechanism)
     * - Cannot be reused after activation - must create new instance
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of created SEQUENCER L2 nodes
     */
    private function createSequencerNodes(RuntimeGraph $graph): array
    {
        $createdNodes = [];
        $l2Nodes = $graph->getNodesByLevel('L2');

        // Track SEQUENCER instances that have already been used
        if (! isset($graph->metadata['used_sequencer_instances'])) {
            $graph->metadata['used_sequencer_instances'] = [];
        }

        foreach ($l2Nodes as $l2Node) {
            // Only process OR and SEQUENCER nodes
            if (! $l2Node->isRNTConstruction()) {
                continue;
            }

            // Get source node ID (could be OR or SEQUENCER)
            $sourceNodeId = $l2Node->rnt_or_node_id ?? $l2Node->rnt_sequencer_node_id ?? null;
            $sourceType = $l2Node->rnt_sequencer_node_id !== null ? 'SEQUENCER' : 'OR';

            if ($sourceNodeId === null) {
                continue;
            }

            // Find SEQUENCER nodes reachable from this source
            $sequencerTargets = $this->rntQuerier->getSequencerNodesFromSource($sourceNodeId, $sourceType);

            foreach ($sequencerTargets as $seqTarget) {
                $sequencerNodeId = $seqTarget['sequencer_node_id'];
                $patternId = $seqTarget['pattern_id'];
                $constructionName = $seqTarget['construction_name'] ?? "seq_node_{$sequencerNodeId}";

                // Get input requirements for this SEQUENCER
                $inputs = $this->rntQuerier->getSequencerInputs($sequencerNodeId, $patternId);

                // Check if we already have a SEQUENCER instance for this pattern that can accept this input
                // SEQUENCER spans can grow, so we look for any instance with the same pattern
                // that overlaps with or is adjacent to the current input
                // Search both existing nodes AND newly created nodes in this pass
                $existingSeq = null;
                $allNodes = array_merge($l2Nodes, $createdNodes);
                foreach ($allNodes as $existing) {
                    if ($existing->rnt_sequencer_node_id === $sequencerNodeId &&
                        ($existing->bindings['pattern_id'] ?? null) === $patternId) {
                        // Check if spans overlap or are adjacent (allowing sequential accumulation)
                        $spansOverlap = ! ($l2Node->span[1] < $existing->span[0] || $l2Node->span[0] > $existing->span[1]);
                        $spansAdjacent = ($l2Node->span[0] === $existing->span[1] + 1) || ($existing->span[0] === $l2Node->span[1] + 1);

                        if ($spansOverlap || $spansAdjacent) {
                            $existingSeq = $existing;
                            break;
                        }
                    }
                }

                if ($existingSeq !== null) {
                    // SEQUENCER instance exists - check if we can add this input based on position rules
                    $position = $seqTarget['position'] ?? null;
                    $hasHeadInput = $this->sequencerHasHeadInput($existingSeq, $sequencerNodeId, $patternId);

                    // Validate position-based linking rules
                    if ($position && ! $this->canLinkToSequencerPosition($position, $l2Node->span, $existingSeq->span, $hasHeadInput)) {
                        continue; // Skip this link - not allowed by position rules
                    }

                    // Check if this input hasn't already been added
                    $inputAlreadyAdded = false;
                    foreach ($existingSeq->bindings['active_inputs'] ?? [] as $activeInput) {
                        if ($activeInput['source_id'] === $l2Node->id) {
                            $inputAlreadyAdded = true;
                            break;
                        }
                    }

                    if (! $inputAlreadyAdded) {
                        // Add this input to the SEQUENCER's active inputs
                        if (! isset($existingSeq->bindings['active_inputs'])) {
                            $existingSeq->bindings['active_inputs'] = [];
                        }

                        $existingSeq->bindings['active_inputs'][] = [
                            'source_id' => $l2Node->id,
                            'source_node_id' => $sourceNodeId,
                            'source_type' => $sourceType,
                            'optional' => $seqTarget['optional'],
                            'activation' => $l2Node->activation,
                        ];

                        // Extend span to cover new input (SEQUENCER spans grow with inputs)
                        if ($l2Node->span[1] > $existingSeq->span[1]) {
                            $existingSeq->span[1] = $l2Node->span[1];
                        }
                        if ($l2Node->span[0] < $existingSeq->span[0]) {
                            $existingSeq->span[0] = $l2Node->span[0];
                        }

                        // Accumulate activation
                        $existingSeq->activation += $l2Node->activation * 0.5;
                        $existingSeq->L5->activation = $existingSeq->activation;

                        // Create feedforward edge from new input to SEQUENCER
                        $graph->addEdge(new ConnectionEdge(
                            source: $l2Node->id,
                            target: $existingSeq->id,
                            type: 'feedforward',
                            weight: 0.5
                        ));

                        // Check if all mandatory inputs are now active
                        $mandatoryCount = count($inputs['mandatory']);
                        $mandatoryActiveCount = 0;

                        foreach ($existingSeq->bindings['active_inputs'] as $activeInput) {
                            if (! $activeInput['optional']) {
                                $mandatoryActiveCount++;
                            }
                        }

                        // If all mandatory inputs are active and not already propagated, mark for propagation
                        if ($mandatoryActiveCount >= $mandatoryCount && ! ($existingSeq->bindings['propagated'] ?? false)) {
                            $existingSeq->bindings['ready_to_propagate'] = true;
                            $existingSeq->rnt_status = 'sequencer_ready'; // Update status to ready
                            $existingSeq->activation = min(1.0, $existingSeq->activation * 1.2); // Boost activation when ready
                            $existingSeq->L5->activation = $existingSeq->activation;

                            // Mark instance as used so it can't be reused (use stored instance key)
                            $instanceKey = $existingSeq->bindings['instance_key'] ?? "{$sequencerNodeId}_{$patternId}_{$existingSeq->span[0]}";
                            $graph->metadata['used_sequencer_instances'][$instanceKey] = true;
                            $existingSeq->bindings['propagated'] = true;
                        }
                    }

                    continue; // Don't create new instance
                }

                // Check if this SEQUENCER instance was already used (prevent reuse)
                $instanceKey = "{$sequencerNodeId}_{$patternId}_{$l2Node->span[0]}";
                if (isset($graph->metadata['used_sequencer_instances'][$instanceKey])) {
                    continue; // This instance was already used and propagated
                }

                // Validate position-based linking rules for new SEQUENCER creation
                // For initial creation: hasHeadInput=false, sequencerSpan=sourceSpan
                $position = $seqTarget['position'] ?? null;
                if ($position && ! $this->canLinkToSequencerPosition($position, $l2Node->span, $l2Node->span, false)) {
                    continue; // Skip - can't create SEQUENCER with this position as first input
                }

                // Create new SEQUENCER instance
                $instanceKey = "{$sequencerNodeId}_{$patternId}_{$l2Node->span[0]}";

                $seqNode = $graph->addL2Node(
                    startPos: $l2Node->span[0],
                    endPos: $l2Node->span[1],
                    constructionType: $constructionName,
                    bindings: [
                        'source' => $l2Node->id,
                        'pattern_id' => $patternId,
                        'sequencer_type' => true,
                        'instance_key' => $instanceKey,
                        'active_inputs' => [
                            [
                                'source_id' => $l2Node->id,
                                'source_node_id' => $sourceNodeId,
                                'source_type' => $sourceType,
                                'optional' => $seqTarget['optional'],
                                'activation' => $l2Node->activation,
                            ],
                        ],
                        'mandatory_input_count' => count($inputs['mandatory']),
                        'ready_to_propagate' => false,
                        'propagated' => false,
                    ],
                    features: [
                        'pattern_id' => $patternId,
                        'construction_name' => $constructionName,
                        'sequencer_node_id' => $sequencerNodeId,
                    ]
                );

                // Set RNT properties
                $seqNode->rnt_sequencer_node_id = $sequencerNodeId;
                $seqNode->rnt_status = 'sequencer_partial';

                // Initialize activation from first input
                $seqNode->activation = $l2Node->activation * 0.5;
                $seqNode->L5->activation = $seqNode->activation;
                $seqNode->L23->activation = 0.0; // SEQUENCER doesn't predict

                // Check if already ready (first input is mandatory and it's the only mandatory input)
                $mandatoryCount = count($inputs['mandatory']);
                if ($mandatoryCount === 1 && ! $seqTarget['optional']) {
                    $seqNode->bindings['ready_to_propagate'] = true;
                    $seqNode->rnt_status = 'sequencer_ready';
                    $seqNode->activation = min(1.0, $seqNode->activation * 1.2);
                    $seqNode->L5->activation = $seqNode->activation;

                    // Mark instance as used (using stored instance key)
                    $graph->metadata['used_sequencer_instances'][$instanceKey] = true;
                    $seqNode->bindings['propagated'] = true;
                }

                // Create feedforward edge
                $graph->addEdge(new ConnectionEdge(
                    source: $l2Node->id,
                    target: $seqNode->id,
                    type: 'feedforward',
                    weight: 0.5
                ));

                $createdNodes[] = $seqNode;
            }
        }

        return $createdNodes;
    }
}

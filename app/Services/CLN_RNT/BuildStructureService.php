<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\ConnectionEdge;
use App\Models\CLN_RNT\Node;
use App\Services\CLN\RuntimeGraph;
use Illuminate\Support\Facades\DB;

/**
 * Build structure for Parsing (RNT Pattern Graph)
 *
 * Implements simple forward-pass activation propagation through the RNT pattern graph:
 * - Word/POS nodes activate and propagate forward
 * - OR nodes: Activate just once when any input arrives
 * - AND nodes: Only propagate when BOTH left and right operands are active
 * - SEQUENCER nodes: Only propagate when the head component is activated
 *   - Once head-activated, a SEQUENCER can't receive new head activations (create new instance)
 *   - But can still receive activation from right link
 *
 * Uses lazy database queries with caching for pattern graph access.
 */
class BuildStructureService
{
    /**
     * Activation threshold for considering a node "active"
     */
    private const ACTIVATION_THRESHOLD = 0.5;

    /**
     * Initial activation level for input word/POS nodes
     */
    private const INITIAL_ACTIVATION = 0.95;

    /**
     * Activation propagation decay factor
     */
    private const PROPAGATION_FACTOR = 0.9;

    /**
     * Maximum number of propagation iterations
     */
    private const MAX_ITERATIONS = 10;

    /**
     * Track which OR nodes have been activated (activate once rule)
     *
     * @var array<int, bool> or_node_id => true
     */
    private array $activatedOrNodes = [];

    /**
     * Track which SEQUENCER instances (L2 nodes) have been head-activated
     * Key: L2 node ID in runtime graph
     * Value: true
     *
     * @var array<string, bool>
     */
    private array $headActivatedSequencers = [];

    private int $sequencerCounter = 0;

    public function __construct()
    {
    }


    /**
     * Find DATA nodes in pattern graph that match an L1 node's features
     *
     * @param \App\Models\CLN_RNT\Node $l1Node L1 runtime node
     * @return array Array of matching DATA node IDs
     */
    private function findMatchingDataNodes(Node $l1Node): array
    {
        $matches = [];
        $word = $l1Node->features['value'] ?? null;
        $pos = $l1Node->features['pos'] ?? null;

        // Match LITERAL nodes by word
        if ($word) {
            $literalMatches = $this->findDataNodesByLiteral($word);
            $matches = array_merge($matches, $literalMatches);
        }

        // Match SLOT nodes by POS
        if ($pos) {
            $slotMatches = $this->findDataNodesByPos($pos);
            $matches = array_merge($matches, $slotMatches);
        }

        return array_unique($matches);
    }


    /**
     * Find DATA nodes matching a POS tag
     *
     * @param string $pos POS tag to match
     * @return array Array of DATA node IDs
     */
    private function findDataNodesByPos(string $pos): array
    {
        $cacheKey = "slot:{$pos}";

        if (isset($this->dataNodeCache[$cacheKey])) {
            return $this->dataNodeCache[$cacheKey];
        }

        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->where('pos', $pos)
            ->whereRaw("JSON_EXTRACT(specification, '$.dataType') = 'slot'")
            ->pluck('id')
            ->toArray();

        $this->dataNodeCache[$cacheKey] = $nodes;

        return $nodes;
    }






    /**
     * Process bigram pattern (3 consecutive SEQUENCERs)
     *
     * @return array [SeqColumn|null, previous L2 sequencer, previous L2 column]
     */
    private function processBigramPattern(
        Node $previous,
        Node $current,
    ): array
    {
        $idPrevious = $previous->id;
        $idCurrent = $current->id;
        $idBigram = "{$idPrevious}_{$idCurrent}";
        $sequencerName = "S{$this->sequencerCounter}";
        $this->sequencerCounter++;
        $higherColumn = new Column(
            cortical_level: 'L2',
            construction_type: 'sequencer',
            span: [$previous->span[0], $current->span[1]],
            id: $idBigram,
            name: $sequencerName,
            features: [
                'trigram' => $idBigram,
                'type' => 'higher_level_sequencer',
                'level' => 2,
            ]
        );
        $sequencers = $this->connectBigramToColumn($higherColumn, $previous, $current);
        return $sequencers;
    }

    /**
     * Connect bigram SEQUENCERs to higher-level column
     */
    private function connectBigramToColumn(
        Column $column,
        Node   $left,
        Node   $right
    ): array
    {
        $sequencers = [];

        $idPatternLeft = $left->getIdPatternNode();
        $edgesLeft = $this->getOutgoingEdges($idPatternLeft);

        foreach ($edgesLeft as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            $features['idPatternNode'] = $targetNodeId;
            //
            $leftOrNode = $this->graph->addL2L23Node(
                name: 'l',
                column: $column,
                startPos: $left->span[0],
                endPos: $right->span[1],
                features: $features
            );

            $edgesForLeftOR = $this->getOutgoingEdges($targetNodeId);
            foreach ($edgesForLeftOR as $edgeForLeftOR) {
                $sequencerNodeId = $edge->to_node_id;
                $sequencerNode = $this->getPatternNode($sequencerNodeId);

                if (!$sequencerNode || $sequencerNode['type'] !== 'SEQUENCER') {
                    continue;
                }

                $features['idPatternNode'] = $sequencerNodeId;
                if (!isset($sequencers[$sequencerNode['construction_name']])) {
                    $sequencer = $this->graph->addL2L5Node(
                        name: $sequencerNode['construction_name'],
                        column: $column,
                        startPos: $left->span[0],
                        endPos: $right->span[1],
                        features: $features
                    );

                    $sequencers[] = $sequencer;
                } else {
                    $sequencer = $sequencers[$sequencerNode['construction_name']];
                }

                $edgeLeft = new ConnectionEdge(
                    source: $leftOrNode->id,
                    target: $sequencer->id,
                    type: 'feedforward',
                    weight: 1.0
                );
                $this->graph->addEdge($edgeLeft);

            }


        }


//        // Connect SeqColumn_A:left → left node
//        $leftNodes_a = $seq_a->l_nodes;
//        if (!empty($leftNodes_a)) {
//            foreach ($leftNodes_a as $leftNode_a) {
//                $leftNode = $higherSeqColumn->getOrCreateLeftNode($seq_a->id);
//                $edgeLeft = new ConnectionEdge(
//                    source: $leftNode_a->id,
//                    target: $leftNode->id,
//                    type: 'feedforward',
//                    weight: 1.0
//                );
//                $graph->addEdge($edgeLeft);
//            }
//        }
//
//        if (!is_null($seq_c)) {
//            $leftNodes_c = $seq_c->l_nodes;
//            if (!empty($leftNodes_c)) {
//                foreach ($leftNodes_c as $leftNode_c) {
//                    $edgeRight = new ConnectionEdge(
//                        source: $higherSeqColumn->s_node->id,
//                        target: $leftNode_c->id,
//                        type: 'feedforward',
//                        weight: 1.0
//                    );
//                    $graph->addEdge($edgeRight);
//                }
//            }
//        }
//
//        // Connect higherSeqColumn → next left nodes
//        //        $leftNodes_a = $seq_a['column']->l_nodes;
//        //        if (!empty($leftNodes_a)) {
//        //            foreach ($leftNodes_a as $leftNode_a) {
//        //                $leftNode = $higherSeqColumn->getOrCreateLeftNode($seq_a['sequencer']->id);
//        //                $edgeLeft = new ConnectionEdge(
//        //                    source: $leftNode_a->id,
//        //                    target: $leftNode->id,
//        //                    type: 'feedforward',
//        //                    weight: 1.0
//        //                );
//        //                $graph->addEdge($edgeLeft);
//        //            }
//        //        }
//
//        // Connect SEQUENCER_B → head (only for new columns)
//        if ($connectHead && $seq_b) {
//
//            // Connect SEQUENCER_A → head
//            $edgeA = new ConnectionEdge(
//                source: $seq_a->s_node->id,
//                target: $higherSeqColumn->h_node->id,
//                type: 'feedforward',
//                weight: 1.0
//            );
//            $graph->addEdge($edgeA);
//
//            $edgeB = new ConnectionEdge(
//                source: $seq_b->s_node->id,
//                target: $higherSeqColumn->h_node->id,
//                type: 'feedforward',
//                weight: 1.0
//            );
//            $graph->addEdge($edgeB);
//        }
        return $sequencers;
    }


    /**
     * Connect from a DATA node
     *
     * @param int $dataNodeId Pattern graph DATA node ID
     * @param RuntimeGraph $graph Runtime graph
     * @param array $stats Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function connectFromDataNode(int $dataNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;
        $edges = $this->getOutgoingEdges($dataNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            //
//            $l2OrNode = $graph->addL1Node(
//                position: $data['position'],
//                constructionType: 'literal',
//                features: $data['features']
//            );


//            // OR nodes activate just once
//            if (isset($this->activatedOrNodes[$targetNodeId])) {
//                continue;
//            }
//
//            // Activate this OR node
//            $this->activatedOrNodes[$targetNodeId] = true;

            $stats['or_nodes_activated']++;
            $stats['nodes_activated']++;
            $changed = true;
        }

        return $changed;
    }

    /**
     * Propagate activation from an OR node to connected nodes (AND, other OR, SEQUENCER)
     *
     * @param int $orNodeId Pattern graph OR node ID
     * @param RuntimeGraph $graph Runtime graph
     * @param array $stats Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function connectFromOrNode(int $orNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;
        $edges = $this->getOutgoingEdges($orNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode) {
                continue;
            }

            $targetType = $targetNode['type'];

            if ($targetType === 'OR') {
                // OR → OR propagation (for construction references)
                if (!isset($this->activatedOrNodes[$targetNodeId])) {
                    $this->activatedOrNodes[$targetNodeId] = true;
                    $stats['or_nodes_activated']++;
                    $stats['nodes_activated']++;
                    $changed = true;
                }
            } elseif ($targetType === 'AND') {
                // OR → AND: Mark as changed to trigger AND node check
                $changed = true;
            } elseif ($targetType === 'SEQUENCER') {
                // OR → SEQUENCER: Check if this is from the head OR
                $properties = json_decode($edge->properties, true);
                $position = $properties['position'] ?? null;

                // Only propagate if this edge is from the head position
                if ($position === 'head') {
                    $constructionName = $targetNode['construction_name'] ?? null;
                    if ($constructionName) {
                        // Always create a NEW L2 node for each head activation
                        // (SEQUENCER can't be reused after head activation)
                        $l2Node = $this->createNewL2NodeForConstruction($graph, $constructionName, $targetNodeId);
                        $l2Node->activation = self::INITIAL_ACTIVATION * self::PROPAGATION_FACTOR;

                        // Mark this L2 node as head-activated
                        $this->headActivatedSequencers[$l2Node->id] = true;

                        $stats['sequencer_nodes_activated']++;
                        $stats['nodes_activated']++;
                        $changed = true;
                    }
                }
            }
        }

        return $changed;
    }

    /**
     * Propagate activation from an AND node
     *
     * AND nodes only propagate when BOTH left and right operands are active.
     *
     * @param int $andNodeId Pattern graph AND node ID
     * @param RuntimeGraph $graph Runtime graph
     * @param array $stats Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function connectFromAndNode(int $andNodeId, RuntimeGraph $graph, array &$stats): bool
    {
        // Check if both operands are active
        $leftActive = false;
        $rightActive = false;

        $incomingEdges = $this->getIncomingEdges($andNodeId);

        foreach ($incomingEdges as $edge) {
            $sourceId = $edge->from_node_id;
            $properties = json_decode($edge->properties, true);
            $label = $properties['label'] ?? null;

            $sourceNode = $this->getPatternNode($sourceId);
            if (!$sourceNode) {
                continue;
            }

            // Check if source is active
            $sourceActive = false;
            if ($sourceNode['type'] === 'OR' && isset($this->activatedOrNodes[$sourceId])) {
                $sourceActive = true;
            } elseif ($sourceNode['type'] === 'SEQUENCER') {
                // Check runtime graph for SEQUENCER activation
                $constructionName = $sourceNode['construction_name'] ?? null;
                if ($constructionName) {
                    $l2Nodes = $this->findL2NodesForConstruction($graph, $constructionName);
                    foreach ($l2Nodes as $l2Node) {
                        if ($l2Node->activation >= self::ACTIVATION_THRESHOLD) {
                            $sourceActive = true;
                            break;
                        }
                    }
                }
            }

            if ($label === 'left' && $sourceActive) {
                $leftActive = true;
            } elseif ($label === 'right' && $sourceActive) {
                $rightActive = true;
            }
        }

        // Only propagate if both operands are active
        if (!$leftActive || !$rightActive) {
            return false;
        }

        // Both operands active - propagate to target nodes
        $changed = false;
        $edges = $this->getOutgoingEdges($andNodeId);

        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode) {
                continue;
            }

            if ($targetNode['type'] === 'OR') {
                // AND → OR propagation
                if (!isset($this->activatedOrNodes[$targetNodeId])) {
                    $this->activatedOrNodes[$targetNodeId] = true;
                    $stats['or_nodes_activated']++;
                    $stats['nodes_activated']++;
                    $stats['and_nodes_activated']++;
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    /**
     * Propagate activation from a SEQUENCER node
     *
     * SEQUENCER nodes propagate to left and right OR nodes after being activated by head.
     * Note: Once head-activated, a SEQUENCER can still receive right link activations.
     *
     * @param int $sequencerNodeId Pattern graph SEQUENCER node ID
     * @param string $l2NodeId Runtime graph L2 node ID for this SEQUENCER instance
     * @param RuntimeGraph $graph Runtime graph
     * @param array $stats Statistics array (updated by reference)
     * @return bool True if any activation changed
     */
    private function connectFromSequencerNode(int $sequencerNodeId, string $l2NodeId, RuntimeGraph $graph, array &$stats): bool
    {
        $changed = false;

        // Only propagate if this SEQUENCER has been head-activated
        if (!isset($this->headActivatedSequencers[$l2NodeId])) {
            return false;
        }

        // Find outgoing edges from SEQUENCER
        $outgoingEdges = $this->getOutgoingEdges($sequencerNodeId);

        foreach ($outgoingEdges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            // OR nodes activate just once
            if (isset($this->activatedOrNodes[$targetNodeId])) {
                continue;
            }

            // Activate this OR node
            $this->activatedOrNodes[$targetNodeId] = true;
            $stats['or_nodes_activated']++;
            $stats['nodes_activated']++;
            $changed = true;

//            $sourceId = $edge->from_node_id;
//            $properties = json_decode($edge->properties, true);
//            $position = $properties['position'] ?? null;
//
//            // Activate the source OR nodes for left/right positions
//            // (head is already active since it gated the SEQUENCER)
//            if (in_array($position, ['left', 'right'])) {
//                $sourceNode = $this->getPatternNode($sourceId);
//                if ($sourceNode && $sourceNode['type'] === 'OR') {
//                    if (! isset($this->activatedOrNodes[$sourceId])) {
//                        $this->activatedOrNodes[$sourceId] = true;
//                        $stats['or_nodes_activated']++;
//                        $stats['nodes_activated']++;
//                        $changed = true;
//                    }
//                }
//            }
        }

        return $changed;
    }

    /**
     * Get all AND node IDs from pattern graph
     *
     * @return array Array of AND node IDs
     */
    private function getAllAndNodeIds(): array
    {
        // Query for AND nodes (not cached as it's a small set)
        return DB::table('parser_pattern_node')
            ->where('type', 'AND')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get active SEQUENCER nodes from runtime graph
     *
     * Returns mapping of L2 node ID => SEQUENCER pattern node ID
     *
     * @param RuntimeGraph $graph Runtime graph
     * @return array Array mapping L2 node ID => SEQUENCER pattern node ID
     */
    private function getActiveSequencerNodes(RuntimeGraph $graph): array
    {
        $activeSequencers = [];

        $l2Nodes = $graph->getNodesByLevel('L2');
        foreach ($l2Nodes as $l2Node) {
            if ($l2Node->activation < self::ACTIVATION_THRESHOLD) {
                continue;
            }

            // Get SEQUENCER pattern node ID from bindings
            $patternNodeId = $l2Node->bindings['pattern_node_id'] ?? null;
            if (!$patternNodeId) {
                continue;
            }

            // Verify it's a SEQUENCER node
            $patternNode = $this->getPatternNode($patternNodeId);
            if ($patternNode && $patternNode['type'] === 'SEQUENCER') {
                $activeSequencers[$l2Node->id] = $patternNodeId;
            }
        }

        return $activeSequencers;
    }

    /**
     * Create new L2 node in runtime graph for a construction
     *
     * Always creates a new node (never reuses existing ones).
     *
     * @param RuntimeGraph $graph Runtime graph
     * @param string $constructionName Construction name
     * @param int $patternNodeId Pattern graph node ID
     * @return \App\Models\CLN_RNT\Column L2 node
     */
    private function createNewL2NodeForConstruction(RuntimeGraph $graph, string $constructionName, int $patternNodeId): \App\Models\CLN_RNT\Column
    {
        // Create new L2 node
        // For now, use placeholder span - this should be determined by composition
        $l2Node = $graph->addL2Node(
            startPos: 0,
            endPos: 0,
            constructionType: $constructionName,
            bindings: ['pattern_node_id' => $patternNodeId],
            features: []
        );

        return $l2Node;
    }

    /**
     * Find all L2 nodes in runtime graph for a construction
     *
     * @param RuntimeGraph $graph Runtime graph
     * @param string $constructionName Construction name
     * @return array Array of L2 nodes
     */
    private function findL2NodesForConstruction(RuntimeGraph $graph, string $constructionName): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $matches = [];

        foreach ($l2Nodes as $node) {
            if ($node->construction_type === $constructionName) {
                $matches[] = $node;
            }
        }

        return $matches;
    }
}

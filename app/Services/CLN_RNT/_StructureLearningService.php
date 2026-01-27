<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use App\Models\CLN_RNT\ConnectionEdge;
use App\Models\CLN_RNT\RuntimeGraph;
use App\Models\CLN_RNT\SeqColumn;

/**
 * StructureLearningService
 *
 * Stage 1 of learning: Build graph structure from training sentences.
 * Creates/reuses nodes, edges, and SeqColumns to form the pattern graph.
 */
class StructureLearningService
{
    /**
     * Counter for SEQUENCER node naming
     */
    private int $sequencerCounter = 1;

    /**
     * Process training sentences to build the graph structure
     *
     * @param  array  $sentences  Array of parsed word data for each sentence
     * @param  RuntimeGraph  $graph  The runtime graph to build into
     * @return array Result containing graph, SeqColumns, and statistics
     */
    public function buildGraphStructure(array $sentences, RuntimeGraph $graph): array
    {
        $processedCount = 0;
        $skippedCount = 0;
        $globalPosition = 0;
        $posColumnsMap = [];
        $levelSequencers = [];
        $sequencerMap = [];
        $columnHistory = [];

        // Reset SEQUENCER counter for this build
        $this->sequencerCounter = 1;

        foreach ($sentences as $sentenceData) {
            if (empty($sentenceData)) {
                $skippedCount++;

                continue;
            }

            $previousSequencer = null;
            $previousColumn = null;
            // $previousL2Sequencer = null;
            // $previousL2Column = null;

            $columnHistory[1] = [];

            foreach ($sentenceData as $data) {
                if ($data['pos'] == 'PUNCT') {
                    continue;
                }
                // 1. Add L1 node for this word
                $l1Node = $graph->addL1Node(
                    position: $globalPosition,
                    constructionType: 'literal',
                    features: $data['features']
                );
                $l1Node->activation = 0.95;

                // 2. Create or reuse POS node
                $posTag = $data['pos'];
                $posNode = $this->findOrCreatePosNode($graph, $posTag);

                if ($posNode->activation < 0.95) {
                    $posNode->activation = 0.95;
                } else {
                    $posNode->activation += 0.05;
                }

                // 3. Add edge connecting word to POS
                $edge = new ConnectionEdge(
                    source: $l1Node->id,
                    target: $posNode->id,
                    type: 'category',
                    weight: 1.0
                );
                $graph->addEdge($edge);

                // 4. Create SeqColumn for POS node (one per POS tag)
                if (! isset($posColumnsMap[$posTag])) {
                    $seqColumn = $this->createSeqColumnForPos($posTag);
                    $posColumnsMap[$posTag] = $seqColumn;

                    // Connect POS node to head node of SeqColumn
                    $posToHeadEdge = new ConnectionEdge(
                        source: $posNode->id,
                        target: $seqColumn->h_node->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $graph->addEdge($posToHeadEdge);
                }

                // Get current column for this POS
                $currentColumn = $posColumnsMap[$posTag];
                $currentSequencer = $currentColumn->s_node;

                // 5. Create sequential connections between columns
                if ($previousSequencer !== null && $previousColumn !== null) {
                    $this->createSequentialConnections(
                        $graph,
                        $previousSequencer,
                        $previousColumn,
                        $currentSequencer,
                        $currentColumn
                    );
                }

                // Update previous references
                $previousSequencer = $currentSequencer;
                $previousColumn = $currentColumn;

                // 6. Track SEQUENCER for higher-level pattern detection
                //                $sentenceSequencerHistory[] = [
                //                    'sequencer' => $currentSequencer,
                //                    'column' => $currentColumn,
                //                    'pos_tag' => $posTag,
                //                ];
                $columnHistory[1][] = $currentColumn;

                //                if (count($sequencerHistory) > 2) {
                //                    array_shift($sequencerHistory);
                //                }

                //                if (count($sequencerHistory) > 3) {
                //                    array_shift($sequencerHistory);
                //                }

                // When we have 3 consecutive SEQUENCERs, create or reuse higher-level SEQUENCER
                //                if (count($sequencerHistory) === 3) {
                //                    [$higherSeqColumn, $previousL2Sequencer, $previousL2Column] = $this->processTrigramPattern(
                //                        $graph,
                //                        $sequencerHistory,
                //                        $sequencerToL2Map,
                //                        $higherLevelSequencers,
                //                        $previousL2Sequencer,
                //                        $previousL2Column
                //                    );
                //
                //                    if ($higherSeqColumn) {
                //                        $previousL2Sequencer = $higherSeqColumn->s_node;
                //                        $previousL2Column = $higherSeqColumn;
                //                    }
                //                }

                $globalPosition++;
            }

            $level = 2;

            $columnHistory[1][] = '';

            $L2SequencerHistory = [];
            if (count($L1SequencerHistory) > 1) {
                for ($i = 1; $i < count($L1SequencerHistory); $i++) {
                    [$higherSeqColumn, $previousL2Sequencer, $previousL2Column] = $this->processBigramPattern(
                        $graph,
                        $L1SequencerHistory[$i - 1],
                        $L1SequencerHistory[$i],
                        $L1SequencerHistory[$i + 1] ?? null,
                        $sequencerToL2Map,
                        $higherLevelSequencers,
                        $previousL2Sequencer,
                        $previousL2Column
                    );
                    $L2SequencerHistory[] = [
                        $higherSeqColumn,
                        $L1SequencerHistory[$i + 1] ?? null,
                        $L1SequencerHistory[$i + 2] ?? null,
                    ];

                    //                        if ($higherSeqColumn) {
                    //                            $previousL2Sequencer = $higherSeqColumn->s_node;
                    //                            $previousL2Column = $higherSeqColumn;
                    //                        }
                }
            }

            $L3Sequencers = [];
            if (count($L2SequencerHistory) > 1) {
                for ($i = 0; $i < count($L2SequencerHistory); $i++) {
                    $seq_a = $L2SequencerHistory[$i][0];
                    $seq_b = $L2SequencerHistory[$i][1];
                    $seq_c = $L2SequencerHistory[$i][2];
                    $this->processBigramPattern(
                        $graph,
                        $seq_a,
                        $seq_b,
                        $seq_c ?? null,
                        $sequencerToL2Map,
                        $L3Sequencers,
                        $previousL2Sequencer,
                        $previousL2Column
                    );
                    //                    $L2SequencerHistory[] = [
                    //                        $higherSeqColumn,
                    //                        $L1SequencerHistory[$i+1] ?? null,
                    //                        $L1SequencerHistory[$i+2] ?? null
                    //                    ];
                }
            }

            $processedCount++;
        }

        // Create PV inhibitory links for WTA dynamics
        //        $pvLinksL1 = $this->createPVInhibitoryLinks($graph, $posColumnsMap);
        //        $pvLinksL2 = $this->createPVInhibitoryLinks($graph, $higherLevelSequencers);

        return [
            'graph' => $graph,
            'seq_columns_l1' => $posColumnsMap,
            'seq_columns_l2' => $higherLevelSequencers,
            'seq_columns_l3' => $L3Sequencers,
            'statistics' => [
                'sentences_processed' => $processedCount,
                'sentences_skipped' => $skippedCount,
                'total_nodes' => $globalPosition,
                'seq_columns_l1' => count($posColumnsMap),
                'seq_columns_l2' => count($higherLevelSequencers),
                //                'pv_links_l1' => $pvLinksL1,
                //                'pv_links_l2' => $pvLinksL2,
            ],
        ];
    }

    /**
     * Find or create a POS node in the graph
     */
    private function findOrCreatePosNode(RuntimeGraph $graph, string $posTag): Column
    {
        $allNodes = $graph->getAllNodes();
        foreach ($allNodes as $node) {
            if ($node->cortical_level === 'L1'
                && $node->construction_type === 'pos'
                && isset($node->features['value'])
                && $node->features['value'] === $posTag) {
                return $node;
            }
        }

        $posNode = $graph->addL1Node(
            position: -1,
            constructionType: 'pos',
            features: [
                'type' => 'POS',
                'value' => $posTag,
            ]
        );
        $posNode->activation = 0.0;

        return $posNode;
    }

    /**
     * Create a SeqColumn for a POS tag
     */
    private function createSeqColumnForPos(string $posTag): SeqColumn
    {
        $columnId = "SeqCol_{$posTag}";
        $sequencerName = "S{$this->sequencerCounter}";
        $this->sequencerCounter++;

        return new SeqColumn(
            construction_type: 'pos_pattern',
            span: [-1, -1],
            id: $columnId,
            features: [
                'pos_tag' => $posTag,
                'type' => 'pos_column',
            ],
            sequencerName: $sequencerName
        );
    }

    /**
     * Create sequential connections between two columns
     */
    private function createSequentialConnections(
        RuntimeGraph $graph,
        Column $previousSequencer,
        SeqColumn $previousColumn,
        Column $currentSequencer,
        SeqColumn $currentColumn
    ): void {
        // Get or create left node for the previous SEQUENCER source
        $leftNode = $currentColumn->getOrCreateLeftNode($previousSequencer->id);

        // Previous SEQUENCER → Current left node
        $seqEdge1 = new ConnectionEdge(
            source: $previousSequencer->id,
            target: $leftNode->id,
            type: 'feedforward',
            weight: 1.0
        );
        $graph->addEdge($seqEdge1);

        // Get or create right node for the current SEQUENCER source
        //        $rightNode = $previousColumn->getOrCreateRightNode($currentSequencer->id);
        //
        //        // Current SEQUENCER → Previous right node
        //        $seqEdge2 = new ConnectionEdge(
        //            source: $currentSequencer->id,
        //            target: $rightNode->id,
        //            type: 'feedforward',
        //            weight: 1.0
        //        );
        //        $graph->addEdge($seqEdge2);
    }

    /**
     * Process bigram pattern (3 consecutive SEQUENCERs)
     *
     * @return array [SeqColumn|null, previous L2 sequencer, previous L2 column]
     */
    private function processBigramPattern(
        RuntimeGraph $graph,
        // array             $sequencerHistory,
        SeqColumn $seq_a,
        ?SeqColumn $seq_b,
        ?SeqColumn $seq_c,
        array &$sequencerToL2Map,
        array &$higherLevelSequencers,
        ?Column $previousL2Sequencer,
        ?SeqColumn $previousL2Column
    ): array {
        if (is_null($seq_b)) {
            return [];
        }
        //        $seq_a = $sequencerHistory[0];
        //        $seq_b = $sequencerHistory[1];
        // $seq_c = $sequencerHistory[2];

        $seqAId = $seq_a->id;
        $seqBId = $seq_b->id;
        // $bigramId = "{$seq_a['pos_tag']}_{$seq_b['pos_tag']}";
        $bigramId = "{$seq_a->id}_{$seq_b->id}";

        if (isset($sequencerToL2Map[$seqAId])) {
            $higherSeqColumn = $sequencerToL2Map[$seqAId];

            // Reuse existing L2 column
            $this->connectBigramToColumn($graph, $seq_a, $higherSeqColumn, false, $seq_b, $seq_c);
        } else {
            // Create new L2 column
            $higherSeqColumn = $this->createHigherLevelSequencer($bigramId);
            $higherLevelSequencers[$bigramId] = $higherSeqColumn;
            $sequencerToL2Map[$seqAId] = $higherSeqColumn;

            // Connect the 2 SEQUENCERs to the higher-level column
            $this->connectBigramToColumn($graph, $seq_a, $higherSeqColumn, true, $seq_b, $seq_c);
        }

        // L2 Sequential connections
        //        if ($previousL2Sequencer !== null && $previousL2Column !== null) {
        //            $this->createSequentialConnections(
        //                $graph,
        //                $previousL2Sequencer,
        //                $previousL2Column,
        //                $higherSeqColumn->s_node,
        //                $higherSeqColumn
        //            );
        //        }

        return [$higherSeqColumn, $previousL2Sequencer, $previousL2Column];
    }

    /**
     * Process trigram pattern (3 consecutive SEQUENCERs)
     *
     * @return array [SeqColumn|null, previous L2 sequencer, previous L2 column]
     */
    private function processTrigramPattern(
        RuntimeGraph $graph,
        array $sequencerHistory,
        array &$sequencerToL2Map,
        array &$higherLevelSequencers,
        ?Column $previousL2Sequencer,
        ?SeqColumn $previousL2Column
    ): array {
        $seq_a = $sequencerHistory[0];
        $seq_b = $sequencerHistory[1];
        $seq_c = $sequencerHistory[2];

        $seqBId = $seq_b['sequencer']->id;
        $trigramId = "{$seq_a['pos_tag']}_{$seq_b['pos_tag']}_{$seq_c['pos_tag']}";

        // Check if SEQUENCER_B already has a connection to an L2 column
        if (isset($sequencerToL2Map[$seqBId])) {
            $higherSeqColumn = $sequencerToL2Map[$seqBId];

            // Reuse existing L2 column
            $this->connectTrigramToColumn($graph, $seq_a, $seq_c, $higherSeqColumn, false);
        } else {
            // Create new L2 column
            $higherSeqColumn = $this->createHigherLevelSequencer($trigramId);
            $higherLevelSequencers[$trigramId] = $higherSeqColumn;
            $sequencerToL2Map[$seqBId] = $higherSeqColumn;

            // Connect the 3 SEQUENCERs to the higher-level column
            $this->connectTrigramToColumn($graph, $seq_a, $seq_c, $higherSeqColumn, true, $seq_b);
        }

        // L2 Sequential connections
        if ($previousL2Sequencer !== null && $previousL2Column !== null) {
            $this->createSequentialConnections(
                $graph,
                $previousL2Sequencer,
                $previousL2Column,
                $higherSeqColumn->s_node,
                $higherSeqColumn
            );
        }

        return [$higherSeqColumn, $previousL2Sequencer, $previousL2Column];
    }

    /**
     * Connect bigram SEQUENCERs to higher-level column
     */
    private function connectBigramToColumn(
        RuntimeGraph $graph,
        // array        $seq_a,
        SeqColumn $seq_a,
        SeqColumn $higherSeqColumn,
        bool $connectHead = false,
        ?SeqColumn $seq_b = null,
        ?SeqColumn $seq_c = null,
    ): void {

        // Connect SeqColumn_A:left → left node
        $leftNodes_a = $seq_a->l_nodes;
        if (! empty($leftNodes_a)) {
            foreach ($leftNodes_a as $leftNode_a) {
                $leftNode = $higherSeqColumn->getOrCreateLeftNode($seq_a->id);
                $edgeLeft = new ConnectionEdge(
                    source: $leftNode_a->id,
                    target: $leftNode->id,
                    type: 'feedforward',
                    weight: 1.0
                );
                $graph->addEdge($edgeLeft);
            }
        }

        if (! is_null($seq_c)) {
            $leftNodes_c = $seq_c->l_nodes;
            if (! empty($leftNodes_c)) {
                foreach ($leftNodes_c as $leftNode_c) {
                    $edgeRight = new ConnectionEdge(
                        source: $higherSeqColumn->s_node->id,
                        target: $leftNode_c->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $graph->addEdge($edgeRight);
                }
            }
        }

        // Connect higherSeqColumn → next left nodes
        //        $leftNodes_a = $seq_a['column']->l_nodes;
        //        if (!empty($leftNodes_a)) {
        //            foreach ($leftNodes_a as $leftNode_a) {
        //                $leftNode = $higherSeqColumn->getOrCreateLeftNode($seq_a['sequencer']->id);
        //                $edgeLeft = new ConnectionEdge(
        //                    source: $leftNode_a->id,
        //                    target: $leftNode->id,
        //                    type: 'feedforward',
        //                    weight: 1.0
        //                );
        //                $graph->addEdge($edgeLeft);
        //            }
        //        }

        // Connect SEQUENCER_B → head (only for new columns)
        if ($connectHead && $seq_b) {

            // Connect SEQUENCER_A → head
            $edgeA = new ConnectionEdge(
                source: $seq_a->s_node->id,
                target: $higherSeqColumn->h_node->id,
                type: 'feedforward',
                weight: 1.0
            );
            $graph->addEdge($edgeA);

            $edgeB = new ConnectionEdge(
                source: $seq_b->s_node->id,
                target: $higherSeqColumn->h_node->id,
                type: 'feedforward',
                weight: 1.0
            );
            $graph->addEdge($edgeB);
        }

        //        // Connect SEQUENCER_C → right node
        //        $rightNode = $higherSeqColumn->getOrCreateRightNode($seq_c['sequencer']->id);
        //        $edgeC = new ConnectionEdge(
        //            source: $seq_c['sequencer']->id,
        //            target: $rightNode->id,
        //            type: 'feedforward',
        //            weight: 1.0
        //        );
        //        $graph->addEdge($edgeC);
    }

    /**
     * Connect trigram SEQUENCERs to higher-level column
     */
    private function connectTrigramToColumn(
        RuntimeGraph $graph,
        array $seq_a,
        array $seq_c,
        SeqColumn $higherSeqColumn,
        bool $connectHead = false,
        ?array $seq_b = null
    ): void {
        // Connect SEQUENCER_A → left node
        $leftNode = $higherSeqColumn->getOrCreateLeftNode($seq_a['sequencer']->id);
        $edgeA = new ConnectionEdge(
            source: $seq_a['sequencer']->id,
            target: $leftNode->id,
            type: 'feedforward',
            weight: 1.0
        );
        $graph->addEdge($edgeA);

        // Connect SEQUENCER_B → head (only for new columns)
        if ($connectHead && $seq_b) {
            $edgeB = new ConnectionEdge(
                source: $seq_b['sequencer']->id,
                target: $higherSeqColumn->h_node->id,
                type: 'feedforward',
                weight: 1.0
            );
            $graph->addEdge($edgeB);
        }

        // Connect SEQUENCER_C → right node
        $rightNode = $higherSeqColumn->getOrCreateRightNode($seq_c['sequencer']->id);
        $edgeC = new ConnectionEdge(
            source: $seq_c['sequencer']->id,
            target: $rightNode->id,
            type: 'feedforward',
            weight: 1.0
        );
        $graph->addEdge($edgeC);
    }

    /**
     * Create a higher-level SEQUENCER column for trigram patterns
     */
    private function createHigherLevelSequencer(string $trigramId): SeqColumn
    {
        $columnId = "SeqCol_L2_{$trigramId}";
        $sequencerName = "S{$this->sequencerCounter}";
        $this->sequencerCounter++;

        return new SeqColumn(
            construction_type: 'trigram_pattern',
            span: [-2, -2],
            id: $columnId,
            features: [
                'trigram' => $trigramId,
                'type' => 'higher_level_sequencer',
                'level' => 2,
            ],
            sequencerName: $sequencerName
        );
    }

    /**
     * Create PV inhibitory links for Winner-Take-All dynamics
     */
    private function createPVInhibitoryLinks(RuntimeGraph $graph, array $seqColumns): int
    {
        $linksCreated = 0;
        $pvNodesBySource = [
            'left' => [],
            'right' => [],
        ];

        // Collect all PV nodes by source ID
        foreach ($seqColumns as $column) {
            foreach ($column->getPVLeftNodes() as $sourceId => $pvNode) {
                if (! isset($pvNodesBySource['left'][$sourceId])) {
                    $pvNodesBySource['left'][$sourceId] = [];
                }
                $pvNodesBySource['left'][$sourceId][] = [
                    'column' => $column,
                    'pv_node' => $pvNode,
                ];
            }

            foreach ($column->getPVRightNodes() as $sourceId => $pvNode) {
                if (! isset($pvNodesBySource['right'][$sourceId])) {
                    $pvNodesBySource['right'][$sourceId] = [];
                }
                $pvNodesBySource['right'][$sourceId][] = [
                    'column' => $column,
                    'pv_node' => $pvNode,
                ];
            }
        }

        // Create inhibitory links between PV nodes with the same source
        foreach (['left', 'right'] as $position) {
            foreach ($pvNodesBySource[$position] as $sourceId => $pvNodesInfo) {
                if (count($pvNodesInfo) < 2) {
                    continue;
                }

                // Create bidirectional inhibitory links between all pairs
                $count = count($pvNodesInfo);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $column1 = $pvNodesInfo[$i]['column'];
                        $column2 = $pvNodesInfo[$j]['column'];

                        $sourceName = $this->getShortSourceName($sourceId);
                        $pvId1 = "{$column1->id}_PV_{$position[0]}_{$sourceName}";
                        $pvId2 = "{$column2->id}_PV_{$position[0]}_{$sourceName}";

                        $edge1 = new ConnectionEdge(
                            source: $pvId1,
                            target: $pvId2,
                            type: 'inhibitory',
                            weight: -1.0
                        );
                        $graph->addEdge($edge1);

                        $edge2 = new ConnectionEdge(
                            source: $pvId2,
                            target: $pvId1,
                            type: 'inhibitory',
                            weight: -1.0
                        );
                        $graph->addEdge($edge2);

                        $linksCreated += 2;
                    }
                }
            }
        }

        return $linksCreated;
    }

    /**
     * Get short source name from node ID
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

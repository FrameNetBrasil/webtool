<?php

namespace App\Models\CLN_RNT;

use App\Services\CLN_RNT\BuildStructureService;
use Illuminate\Support\Facades\DB;

class LearnGraph
{
    private const ACTIVATION_THRESHOLD = 0.5;

    private const MAX_ITERATIONS = 10;

    // Sequential processing parameters
    private const SEQUENTIAL_ITERATIONS = 5; // Fewer iterations per word

    private const ACTIVATION_DECAY = 0.6; // Decay factor for temporal context (0.6 = 40% decay)

    private const DT = 1.0;

    private const INHIBITION_STRENGTH = 0.5;

    // Hebbian learning parameters
    private const HEBBIAN_LEARNING_RATE = 0.175; // 0.1;

    private const HEBBIAN_COACTIVATION_THRESHOLD = 0.5;

    private const MAX_WEIGHT = 3.0;
    private array $columns = [];
    private array $nodes = [];

    private array $edges = [];

    private array $l1ByPosition = [];

    private array $l2BySpan = [];
    public BuildStructureService $buildStructure;

    /**
     * Cache for pattern graph edges (lazy loaded)
     * Key: from_node_id
     * Value: array of edge objects
     *
     * @var array<int, array>
     */
    private array $edgeCache = [];

    /**
     * Cache for pattern graph nodes (lazy loaded)
     * Key: node_id
     * Value: node data array
     *
     * @var array<int, array>
     */
    private array $nodeCache = [];

    /**
     * Cache for DATA node lookups
     * Key: "literal:{word}" or "slot:{pos}"
     * Value: array of matching DATA node IDs
     *
     * @var array<string, array>
     */
    private array $dataNodeCache = [];

    private int $sequencerCounter = 0;

    public function __construct(BuildStructureService $buildStructure)
    {
        $this->buildStructure = $buildStructure;
    }

    public function addColumn(
        string $cortical_level, // L1 or L2
        string $construction_type, // phrasal, sequencer, mwe
        array  $span,
        string $id,
        string $name,
        array  $features = []
    ): Column
    {
        $column = new Column(
            $cortical_level, // L1 or L2
            $construction_type, // phrasal, sequencer, mwe
            $span,
            $id,
            $name,
            $features = []
        );
        $this->columns[$cortical_level][$id] = $column;

        return $column;
    }

    public function getColumn(string $cortical_level, string $id): ?Column {
        return $this->columns[$cortical_level][$id] ?? null;
    }

    public function addL1Node(
        string $id,
        string $name,
        Column $column,
        int    $position,
        string $constructionType,
        array  $features = []
    ): Node
    {
        $value = $features['value'] ?? '';
        if ($value !== '') {
            $id .= "_{$value}";
        }

//        $column = $this->addColumn(
//            cortical_level: 'L1',
//            construction_type: $constructionType,
//            span: [$position, $position],
//            id: $id,
//            name: $name,
//            features: $features
//        );

//        $this->columns['L1'][$id] = $column;
        $features['name'] = $name;

        $node = $column->L5->createNode($id, 'DATA', [$position, $position], $features);

        $this->nodes[$id] = $node;
        $this->l1ByPosition[$position][] = $node;

        return $node;
    }

    public function getL1Nodes(): array
    {
        $nodes = [];
        foreach ($this->columns['L1'] as $L1Column) {
            $nodesAtL5 = $L1Column->getNodesByLayer('L5');
            foreach ($nodesAtL5 as $node) {
                $nodes[] = $node;
            }
        }
        return $nodes;
    }

    public function addL2L23Node(
        string $id,
        string $name,
        Column $column,
        int    $startPos,
        int    $endPos,
        array  $bindings = [],
        array  $features = []
    ): Node
    {
        $features['name'] = $name;
        $node = $column->L23->createNode($id, 'OR', [$startPos, $endPos], $features);
        $this->nodes[$id] = $node;
        $spanKey = "{$startPos}:{$endPos}";
        $this->l2BySpan[$spanKey][] = $node;
        return $node;
    }

    public function addL2L5Node(
        string $id,
        string $name,
        Column $column,
        int    $startPos,
        int    $endPos,
//        array  $bindings = [],
        array  $features = []
    ): Node
    {
        $features['name'] = $name;
        $node = $column->L5->createNode($id, 'SEQUENCER', [$startPos, $endPos], $features);
        $this->nodes[$id] = $node;
        $spanKey = "{$startPos}:{$endPos}";
        $this->l2BySpan[$spanKey][] = $node;
        return $node;
    }

    public function createPOSColumn(array $data): Column
    {
        $name = $data['word'];
        $position = $data['position'];
        $features = $data['features'];
        $posTag = $data['pos'];
        // 1. Add L1 node for this word
        $patternNode = $this->findDataNodesByLiteral($name);
        $idLiteralNode = empty($patternNode) ? 0 : $patternNode[0];
        $features['idPatternNode'] = $idLiteralNode;
        $id = "L1_L5_literal_{$name}";
        // $l1Node = $this->getNode($id);
        $idPosColumn = 'Column_' . $posTag;
        $posColumn = $this->columns['L1'][$idPosColumn] ?? null;
        if (is_null($posColumn)) {
            $posColumn = $this->addColumn(
                cortical_level: 'L1',
                construction_type: 'data',
                span: [$position, $position],
                id: $idPosColumn,
                name: $idPosColumn,
                features: $features
            );
        }

        $l1Node = $this->addL1Node(
            id: $id,
            name: $name,
            column: $posColumn,
            position: $position,
            constructionType: 'literal',
            features: $features
        );


        if ($idLiteralNode > 0) {
            // L23 for Literal

            $edges = $this->getOutgoingEdges($idLiteralNode);
            foreach ($edges as $edge) {
                $targetNodeId = $edge->to_node_id;
                $targetNode = $this->getPatternNode($targetNodeId);

                if (!$targetNode || $targetNode['type'] !== 'OR') {
                    continue;
                }

                $idColumn = $targetNodeId;
                $column = $this->columns['L2'][$idColumn] ?? null;
                if (is_null($column)) {
                    $colName = $idColumn;
                    $features['pos_tag'] = $colName;
                    $features['type'] = 'pos_column';
                    $column = $this->addColumn(
                        cortical_level: 'L2',
                        construction_type: 'pos_pattern',
                        span: [$position, $position],
                        id: $idColumn,
                        name: $colName,
                        features: $features
                    );
                }
                $column->isRoot = ($data['deprel'] == 'root');

                $features['idPatternNode'] = $targetNodeId;
                $features['type'] = 'or';
                //
                $orNode = $this->addL2L23Node(
                    id: "L2_L23_{$column->name}_h",
                    name: 'h',
                    column: $column,
                    startPos: $position,
                    endPos: $position,
                    features: $features
                );

                // Connect POS node to head node of SeqColumn
                $litToHeadEdge = new ConnectionEdge(
                    source: $l1Node->id,
                    target: $orNode->id,
                    type: 'feedforward',
                    weight: 1.0
                );
                $this->addEdge($litToHeadEdge);

                $edgesForOr = $this->getOutgoingEdges($targetNodeId);
                foreach ($edgesForOr as $edgeForOr) {
                    $sequencerNodeId = $edgeForOr->to_node_id;
                    $sequencerNode = $this->getPatternNode($sequencerNodeId);

                    if (!$sequencerNode || $sequencerNode['type'] !== 'SEQUENCER') {
                        continue;
                    }

                    $features['idPatternNode'] = $sequencerNodeId;
                    $features['type'] = 'sequencer';
                    if (!isset($sequencers[$sequencerNode['construction_name']])) {
                        $name = $sequencerNode['construction_name'];
                        $sequencer = $this->addL2L5Node(
                            id: "L2_L5_{$column->name}_{$name}",
                            name: $name,
                            column: $column,
                            startPos: $position,
                            endPos: $position,
                            features: $features
                        );
                        $sequencers[] = $sequencer;
                    } else {
                        $sequencer = $sequencers[$sequencerNode['construction_name']];
                    }

                    $edgeLeft = new ConnectionEdge(
                        source: $orNode->id,
                        target: $sequencer->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($edgeLeft);

                }
            }


        } else {
            // 2. Create POS node
            $patternNode = $this->findDataNodesByPos($posTag);
            $idPOSNode = empty($patternNode) ? 0 : $patternNode[0];
            $id = "L1_L5_pos_{$posTag}";
            $posNode = $this->addL1Node(
                id: $id,
                name: $posTag,
                column: $posColumn,
                position: $position,
                constructionType: 'pos',
                features: [
                    'type' => 'POS',
                    'value' => $posTag,
                    'idPatternNode' => $idPOSNode
                ]
            );

            // 3. Add edge connecting word to POS
            $edge = new ConnectionEdge(
                source: $l1Node->id,
                target: $posNode->id,
                type: 'category',
                weight: 1.0
            );
            $this->addEdge($edge);
            // L23 for POS

            $edges = $this->getOutgoingEdges($idPOSNode);
            foreach ($edges as $edge) {
                $targetNodeId = $edge->to_node_id;
                $targetNode = $this->getPatternNode($targetNodeId);

                if (!$targetNode || $targetNode['type'] !== 'OR') {
                    continue;
                }
                $idColumn = $targetNodeId;
                $column = $this->columns['L2'][$idColumn] ?? null;
                if (is_null($column)) {
                    $colName = $idColumn;
                    $features['pos_tag'] = $colName;
                    $features['type'] = 'pos_column';
                    $column = $this->addColumn(
                        cortical_level: 'L2',
                        construction_type: 'pos_pattern',
                        span: [$position, $position],
                        id: $idColumn,
                        name: $colName,
                        features: $features
                    );
                }
                $column->isRoot = ($data['deprel'] == 'root');

                $features['idPatternNode'] = $targetNodeId;
                $features['type'] = 'or';
                //
                $orNode = $this->addL2L23Node(
                    id: "L2_L23_{$column->name}_h",
                    name: 'h',
                    column: $column,
                    startPos: $position,
                    endPos: $position,
                    features: $features
                );

                // Connect POS node to head node of SeqColumn
                $posToHeadEdge = new ConnectionEdge(
                    source: $posNode->id,
                    target: $orNode->id,
                    type: 'feedforward',
                    weight: 1.0
                );
                $this->addEdge($posToHeadEdge);

                $edgesForOr = $this->getOutgoingEdges($targetNodeId);
                foreach ($edgesForOr as $edgeForOr) {
                    $sequencerNodeId = $edgeForOr->to_node_id;
                    $sequencerNode = $this->getPatternNode($sequencerNodeId);

                    if (!$sequencerNode || $sequencerNode['type'] !== 'SEQUENCER') {
                        continue;
                    }

                    $features['idPatternNode'] = $sequencerNodeId;
                    $features['type'] = 'sequencer';
                    if (!isset($sequencers[$sequencerNode['construction_name']])) {
                        $name = $sequencerNode['construction_name'];
                        $sequencer = $this->addL2L5Node(
                            id: "L2_L5_{$column->name}_{$name}",
                            name: $name,
                            column: $column,
                            startPos: $position,
                            endPos: $position,
                            features: $features
                        );
                        $sequencers[] = $sequencer;
                    } else {
                        $sequencer = $sequencers[$sequencerNode['construction_name']];
                    }

                    $edgeLeft = new ConnectionEdge(
                        source: $orNode->id,
                        target: $sequencer->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($edgeLeft);

                }
            }

        }


        return $column;
    }

    public function createNextLevelColumns(array $currentColumns): array
    {
        $next = [];
        $n = count($currentColumns);
        for ($i = 1; $i < $n; $i++) {
            $previousColumn = $currentColumns[$i - 1] ?? null;
            $currentColumn = $currentColumns[$i] ?? null;

            $id = "{$previousColumn->id}_l";
            $name = $id;
            $features['type'] = 'seq_column';

            $column = $this->columns['L2'][$id] ?? null;
            if (is_null($column)) {
                $column = $this->addColumn(
                    cortical_level: 'L2',
                    construction_type: 'seq_pattern',
                    span: [-1, -1],
                    id: $id,
                    name: $name,
                    features: $features
                );
                $sequencer = $this->addL2L5Node(
                    id: $id,
                    name: $name,
                    column: $column,
                    startPos: -1,
                    endPos: -1,
                    features: $features
                );
            } else {
                $sequencer = $this->getNode($id);
            }
            $sNodes = $previousColumn->getSNodes();
            foreach ($sNodes as $sNode) {
                $idOR = "L2_L23_{$column->name}_l";
                $orNode = $this->getNode($idOR);
                if (is_null($orNode)) {
                    $orNode = $this->addL2L23Node(
                        id: $idOR,
                        name: 'l',
                        column: $column,
                        startPos: -1,
                        endPos: -1,
                        features: $features
                    );
                    // Connect SEQ node to or node of Column
                    $seqToOrEdge = new ConnectionEdge(
                        source: $sNode->id,
                        target: $orNode->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($seqToOrEdge);

                    // Connect OR to SEQ of columns
                    $orToSeqEdge = new ConnectionEdge(
                        source: $orNode->id,
                        target: $sequencer->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($orToSeqEdge);

                    if ($previousColumn->isRoot) {
                        $idOrPreviousColumn = "L2_L23_{$previousColumn->name}_h";

                        // Connect SEQ node to OR from previous column
                        $seqToPreviousColumn = new ConnectionEdge(
                            source: $sequencer->id,
                            target: $idOrPreviousColumn,
                            type: 'feedforward',
                            weight: 1.0
                        );
                        $this->addEdge($seqToPreviousColumn);
                    }
                }
            }
            $sNodes = $currentColumn->getSNodes();
            foreach ($sNodes as $sNode) {
                $idOR = "L2_L23_{$column->name}_{$previousColumn->id}_{$currentColumn->id}_r";
                $orNode = $this->getNode($idOR);
                if (is_null($orNode)) {
                    $orNode = $this->addL2L23Node(
                        id: $idOR,
                        name: 'r',
                        column: $column,
                        startPos: -1,
                        endPos: -1,
                        features: $features
                    );
                    // Connect SEQ node to or node of Column
                    $seqToOrEdge = new ConnectionEdge(
                        source: $sNode->id,
                        target: $orNode->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($seqToOrEdge);

                    // Connect OR to DEQ of columns
                    $orToSeqEdge = new ConnectionEdge(
                        source: $orNode->id,
                        target: $sequencer->id,
                        type: 'feedforward',
                        weight: 1.0
                    );
                    $this->addEdge($orToSeqEdge);

                    if ($currentColumn->isRoot) {
                        $idOrCurrentColumn = "L2_L23_{$currentColumn->name}_h";

                        // Connect SEQ node to OR from previous column
                        $seqToCurrentColumn = new ConnectionEdge(
                            source: $sequencer->id,
                            target: $idOrCurrentColumn,
                            type: 'feedforward',
                            weight: 1.0
                        );
                        $this->addEdge($seqToCurrentColumn);
                    }

                }
            }

            $next[] = $column;
        }
        return $next;
    }


    public function addEdge(ConnectionEdge $edge): void
    {
        $edgeId = "{$edge->source}:{$edge->target}:{$edge->type}";
        $this->edges[$edgeId] = $edge;
    }

    public function removeEdge(string $sourceId, string $targetId, string $type = 'feedforward'): void
    {
        $edgeId = "{$sourceId}:{$targetId}:{$type}";
        unset($this->edges[$edgeId]);
    }

    public function getNode(string $id): ?Node
    {
        return $this->nodes[$id] ?? null;
    }

    public function getNodesAtPosition(int $position): array
    {
        return $this->l1ByPosition[$position] ?? [];
    }

    public function getNodesInSpan(int $start, int $end): array
    {
        $spanKey = "{$start}:{$end}";

        return $this->l2BySpan[$spanKey] ?? [];
    }

    public function getAllNodes(): array
    {
        return array_values($this->nodes);
    }

    public function getColumns(string $level): array
    {
        return $this->columns[$level];
    }


    public function getNodesByLevel(string $level, string $layer): array
    {
        $columns = $this->columns[$level] ?? [];
        $nodes = [];
        foreach ($columns as $column) {
            $nodes[] = $column->getNodesByLayer($layer);
        }
        return $nodes;
//        return array_filter(
//            $this->nodes,
//            fn ($node) => $node->cortical_level === $level
//        );
    }

    public function getEdges(string $sourceId): array
    {
        return array_filter(
            $this->edges,
            fn($edge) => $edge->source === $sourceId
        );
    }

//    public function getIncomingEdges(string $targetId): array
//    {
//        return array_filter(
//            $this->edges,
//            fn($edge) => $edge->target === $targetId
//        );
//    }

    public function removeNode(string $id): void
    {
        if (isset($this->nodes[$id])) {
            $node = $this->nodes[$id];

            if ($node->cortical_level === 'L1') {
                $position = $node->getPosition();
                if (isset($this->l1ByPosition[$position])) {
                    $this->l1ByPosition[$position] = array_filter(
                        $this->l1ByPosition[$position],
                        fn($n) => $n->id !== $id
                    );
                }
            } elseif ($node->cortical_level === 'L2') {
                $spanKey = $node->getSpanString();
                if (isset($this->l2BySpan[$spanKey])) {
                    $this->l2BySpan[$spanKey] = array_filter(
                        $this->l2BySpan[$spanKey],
                        fn($n) => $n->id !== $id
                    );
                }
            }

            unset($this->nodes[$id]);

            $this->edges = array_filter(
                $this->edges,
                fn($edge) => $edge->source !== $id && $edge->target !== $id
            );
        }
    }

    public function clear(): void
    {
        $this->nodes = [];
        $this->edges = [];
        $this->l1ByPosition = [];
        $this->l2BySpan = [];
    }

    /**
     * Find DATA nodes matching a literal word value
     *
     * @param string $word Word to match
     * @return array Array of DATA node IDs
     */
    private function findDataNodesByLiteral(string $word): array
    {
        $cacheKey = "literal:{$word}";

        if (isset($this->dataNodeCache[$cacheKey])) {
            return $this->dataNodeCache[$cacheKey];
        }

        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->where('value', $word)
            ->whereRaw("JSON_EXTRACT(specification, '$.dataType') = 'literal'")
            ->pluck('id')
            ->toArray();

        $this->dataNodeCache[$cacheKey] = $nodes;

        return $nodes;
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

    private function findORNode(string $construction, string $position): array
    {
        $cacheKey = "or:{$construction}_{$position}";

        if (isset($this->nodeCache[$cacheKey])) {
            return $this->nodeCache[$cacheKey];
        }
        $specification = '{"type":"OR","construction_name":"' . $construction . '","layer":"L23","position":"' . $position . '"}';
        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'OR')
            ->where('specification', $specification)
            ->pluck('id')
            ->toArray();
        $node = $nodes[0];
        $this->nodeCache[$cacheKey] = $node;

        return $node;
    }

    private function findSequencerNode(string $construction): array
    {
        $cacheKey = "seq:{$construction}";

        if (isset($this->nodeCache[$cacheKey])) {
            return $this->nodeCache[$cacheKey];
        }
        $specification = '{"type":"SEQUENCER","construction_name":"' . $construction . '","layer":"L5"}';
        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'SEQUENCER')
            ->where('specification', $specification)
            ->pluck('id')
            ->toArray();
        $node = $nodes[0];
        $this->nodeCache[$cacheKey] = $node;

        return $node;
    }

    /**
     * Get pattern graph node by ID (with caching)
     *
     * @param int $nodeId Node ID
     * @return array|null Node data or null
     */
    private function getPatternNode(int $nodeId): ?array
    {
        if (isset($this->nodeCache[$nodeId])) {
            return $this->nodeCache[$nodeId];
        }

        $node = DB::table('parser_pattern_node')
            ->where('id', $nodeId)
            ->select('id', 'type', 'specification', 'construction_name')
            ->first();

        if (!$node) {
            return null;
        }

        $this->nodeCache[$nodeId] = [
            'id' => $node->id,
            'type' => $node->type,
            'specification' => json_decode($node->specification, true),
            'construction_name' => $node->construction_name,
        ];

        return $this->nodeCache[$nodeId];
    }

    /**
     * Get outgoing edges from a node (with caching)
     *
     * @param int $fromNodeId Source node ID
     * @return array Array of edge objects
     */
    private function getOutgoingEdges(int $fromNodeId): array
    {
        if (isset($this->edgeCache[$fromNodeId])) {
            return $this->edgeCache[$fromNodeId];
        }

        $edges = DB::table('parser_pattern_edge')
            ->where('from_node_id', $fromNodeId)
            ->select('from_node_id', 'to_node_id', 'properties', 'sequence')
            ->get()
            ->all();

        $this->edgeCache[$fromNodeId] = $edges;

        return $edges;
    }

    /**
     * Apply Hebbian learning to strengthen co-active connections
     *
     * "Neurons that fire together, wire together"
     * Strengthens weights of edges where both source and target are active.
     *
     */
    private function applyHebbianLearning(Node $source, Node $target): void
    {
        $idSource = $source->id;
        $idTarget = $target->id;
        $edges = $this->getEdges($idSource);
        foreach ($edges as $edge) {
            if ($edge->target == $idTarget) {
                // Hebbian learning rule: Δw = η * a_source * a_target
                $deltaWeight = self::HEBBIAN_LEARNING_RATE;// * $source->activation * $target->activation;

                // Update weight (capped at MAX_WEIGHT)
                $edge->weight = min(self::MAX_WEIGHT, $edge->weight + $deltaWeight);
            }
        }
    }


}

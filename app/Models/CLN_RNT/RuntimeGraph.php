<?php

namespace App\Models\CLN_RNT;

use App\Services\CLN_RNT\BuildStructureService;
use Illuminate\Support\Facades\DB;

class RuntimeGraph
{
    private array $columns = [];
    private array $nodes = [];

    private array $literalNodes = [];

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

    public string $output_dir;

    public function __construct(BuildStructureService $buildStructure)
    {
        $this->buildStructure = $buildStructure;
    }

    public function setOutputDir(string $output_dir): void
    {
        $this->output_dir = $output_dir;
    }

    public function addData(array $data)
    {
        $name = $data['word'];
        $position = $data['position'];
        $features = $data['features'];
        $posTag = $data['pos'];
        // 1. Add DATA node for this word
        $patternNode = $this->findDataNodesByLiteral($name);
        $features['idPatternNode'] = empty($patternNode) ? 0 : $patternNode[0]->id;
        $l1Node = $this->addDataNode(
            id: "{$name}_{$position}",
            name: $name,
            idPatternNode: empty($patternNode) ? 0 : $patternNode[0]->id,
            position: $position,
            constructionType: 'literal',
            features: $features
        );
        // 2. Create POS node
        $patternNode = $this->findDataNodesByPos($posTag);
        $idPatternNode = empty($patternNode) ? 0 : $patternNode[0];
        $posNode = $this->addDataNode(
            id: $idPatternNode,
            name: $posTag,
            idPatternNode: $idPatternNode,
            position: $position,
            constructionType: 'pos',
            features: [
                'type' => 'POS',
                'value' => $posTag,
            ]
        );

        // 3. Add edge connecting word to POS
        $edge = new ConnectionEdge(
            source: $l1Node->id,
            target: $idPatternNode,
            type: 'category',
            weight: 1.0
        );
        $this->addEdge($edge);
        $this->buildFromPOS($posNode, $position);
    }

    public function addDataNode(
        string $id,
        string $name,
        int    $idPatternNode,
        int    $position,
        string $constructionType,
        array  $features
    ): Node
    {
        if ($idPatternNode == 0) {
            $idPatternNode = $id;
        }
        $node = $this->nodes[$idPatternNode] ?? null;
        if (is_null($node)) {
            $features['name'] = $name;
            $node = new Node($id, "DATA", $idPatternNode, [$position, $position], $features);
            $this->nodes[$idPatternNode] = $node;
            $this->l1ByPosition[$position][] = $node;
            if ($constructionType === 'literal') {
                $this->literalNodes[] = $node;
//                $node->activation = 1.0;
            }
        }
        return $node;
    }

    public function addNode(
        string $id,
        string $name,
        int    $idPatternNode,
        array  $span,
        string $type, // OR, AND, SOM, VIP
        array  $features
    ): Node
    {
        $node = $this->nodes[$idPatternNode] ?? null;
        if (is_null($node)) {
            $features['name'] = $name;
            $node = new Node($id, $type, $idPatternNode, $span, $features);
            $this->nodes[$id] = $node;
        }
        return $node;
    }

    public function addEOS(int $position): void {
        $eosNode = $this->addDataNode(
            id: "eos",
            name: 'EOS_' . $position,
            idPatternNode: 0,
            position: $position,
            constructionType: 'literal',
            features: []
        );
        // link to all VIP+ nodes
        foreach ($this->nodes as $node) {
            if ($node->type == 'VIP') {
                $connectionEdge = new ConnectionEdge(
                    source: $eosNode->id,
                    target: $node->id,
                    type: 'eos',
                    weight: 1.0,
                    optional: false,
                );
                $this->addEdge($connectionEdge);
            }
        }
    }

    public function buildFromPOS(Node $posNode, int $position): void
    {

        $nodes = [$posNode];
        $i = 0;
        do {
            $next = [];
            foreach ($nodes as $node) {
                $edges = $this->getOutgoingEdges($node->getIdPatternNode());

                foreach ($edges as $edge) {
                    $targetNodeId = $edge->to_node_id;
                    $targetNode = $this->getPatternNode($targetNodeId);

                    if (!$targetNode) {
                        continue;
                    }
                    $target = $this->nodes[$targetNodeId] ?? null;
                    if (is_null($target)) {
                        $name = $targetNode['construction_name'];
                        $target = $this->addNode(
                            id: $targetNodeId,
                            name: $name,
                            idPatternNode: $targetNodeId,
                            span: [$position, $position],
                            type: $targetNode['type'],
                            features: [
                                'value' => $targetNode['value'] ?? null,
                            ]
                        );
                    }
                    $properties = json_decode($edge->properties, true);
                    $connectionEdge = new ConnectionEdge(
                        source: $node->id,
                        target: $target->id,
                        type: $properties['label'],
                        weight: 1.0,
                        optional: (isset($properties['optional']) && $properties['optional'] === true),
                    );
                    $this->addEdge($connectionEdge);
                    $next[] = $target;
                }
            }
            $nodes = $next;
            ++$i;
        } while ((!empty($nodes) && ($i < 15)));

    }


    public function addL1Node(string $name, int $position, string $constructionType, array $features = []): Node
    {
        $value = $features['value'] ?? '';
        $id = "L1_P{$position}_{$constructionType}";
        if ($value !== '') {
            $id .= "_{$value}";
        }

        $column = new Column(
            cortical_level: 'L1',
            construction_type: $constructionType,
            span: [$position, $position],
            id: $id,
            name: $name,
            features: $features
        );

        $this->columns['L1'][$id] = $column;
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
        string $name,
        Column $column,
        int    $startPos,
        int    $endPos,
        array  $bindings = [],
        array  $features = []
    ): Node
    {
        $id = "L2_L23_{$column->name}_{$name}_{$features['idPatternNode']}_{$startPos}:{$endPos}";

        $features['name'] = $name;

        $node = $column->L23->createNode($id, 'OR', [$startPos, $endPos], $features);

        $this->nodes[$id] = $node;
        $spanKey = "{$startPos}:{$endPos}";
        $this->l2BySpan[$spanKey][] = $node;

        return $node;
    }

    public function addL2L5Node(
        string $name,
        Column $column,
        int    $startPos,
        int    $endPos,
//        array  $bindings = [],
        array  $features = []
    ): Node
    {
        $id = "L2_L5_{$column->name}_{$name}_{$features['idPatternNode']}_{$startPos}:{$endPos}";

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
        $features['idPatternNode'] = empty($patternNode) ? 0 : $patternNode[0]->id;
        $l1Node = $this->addL1Node(
            name: $name,
            position: $position,
            constructionType: 'literal',
            features: $features
        );
        // 2. Create POS node
        $patternNode = $this->findDataNodesByPos($posTag);
        $idPOSNode = empty($patternNode) ? 0 : $patternNode[0];
        $posNode = $this->addL1Node(
            name: $posTag,
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

        $this->sequencerCounter++;
        $id = "Col_{$posTag}_{$this->sequencerCounter}";
        $name = "S{$this->sequencerCounter}";
        $features['pos_tag'] = $posTag;
        $features['type'] = 'pos_column';

        $column = new Column(
            cortical_level: 'L2',
            construction_type: 'pos_pattern',
            span: [$position, $position],
            id: $id,
            name: $name,
            features: $features
        );
        $this->columns['L2'][$id] = $column;

        $edges = $this->getOutgoingEdges($idPOSNode);
        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            $features['idPatternNode'] = $targetNodeId;
            $features['type'] = 'or';
            //
            $orNode = $this->addL2L23Node(
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
                    $sequencer = $this->addL2L5Node(
                        name: $sequencerNode['construction_name'],
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
        return $column;
    }

    public function createPOSColumnLearn(array $data): Column
    {
        $name = $data['word'];
        $position = $data['position'];
        $features = $data['features'];
        $posTag = $data['pos'];
        // 1. Add L1 node for this word
        $patternNode = $this->findDataNodesByLiteral($name);
        $features['idPatternNode'] = empty($patternNode) ? 0 : $patternNode[0]->id;

        $id = "L1_P{$position}_literal";
        $l1Node = $this->getNode($id);
        if (is_null($l1Node)) {
            $l1Node = $this->addL1Node(
                name: $name,
                position: $position,
                constructionType: 'literal',
                features: $features
            );
        }
        // 2. Create POS node
        $patternNode = $this->findDataNodesByPos($posTag);
        $idPOSNode = empty($patternNode) ? 0 : $patternNode[0];
        $posNode = $this->addL1Node(
            name: $posTag,
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

        $this->sequencerCounter++;
        $id = "Col_{$posTag}_{$this->sequencerCounter}";
        $name = "S{$this->sequencerCounter}";
        $features['pos_tag'] = $posTag;
        $features['type'] = 'pos_column';

        $column = new Column(
            cortical_level: 'L2',
            construction_type: 'pos_pattern',
            span: [$position, $position],
            id: $id,
            name: $name,
            features: $features
        );
        $this->columns['L2'][$id] = $column;

        $edges = $this->getOutgoingEdges($idPOSNode);
        foreach ($edges as $edge) {
            $targetNodeId = $edge->to_node_id;
            $targetNode = $this->getPatternNode($targetNodeId);

            if (!$targetNode || $targetNode['type'] !== 'OR') {
                continue;
            }

            $features['idPatternNode'] = $targetNodeId;
            $features['type'] = 'or';
            //
            $orNode = $this->addL2L23Node(
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
                    $sequencer = $this->addL2L5Node(
                        name: $sequencerNode['construction_name'],
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
        return $column;
    }

    public function createNextLevelColumns(array $currentColumns): array
    {
        $next = [];
        $n = count($currentColumns);
        for ($i = 1; $i < $n; $i++) {
            $sequencers = [];
            $this->sequencerCounter++;
            $id = "Col_{$this->sequencerCounter}";
            $name = "S{$this->sequencerCounter}";
            $features['type'] = 'seq_column';

            $column = new Column(
                cortical_level: 'L2',
                construction_type: 'seq_pattern',
                span: [-1, -1],
                id: $id,
                name: $name,
                features: $features
            );
            $this->columns['L2'][$id] = $column;
            $previousColumn = $currentColumns[$i - 1] ?? null;
            $sNodes = $previousColumn->getSNodes();
            foreach ($sNodes as $sNode) {
                $idSPatternNode = $sNode->getIdPatternNode();
                $edges = $this->getOutgoingEdges($idSPatternNode);
                foreach ($edges as $edge) {
                    $targetNodeId = $edge->to_node_id;
                    $targetNode = $this->getPatternNode($targetNodeId);
                    if (!$targetNode || $targetNode['type'] !== 'OR') {
                        continue;
                    }
                    $features['idPatternNode'] = $targetNodeId;
                    $features['type'] = 'or';
                    $position = $targetNode['specification']['position'];
                    if ($position != 'left') {
                        continue;
                    }
                    //
                    $orNode = $this->addL2L23Node(
                        name: $position[0],
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
                            $sequencer = $this->addL2L5Node(
                                name: $sequencerNode['construction_name'],
                                column: $column,
                                startPos: -1,
                                endPos: -1,
                                features: $features
                            );

                            $sequencers[] = $sequencer;
                        } else {
                            $sequencer = $sequencers[$sequencerNode['construction_name']];
                        }

                        $otToSeqEdge = new ConnectionEdge(
                            source: $orNode->id,
                            target: $sequencer->id,
                            type: 'feedforward',
                            weight: 1.0
                        );
                        $this->addEdge($otToSeqEdge);
                    }
                }
            }
            $currentColumn = $currentColumns[$i] ?? null;
            $sNodes = $currentColumn->getSNodes();
            foreach ($sNodes as $sNode) {
                $idSPatternNode = $sNode->getIdPatternNode();
                $edges = $this->getOutgoingEdges($idSPatternNode);
                foreach ($edges as $edge) {
                    $targetNodeId = $edge->to_node_id;
                    $targetNode = $this->getPatternNode($targetNodeId);
                    if (!$targetNode || $targetNode['type'] !== 'OR') {
                        continue;
                    }
                    $features['idPatternNode'] = $targetNodeId;
                    $features['type'] = 'or';
                    $position = $targetNode['specification']['position'];
                    if ($position != 'head') {
                        continue;
                    }
                    //
                    $orNode = $this->addL2L23Node(
                        name: $position[0],
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
                            $sequencer = $this->addL2L5Node(
                                name: $sequencerNode['construction_name'],
                                column: $column,
                                startPos: -1,
                                endPos: -1,
                                features: $features
                            );

                            $sequencers[] = $sequencer;
                        } else {
                            $sequencer = $sequencers[$sequencerNode['construction_name']];
                        }

                        $otToSeqEdge = new ConnectionEdge(
                            source: $orNode->id,
                            target: $sequencer->id,
                            type: 'feedforward',
                            weight: 1.0
                        );
                        $this->addEdge($otToSeqEdge);
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

    public function getLiteralNodes(): array
    {
        return $this->literalNodes;
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

    public function getIncomingEdges(string $targetId): array
    {
        return array_filter(
            $this->edges,
            fn($edge) => $edge->target === $targetId
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
            ->select('id', 'type', 'specification', 'construction_name','value')
            ->first();

        if (!$node) {
            return null;
        }

        $this->nodeCache[$nodeId] = [
            'id' => $node->id,
            'type' => $node->type,
            'specification' => json_decode($node->specification, true),
            'construction_name' => $node->construction_name,
            'value' => $node->value,
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

}

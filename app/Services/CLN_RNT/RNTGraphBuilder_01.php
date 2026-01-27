<?php

namespace App\Services\CLN_RNT;

use App\Database\Criteria;
use Illuminate\Support\Facades\DB;

/**
 * RNT (Relational Network Theory) Graph Builder
 *
 * Builds pattern graphs using SeqColumn structure:
 * - Each construction is represented by a SeqColumn
 * - SeqColumn has:
 *   - L5: One SEQUENCER node
 *   - L23: Three OR nodes (left, head, right)
 * - DATA nodes represent matching criteria (words, POS, CE, etc.)
 * - Pattern elements connect to appropriate OR nodes based on position
 * - MWE is represented by AND nodes
 */
class RNTGraphBuilder_01
{
    private int $nodeCounter = 0;

    private array $stats = [
        'constructions_processed' => 0,
        'seq_columns_created' => 0,
        'sequencer_nodes' => 0,
        'or_nodes' => 0,
        'and_nodes' => 0,
        'data_nodes' => 0,
        'edges_created' => 0,
        'constructions_skipped' => 0,
        'mwe_patterns' => 0,
    ];

    private bool $dryRun;

    /**
     * Registry of construction names to their SeqColumn node IDs
     *
     * @var array<string, array{
     *   sequencer_id: int,
     *   left_or_id: int,
     *   head_or_id: int,
     *   right_or_id: int,
     *   construction_type: string,
     *   pattern_id: int
     * }>
     */
    private array $columnRegistry = [];

    /**
     * Registry of DATA nodes by their specification hash
     * Enables DATA node deduplication across patterns
     *
     * @var array<string, int> spec_hash => db_node_id
     */
    private array $dataNodeRegistry = [];

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Build the complete RNT pattern graph from all enabled constructions
     *
     * Uses a two-pass approach:
     * 1. PASS 1: Create a Column for each construction (according to construction_type)
     * 2. PASS 2: Process patterns and connect to left/head/right OR nodes
     */
    public function buildGraph(): void
    {
        $constructions = DB::table('parser_construction_v4')
            ->whereNotNull('compiledPattern')
            ->where('enabled', 1)
            ->orderBy('idConstruction')
            ->get();

        // PASS 1: Create SeqColumn structure for each construction
        foreach ($constructions as $construction) {
            $this->createColumnForConstruction($construction);
        }

        // PASS 2: Process patterns and connect elements to OR nodes
        foreach ($constructions as $construction) {
            $this->connectPatternToColumn($construction);
        }

        $this->stats['constructions_processed'] = count($constructions);
    }

    public function createColumnForPhrase($construction): void
    {
        $pattern = json_decode($construction->compiledPattern, true);
        $patternId = $construction->idConstruction + 1000;
        $constructionName = 'phrase_' . $construction->name;
        $constructionType = 'clausal';//$construction->constructionType ?? 'phrasal';

        // sequencer for this construction
        $sequencer = Criteria::table("parser_pattern_node")
            ->where("construction_name", $construction->name)
            ->where("type", "SEQUENCER")
            ->first();

        $leftOrSpec = [
            'type' => 'OR',
            'construction_name' => $constructionName,
            'layer' => 'L23',
            'position' => 'left',
        ];
        $leftOrId = $this->insertNode($leftOrSpec, $patternId, 'L23_left');

        $this->insertEdge($patternId, $sequencer->id, $leftOrId, [
            'sequence' => 1,
            'label' => 'left',
            'position' => 'left',
        ]);

        // Create SEQUENCER node (L5)
        $sequencerSpec = [
            'type' => 'SEQUENCER',
            'construction_name' => $constructionName,
            'layer' => 'L5',
        ];
        $sequencerId = $this->insertNode($sequencerSpec, $patternId, 'L5_S');

        $this->insertEdge($patternId, $leftOrId, $sequencerId, [
            'sequence' => 1,
            'label' => 'left',
            'position' => 'left',
        ]);
//        if ($constructionType == 'sequencer') {
//
//
//            $rightOrSpec = [
//                'type' => 'OR',
//                'construction_name' => $constructionName,
//                'layer' => 'L23',
//                'position' => 'right',
//            ];
//            $rightOrId = $this->insertNode($rightOrSpec, $patternId, 'L23_right');
//
//            // Create internal edges: OR nodes → SEQUENCER
//            $this->insertEdge($patternId, $leftOrId, $sequencerId, [
//                'sequence' => 0,
//                'label' => 'left',
//                'position' => 'left',
//            ]);
//
//            $this->insertEdge($patternId, $rightOrId, $sequencerId, [
//                'sequence' => 2,
//                'label' => 'right',
//                'position' => 'right',
//            ]);
//        }

        // Register the Column
        $this->columnRegistry[$constructionName] = [
            'sequencer_id' => $sequencerId,
            'left_or_id' => $leftOrId ?? null,
//            'head_or_id' => $headOrId,
            'right_or_id' => $rightOrId ?? null,
            'construction_type' => $constructionType,
            'pattern_id' => $patternId,
        ];

        $this->stats['seq_columns_created']++;
    }


    /**
     * PASS 1: Create Column structure for a construction
     */
    private function createColumnForConstruction($construction): void
    {
        $pattern = json_decode($construction->compiledPattern, true);
        if (!is_array($pattern)) {
            $this->stats['constructions_skipped']++;
            return;
        }

        $patternId = $construction->idConstruction;
        $constructionName = $construction->name;
        $constructionType = $construction->constructionType ?? 'phrasal';

        if ($constructionType == 'mwe') {

            $pattern = json_decode($construction->compiledPattern, true);
            if (!is_array($pattern)) {
                return;
            }

//            $constructionName = $construction->name;
//            $seqColumn = $this->columnRegistry[$constructionName] ?? null;

//            if (!$seqColumn) {
//                return;
//            }

            $nodes = $pattern['nodes'] ?? [];
            $edges = $pattern['edges'] ?? [];

            // Analyze pattern structure
            $structure = $this->analyzePatternStructure($nodes, $edges);

            $elements = $structure['elements'] ?? [];

            // Create DATA/CONSTRUCTION_REF nodes for each element
            $elementNodeIds = [];
            foreach ($elements as $clnNodeId) {
                $node = $nodes[$clnNodeId] ?? null;
                if (!$node) {
                    continue;
                }

                // Skip START, END, INTERMEDIATE
                if (in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE'])) {
                    continue;
                }

                // Create DATA node
                $dataNodeId = $this->createDataNode($node, $patternId);
                if ($dataNodeId) {
                    $elementNodeIds[] = $dataNodeId;
                }
            }

            if (count($elementNodeIds) === 1) {
                // Create AND node
                $andSpec = [
                    'type' => 'AND',
                    'position' => 'root',
                    'construction_name' => $constructionName,
                ];
                $rootAndNodeId = $this->insertNode($andSpec, $patternId, 'mwe_and_0');
                // Single element: link directly to target head OR
//                $this->insertEdge($patternId, $elementNodeIds[0], $targetHeadOrId, [
//                    'sequence' => 0,
//                    'label' => 'mwe_single',
//                ]);
            } else {
                // Multiple elements: Build AND binary tree
                $rootAndNodeId = $this->buildMWEBinaryTree($constructionName, $elementNodeIds, $patternId);

                // Link root AND node to target construction's head OR
//                $this->insertEdge($patternId, $rootAndNodeId, $targetHeadOrId, [
//                    'sequence' => 0,
//                    'label' => 'mwe_composition',
//                ]);
            }

            // Register the Column
            $this->columnRegistry[$constructionName] = [
                'and_root_id' => $rootAndNodeId,
//                'left_or_id' => $leftOrId,
//                'head_or_id' => $headOrId,
//                'right_or_id' => $rightOrId,
                'construction_type' => $constructionType,
                'pattern_id' => $patternId,
            ];

            $this->stats['mwe_patterns']++;

        } else {

            // Create SEQUENCER node (L5)
            $sequencerSpec = [
                'type' => 'SEQUENCER',
                'construction_name' => $constructionName,
                'layer' => 'L5',
            ];
            $sequencerId = $this->insertNode($sequencerSpec, $patternId, 'L5_S');

            $headOrSpec = [
                'type' => 'OR',
                'construction_name' => $constructionName,
                'layer' => 'L23',
                'position' => 'head',
            ];
            $headOrId = $this->insertNode($headOrSpec, $patternId, 'L23_head');

            $this->insertEdge($patternId, $headOrId, $sequencerId, [
                'sequence' => 1,
                'label' => 'head',
                'position' => 'head',
            ]);
            if ($constructionType == 'sequencer') {

                $leftOrSpec = [
                    'type' => 'OR',
                    'construction_name' => $constructionName,
                    'layer' => 'L23',
                    'position' => 'left',
                ];
                $leftOrId = $this->insertNode($leftOrSpec, $patternId, 'L23_left');

                $rightOrSpec = [
                    'type' => 'OR',
                    'construction_name' => $constructionName,
                    'layer' => 'L23',
                    'position' => 'right',
                ];
                $rightOrId = $this->insertNode($rightOrSpec, $patternId, 'L23_right');

                // Create internal edges: OR nodes → SEQUENCER
                $this->insertEdge($patternId, $leftOrId, $sequencerId, [
                    'sequence' => 0,
                    'label' => 'left',
                    'position' => 'left',
                ]);

                $this->insertEdge($patternId, $rightOrId, $sequencerId, [
                    'sequence' => 2,
                    'label' => 'right',
                    'position' => 'right',
                ]);
            }

            // Register the Column
            $this->columnRegistry[$constructionName] = [
                'sequencer_id' => $sequencerId,
                'left_or_id' => $leftOrId ?? null,
                'head_or_id' => $headOrId,
                'right_or_id' => $rightOrId ?? null,
                'construction_type' => $constructionType,
                'pattern_id' => $patternId,
            ];

            $this->stats['seq_columns_created']++;
        }
    }

    /**
     * PASS 2: Connect pattern elements to Column OR nodes
     *
     * Rules:
     * - Phrasal constructions: All pattern alternatives connect to head OR
     * - MWE: Process as sequence, connecting to left/head/right based on position
     * - Sequencer: Three elements (first→left, middle→head, third→right)
     */
    private function connectPatternToColumn($construction): void
    {
        $pattern = json_decode($construction->compiledPattern, true);
        if (!is_array($pattern)) {
            return;
        }

        $constructionName = $construction->name;
        $seqColumn = $this->columnRegistry[$constructionName] ?? null;

        if (!$seqColumn) {
            return;
        }

        $nodes = $pattern['nodes'] ?? [];
        $edges = $pattern['edges'] ?? [];

        // Analyze pattern structure
        $structure = $this->analyzePatternStructure($nodes, $edges);

        // Connect based on construction type
        $constructionType = $construction->constructionType ?? 'phrasal';

        if ($constructionType === 'mwe') {
            $this->connectMWEPattern($structure, $nodes, $construction, $seqColumn);
        } elseif ($constructionType === 'sequencer') {
            $this->connectSequencerPattern($structure, $nodes, $construction, $seqColumn);
        } else {
            // Phrasal or default: connect all alternatives to head
            $this->connectPhrasalPattern($structure, $nodes, $construction, $seqColumn);
        }
    }

    /**
     * Analyze pattern structure to extract elements
     */
    private function analyzePatternStructure(array $nodes, array $edges): array
    {
        // Find START and END nodes
        $startId = null;
        $endId = null;

        foreach ($nodes as $nodeId => $node) {
            if ($node['type'] === 'START') {
                $startId = $nodeId;
            } elseif ($node['type'] === 'END') {
                $endId = $nodeId;
            }
        }

        if (!$startId || !$endId) {
            return ['type' => 'empty', 'elements' => []];
        }

        // Find paths from START
        $pathsFromStart = array_filter($edges, fn($e) => $e['from'] === $startId);

        if (count($pathsFromStart) > 1) {
            // Multiple alternatives
            return [
                'type' => 'alternative',
                'alternatives' => $this->extractAlternatives($startId, $endId, $edges, $nodes),
            ];
        } else {
            // Single sequence
            return [
                'type' => 'sequence',
                'elements' => $this->extractSequence($startId, $endId, $edges, $nodes),
            ];
        }
    }

    /**
     * Extract alternative paths from START to END
     */
    private function extractAlternatives(string $startId, string $endId, array $edges, array $nodes): array
    {
        $alternatives = [];

        foreach ($edges as $edge) {
            if ($edge['from'] === $startId) {
                $path = $this->tracePath($edge['to'], $endId, $edges, $nodes);
                if (!empty($path)) {
                    $alternatives[] = $path;
                }
            }
        }

        return $alternatives;
    }

    /**
     * Extract sequence of elements from START to END
     */
    private function extractSequence(string $startId, string $endId, array $edges, array $nodes): array
    {
        $sequence = [];
        $current = $startId;
        $visited = [];

        while ($current !== $endId) {
            if (isset($visited[$current])) {
                break;
            }
            $visited[$current] = true;

            $nextEdge = null;
            foreach ($edges as $edge) {
                if ($edge['from'] === $current) {
                    $nextEdge = $edge;
                    break;
                }
            }

            if (!$nextEdge) {
                break;
            }

            $nextNode = $nodes[$nextEdge['to']] ?? null;
            if ($nextNode && $nextNode['type'] !== 'END') {
                $sequence[] = $nextEdge['to'];
            }

            $current = $nextEdge['to'];
        }

        return $sequence;
    }

    /**
     * Trace a path from node to END
     */
    private function tracePath(string $fromId, string $endId, array $edges, array $nodes): array
    {
        $path = [];
        $current = $fromId;
        $visited = [];

        while ($current !== $endId) {
            if (isset($visited[$current])) {
                break;
            }
            $visited[$current] = true;

            $node = $nodes[$current] ?? null;
            if ($node && $node['type'] !== 'END') {
                $path[] = $current;
            }

            $nextEdge = null;
            foreach ($edges as $edge) {
                if ($edge['from'] === $current) {
                    $nextEdge = $edge;
                    break;
                }
            }

            if (!$nextEdge) {
                break;
            }

            $current = $nextEdge['to'];
        }

        return $path;
    }

    /**
     * Connect phrasal pattern: all alternatives go to head OR
     */
    private function connectPhrasalPattern(array $structure, array $nodes, $construction, array $seqColumn): void
    {
        $patternId = $construction->idConstruction;
        $headOrId = $seqColumn['head_or_id'];

        if ($structure['type'] === 'alternative') {
            // Connect each alternative to head OR
            $sequence = 0;
            foreach ($structure['alternatives'] as $alternative) {
                foreach ($alternative as $nodeId) {
                    $this->connectElementToOr($nodeId, $nodes, $headOrId, $patternId, $sequence++);
                }
            }
        } elseif ($structure['type'] === 'sequence') {
            // Connect all elements to head OR
            $sequence = 0;
            foreach ($structure['elements'] as $nodeId) {
                $this->connectElementToOr($nodeId, $nodes, $headOrId, $patternId, $sequence++);
            }
        }
    }

    /**
     * Connect MWE pattern: build AND binary tree and link to target construction
     *
     * MWE constructions:
     * - Build binary tree using AND nodes for the MWE elements
     * - Link the root AND node to the head OR node of target construction (from phrasalCE)
     */
    private function connectMWEPattern(array $structure, array $nodes, $construction, array $seqColumn): void
    {
        if ($structure['type'] !== 'sequence') {
            // Fallback to phrasal if not a sequence
            $this->connectPhrasalPattern($structure, $nodes, $construction, $seqColumn);

            return;
        }

        $elements = $structure['elements'];
        $patternId = $construction->idConstruction;

        if (count($elements) === 0) {
            return;
        }

        // Get target construction from phrasalCE field (uppercased)
        $targetConstructionName = mb_strtoupper($construction->phrasalCE ?? '', 'UTF-8');
        $targetSeqColumn = $this->columnRegistry[$targetConstructionName] ?? null;

        if (!$targetSeqColumn) {
            // No valid target construction, fallback to phrasal pattern
            $this->connectPhrasalPattern($structure, $nodes, $construction, $seqColumn);

            return;
        }

        $targetHeadOrId = $targetSeqColumn['head_or_id'];
        $rootAndNodeId = $seqColumn['and_root_id'];
        $this->insertEdge($patternId, $rootAndNodeId, $targetHeadOrId, [
            'sequence' => 0,
            'label' => 'mwe_composition',
        ]);


        // Create DATA/CONSTRUCTION_REF nodes for each element
//        $elementNodeIds = [];
//        foreach ($elements as $clnNodeId) {
//            $node = $nodes[$clnNodeId] ?? null;
//            if (!$node) {
//                continue;
//            }
//
//            // Skip START, END, INTERMEDIATE
//            if (in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE'])) {
//                continue;
//            }
//
//            if ($this->isConstructionReference($node)) {
//                // Get referenced construction's SEQUENCER
//                $referencedName = $this->getReferencedConstructionName($node);
//                $referencedSeqColumn = $this->columnRegistry[$referencedName] ?? null;
//
//                if ($referencedSeqColumn) {
//                    $elementNodeIds[] = $referencedSeqColumn['sequencer_id'];
//                }
//            } else {
//                // Create DATA node
//                $dataNodeId = $this->createDataNode($node, $patternId);
//                if ($dataNodeId) {
//                    $elementNodeIds[] = $dataNodeId;
//                }
//            }
//        }
//
//        if (empty($elementNodeIds)) {
//            return;
//        }
//
//        if (count($elementNodeIds) === 1) {
//            // Single element: link directly to target head OR
//            $this->insertEdge($patternId, $elementNodeIds[0], $targetHeadOrId, [
//                'sequence' => 0,
//                'label' => 'mwe_single',
//            ]);
//        } else {
//            // Multiple elements: Build AND binary tree
//            $rootAndNodeId = $this->buildMWEBinaryTree($elementNodeIds, $patternId);
//
//            // Link root AND node to target construction's head OR
//            $this->insertEdge($patternId, $rootAndNodeId, $targetHeadOrId, [
//                'sequence' => 0,
//                'label' => 'mwe_composition',
//            ]);
//        }
//
//        $this->stats['mwe_patterns']++;
    }

    /**
     * Connect sequencer pattern: exactly three elements (left, head, right)
     *
     * With the new compiled graph structure, sequencer constructions have
     * 2 INTERMEDIATE nodes that explicitly separate the three elements:
     * START → left_elements → INTERMEDIATE1 → head_elements → INTERMEDIATE2 → right_elements → END
     *
     * This simplifies the logic significantly as we can use INTERMEDIATE nodes as boundaries.
     */
    private function connectSequencerPattern(array $structure, array $nodes, $construction, array $seqColumn): void
    {
        $patternId = $construction->idConstruction;
        $edges = json_decode($construction->compiledPattern, true)['edges'] ?? [];

        // Extract the three element groups using INTERMEDIATE nodes as boundaries
        $elementGroups = $this->extractSequencerElements($nodes, $edges);

        if (!$elementGroups) {
            // No INTERMEDIATE nodes found, fallback to old logic
            if ($structure['type'] !== 'sequence') {
                $this->connectPhrasalPattern($structure, $nodes, $construction, $seqColumn);

                return;
            }

            $elements = $structure['elements'];

            if (count($elements) < 3) {
                $this->connectMWEPattern($structure, $nodes, $construction, $seqColumn);

                return;
            }

            // Old logic: first → left, second → head, third → right
            $this->connectElementToOr($elements[0], $nodes, $seqColumn['left_or_id'], $patternId, 0);
            $this->connectElementToOr($elements[1], $nodes, $seqColumn['head_or_id'], $patternId, 0);
            $this->connectElementToOr($elements[2], $nodes, $seqColumn['right_or_id'], $patternId, 0);

            return;
        }

        // Connect left elements to left OR
        $leftSequence = 0;
        foreach ($elementGroups['left'] as $nodeId) {
            $this->connectElementToOr($nodeId, $nodes, $seqColumn['left_or_id'], $patternId, $leftSequence++);
        }

        // Connect head elements to head OR
        $headSequence = 0;
        foreach ($elementGroups['head'] as $nodeId) {
            $this->connectElementToOr($nodeId, $nodes, $seqColumn['head_or_id'], $patternId, $headSequence++);
        }

        // Connect right elements to right OR
        $rightSequence = 0;
        foreach ($elementGroups['right'] as $nodeId) {
            $this->connectElementToOr($nodeId, $nodes, $seqColumn['right_or_id'], $patternId, $rightSequence++);
        }
    }

    /**
     * Extract sequencer elements using INTERMEDIATE nodes as boundaries
     *
     * Returns array with 'left', 'head', 'right' element groups, or null if not a valid sequencer pattern.
     */
    private function extractSequencerElements(array $nodes, array $edges): ?array
    {
        // Find START, END, and INTERMEDIATE nodes
        $startId = null;
        $endId = null;
        $intermediateIds = [];

        foreach ($nodes as $nodeId => $node) {
            if ($node['type'] === 'START') {
                $startId = $nodeId;
            } elseif ($node['type'] === 'END') {
                $endId = $nodeId;
            } elseif ($node['type'] === 'INTERMEDIATE') {
                $intermediateIds[] = $nodeId;
            }
        }

        // Must have exactly 2 INTERMEDIATE nodes for sequencer pattern
        if (count($intermediateIds) !== 2) {
            return null;
        }

        if (!$startId || !$endId) {
            return null;
        }

        // Determine which INTERMEDIATE is first and which is second by traversal order
        // INTERMEDIATE1 should be reachable from START before INTERMEDIATE2
        $intermediate1 = $this->findFirstIntermediate($startId, $intermediateIds, $edges);
        $intermediate2 = null;

        foreach ($intermediateIds as $intId) {
            if ($intId !== $intermediate1) {
                $intermediate2 = $intId;
                break;
            }
        }

        if (!$intermediate1 || !$intermediate2) {
            return null;
        }

        // Extract elements in each section
        return [
            'left' => $this->extractElementsBetween($startId, $intermediate1, $edges, $nodes),
            'head' => $this->extractElementsBetween($intermediate1, $intermediate2, $edges, $nodes),
            'right' => $this->extractElementsBetween($intermediate2, $endId, $edges, $nodes),
        ];
    }

    /**
     * Find the first INTERMEDIATE node reachable from START
     */
    private function findFirstIntermediate(string $startId, array $intermediateIds, array $edges): ?string
    {
        $queue = [$startId];
        $visited = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            // Check if current is an INTERMEDIATE node
            if (in_array($current, $intermediateIds)) {
                return $current;
            }

            // Add neighbors to queue
            foreach ($edges as $edge) {
                if ($edge['from'] === $current) {
                    $queue[] = $edge['to'];
                }
            }
        }

        return null;
    }

    /**
     * Extract elements between two nodes (excluding start/end boundaries and INTERMEDIATE nodes)
     */
    private function extractElementsBetween(string $fromId, string $toId, array $edges, array $nodes): array
    {
        $elements = [];
        $visited = [];
        $queue = [];

        // Find all nodes directly reachable from fromId
        foreach ($edges as $edge) {
            if ($edge['from'] === $fromId) {
                $queue[] = $edge['to'];
            }
        }

        while (!empty($queue)) {
            $current = array_shift($queue);

            if ($current === $toId) {
                continue;
            }

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $node = $nodes[$current] ?? null;
            if (!$node) {
                continue;
            }

            // Skip START, END, INTERMEDIATE nodes
            if (in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE'])) {
                // But continue traversal through INTERMEDIATE if not the target
                foreach ($edges as $edge) {
                    if ($edge['from'] === $current) {
                        $queue[] = $edge['to'];
                    }
                }

                continue;
            }

            // This is a real element
            $elements[] = $current;

            // Continue traversal
            foreach ($edges as $edge) {
                if ($edge['from'] === $current) {
                    $queue[] = $edge['to'];
                }
            }
        }

        return $elements;
    }

    /**
     * Build AND binary tree for MWE elements
     *
     * Creates a left-associative binary tree: ((A AND B) AND C) AND D
     * Returns the root AND node ID
     */
    private function buildMWEBinaryTree(string $constructionName, array $elementNodeIds, int $patternId): int
    {
        if (count($elementNodeIds) < 2) {
            // Should not happen, but return first element if it does
            return $elementNodeIds[0];
        }

        // Start with first element
        $current = $elementNodeIds[0];

        // Build left-associative binary tree
        for ($i = 1; $i < count($elementNodeIds); $i++) {
            $isRoot = ($i === count($elementNodeIds) - 1);

            // Create AND node
            $andSpec = [
                'type' => 'AND',
                'position' => $isRoot ? 'root' : 'internal',
                'layer' => 'L5',
                'construction_name' => $constructionName,
            ];
            $andNodeId = $this->insertNode($andSpec, $patternId, 'mwe_and_' . $i);

            $leftOrSpec = [
                'type' => 'OR',
                'sequence' => $i,
                'layer' => 'L23',
                'position' => 'left',
                'construction_name' => $constructionName,
            ];
            $leftOrId = $this->insertNode($leftOrSpec, $patternId, 'L23_OR_left');

            // Left OR → AND
            $this->insertEdge($patternId, $leftOrId, $andNodeId, [
                'sequence' => $i,
                'label' => 'left',
            ]);

            // Left operand → Left OR
            $this->insertEdge($patternId, $current, $leftOrId, [
                'sequence' => 0,
                'label' => 'left',
            ]);

            $rightOrSpec = [
                'type' => 'OR',
                'sequence' => $i,
                'layer' => 'L23',
                'position' => 'right',
                'construction_name' => $constructionName,
            ];
            $rightOrId = $this->insertNode($rightOrSpec, $patternId, 'L23_OR_right');

            // Right OR → AND
            $this->insertEdge($patternId, $rightOrId, $andNodeId, [
                'sequence' => $i,
                'label' => 'right',
            ]);

            // Right operand → Right OR
            $this->insertEdge($patternId, $elementNodeIds[$i], $rightOrId, [
                'sequence' => $i,
                'label' => 'right',
            ]);

            // Current becomes this AND node for next iteration
            $current = $andNodeId;
        }

        // Return root AND node
        return $current;
    }

    /**
     * Connect a single pattern element to an OR node
     *
     * Element can be:
     * - CONSTRUCTION_REF: Link to referenced construction's SEQUENCER
     * - LITERAL/SLOT/etc: Create DATA node and link to OR
     */
    private function connectElementToOr(string $nodeId, array $nodes, int $orNodeId, int $patternId, int $sequence): void
    {
        $node = $nodes[$nodeId] ?? null;
        if (!$node) {
            return;
        }

        // Skip START, END, INTERMEDIATE
        if (in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE'])) {
            return;
        }

        if ($this->isConstructionReference($node)) {
            // Link to referenced construction's SEQUENCER
            $referencedName = $this->getReferencedConstructionName($node);
            $referencedSeqColumn = $this->columnRegistry[$referencedName] ?? null;

            if ($referencedSeqColumn) {
                $referencedSequencerId = $referencedSeqColumn['sequencer_id'];

                // SEQUENCER → OR edge
                $this->insertEdge($patternId, $referencedSequencerId, $orNodeId, [
                    'sequence' => $sequence,
                    'label' => 'construction_ref',
                ]);
            }
        } else {
            // Create DATA node and link to OR
            $dataNodeId = $this->createDataNode($node, $patternId);
            if (isset($node['pos']) && ($node['pos'] == 'NULL')) {
                return;
            }

            if ($dataNodeId) {
                // DATA → OR edge
                $this->insertEdge($patternId, $dataNodeId, $orNodeId, [
                    'sequence' => $sequence,
                    'label' => 'data',
                ]);
            }
        }
    }

    /**
     * Create DATA node from pattern element
     */
    private function createDataNode(array $node, int $patternId): ?int
    {
        // Only create DATA nodes for matching criteria
        if (!in_array($node['type'] ?? '', ['LITERAL', 'SLOT', 'CE_SLOT', 'COMBINED_SLOT', 'WILDCARD'])) {
            return null;
        }

        $dataSpec = $this->createDataNodeSpec($node);

        // Check for deduplication
        $specHash = hash('sha256', json_encode($dataSpec));

        if (isset($this->dataNodeRegistry[$specHash])) {
            // Reuse existing DATA node
            return $this->dataNodeRegistry[$specHash];
        }

        // Create new DATA node
        $nodeId = $this->insertNode($dataSpec, $patternId, 'data_' . $node['type']);

        // Register for deduplication
        $this->dataNodeRegistry[$specHash] = $nodeId;

        return $nodeId;
    }

    /**
     * Create DATA node specification from pattern element
     */
    private function createDataNodeSpec(array $node): array
    {
        $spec = ['type' => 'DATA'];

        switch ($node['type']) {
            case 'LITERAL':
                $spec['dataType'] = 'literal';
                $spec['value'] = $node['value'] ?? '';
                break;

            case 'SLOT':
                $spec['dataType'] = 'slot';
                $spec['pos'] = $node['pos'] ?? '';
                $spec['constraint'] = $node['constraint'] ?? null;
                break;

            case 'CE_SLOT':
                $spec['dataType'] = 'ce_slot';
                $spec['ce_label'] = $node['ce_label'] ?? '';
                $spec['ce_tier'] = $node['ce_tier'] ?? '';
                break;

            case 'COMBINED_SLOT':
                $spec['dataType'] = 'combined_slot';
                $spec['pos'] = $node['pos'] ?? '';
                $spec['ce_label'] = $node['ce_label'] ?? '';
                $spec['ce_tier'] = $node['ce_tier'] ?? '';
                $spec['constraint'] = $node['constraint'] ?? null;
                break;

            case 'WILDCARD':
                $spec['dataType'] = 'wildcard';
                break;

            default:
                $spec['dataType'] = 'other';
                $spec['original_type'] = $node['type'];
                break;
        }

        return $spec;
    }

    /**
     * Check if node is a construction reference
     */
    private function isConstructionReference(array $node): bool
    {
        return in_array($node['type'] ?? '', ['CONSTRUCTION_REF', 'CONSTRUCTION']);
    }

    /**
     * Get referenced construction name
     */
    private function getReferencedConstructionName(array $node): string
    {
        return $node['construction_name'] ?? '';
    }

    /**
     * Insert node into database
     */
    public function insertNode(array $nodeSpec, string $patternId, string $localId): int
    {
        if ($this->dryRun) {
            $nodeId = ++$this->nodeCounter;
            $this->incrementNodeTypeStats($nodeSpec['type']);

            return $nodeId;
        }

        $type = $nodeSpec['type'];

        // Include pattern_id and local_id in hash for uniqueness
        $hashData = array_merge($nodeSpec, [
            'pattern_id' => $patternId,
            'local_id' => $localId,
        ]);

        $nodeData = [
            'type' => $type,
            'specification' => json_encode($nodeSpec),
            'spec_hash' => hash('sha256', json_encode($hashData)),
            'value' => $nodeSpec['value'] ?? null,
            'pos' => $nodeSpec['pos'] ?? null,
            'ce_label' => $nodeSpec['ce_label'] ?? null,
            'ce_tier' => $nodeSpec['ce_tier'] ?? null,
            'construction_name' => $nodeSpec['construction_name'] ?? null,
            'usage_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $nodeId = DB::table('parser_pattern_node')->insertGetId($nodeData);

        $this->incrementNodeTypeStats($type);

        return $nodeId;
    }

    /**
     * Increment node type statistics
     */
    private function incrementNodeTypeStats(string $type): void
    {
        switch ($type) {
            case 'DATA':
                $this->stats['data_nodes']++;
                break;
            case 'OR':
                $this->stats['or_nodes']++;
                break;
            case 'AND':
                $this->stats['and_nodes']++;
                break;
            case 'SEQUENCER':
                $this->stats['sequencer_nodes']++;
                break;
        }
    }

    /**
     * Insert edge into database
     */
    public function insertEdge(string $patternId, int $fromId, int $toId, array $edgeData): void
    {
        if ($this->dryRun) {
            $this->stats['edges_created']++;

            return;
        }

        $properties = [
            'label' => $edgeData['label'] ?? null,
        ];

        // Add optional properties
        if (isset($edgeData['position'])) {
            $properties['position'] = $edgeData['position'];
        }

        if (isset($edgeData['optional'])) {
            $properties['optional'] = $edgeData['optional'];
        }

        DB::table('parser_pattern_edge')->insert([
            'pattern_id' => $patternId,
            'from_node_id' => $fromId,
            'to_node_id' => $toId,
            'properties' => json_encode($properties),
            'sequence' => $edgeData['sequence'] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->stats['edges_created']++;
    }

    /**
     * Get build statistics
     */
    public function getStatistics(): array
    {
        return $this->stats;
    }
}

<?php

namespace App\Services\CLN_RNT;

use App\Database\Criteria;
use Illuminate\Support\Facades\DB;

/**
 * RNT (Relational Network Theory) Graph Builder
 */
class RNTGraphBuilder
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
    private array $constructionRegistry = [];

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

        foreach ($constructions as $construction) {
            $this->createRootNode($construction);
        }

        // PASS 2: Process patterns and connect elements to OR nodes
        foreach ($constructions as $construction) {
            $this->connectPatternToColumn($construction);
        }

        $this->stats['constructions_processed'] = count($constructions);
    }

    public function createRootNode($construction): void
    {
        $pattern = json_decode($construction->compiledPattern, true);
        $patternId = $construction->idConstruction;
        $constructionName = $construction->name;
        if ($construction->constructionType == 'phrasal') {
            $orSpec = [
                'type' => 'OR',
                'construction_name' => $construction->name,
                'layer' => 'L23',
                'position' => 'head',
                'construction_type' => 'phrasal'
            ];
            $orId = $this->insertNode($orSpec, $patternId, 'L23');

            $this->constructionRegistry[$constructionName] = [
                'or_id' => $orId,
            ];
        } else if ($construction->constructionType == 'som') {
            $orSpec = [
                'type' => 'SOM',
                'construction_name' => $construction->name,
                'construction_type' => 'som'
            ];
            $orId = $this->insertNode($orSpec, $patternId, 'L23');
            $this->constructionRegistry[$constructionName] = [
                'som_id' => $orId,
            ];
        } else if ($construction->constructionType == 'vip') {
            $orSpec = [
                'type' => 'VIP',
                'construction_name' => $construction->name,
                'construction_type' => 'vip'
            ];
            $orId = $this->insertNode($orSpec, $patternId, 'L23');
            $this->constructionRegistry[$constructionName] = [
                'vip_id' => $orId,
            ];
        } else {
            // Create AND node
            $andSpec = [
                'type' => 'AND',
                'position' => 'root',
                'layer' => 'L5',
                'value' => 2,
                'construction_name' => $constructionName,
                'construction_type' => $construction->constructionType,
            ];
            $andId = $this->insertNode($andSpec, $patternId, 'cxn_and_0');
            $this->constructionRegistry[$constructionName] = [
                'and_id' => $andId,
            ];
        }

        $this->stats['seq_columns_created']++;
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
        $nodes = $pattern['nodes'] ?? [];
        $edges = $pattern['edges'] ?? [];

        // Analyze pattern structure
        $structure = $this->analyzePatternStructure($nodes, $edges);

        // Connect based on construction type
        $constructionType = $construction->constructionType ?? 'phrasal';

        if ($constructionType === 'mwe') {
            $this->connectMWEPattern($structure, $nodes, $construction);
        } elseif ($constructionType === 'sequencer') {
            $this->connectSequencerPattern($structure, $nodes, $construction);
        } elseif ($constructionType === 'som') {
            $this->connectSOMPattern($structure, $nodes, $construction);
        } elseif ($constructionType === 'vip') {
            $this->connectVIPPattern($structure, $nodes, $construction);
        } elseif ($constructionType === 'phrasal') {
            $this->connectPhrasalPattern($structure, $nodes, $construction);
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
        //$pathsFromStart = array_filter($edges, fn($e) => $e['from'] === $startId);
        $pathsFromStart = $this->findAllPaths($nodes, $edges);

        if (count($pathsFromStart) > 1) {
            // Multiple alternatives
            return [
                'type' => 'alternative',
                'alternatives' => $pathsFromStart //$this->extractAlternatives($startId, $endId, $edges, $nodes),
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
    private function connectPhrasalPattern(array $structure, array $nodes, $construction): void
    {
        $patternId = $construction->idConstruction;
        echo $construction->name . "\n";
        $orId = $this->constructionRegistry[$construction->name]['or_id'];

        if ($structure['type'] === 'alternative') {
            // Connect each alternative to head OR
            $sequence = 0;
            foreach ($structure['alternatives'] as $alternative) {
                foreach ($alternative as $nodeId) {
                    $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
                }
            }
        } elseif ($structure['type'] === 'sequence') {
            // Connect all elements to head OR
            $sequence = 0;
            foreach ($structure['elements'] as $nodeId) {
                $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
            }
        }
    }

    /**
     * Connect SOM pattern: all alternatives go to head SOM
     */
    private function connectSOMPattern(array $structure, array $nodes, $construction): void
    {
        $patternId = $construction->idConstruction;
        echo $construction->name . "\n";
        $orId = $this->constructionRegistry[$construction->name]['som_id'];

        if ($structure['type'] === 'alternative') {
            // Connect each alternative to head SOM
            $sequence = 0;
            foreach ($structure['alternatives'] as $alternative) {
                foreach ($alternative as $nodeId) {
                    $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
                }
            }
        } elseif ($structure['type'] === 'sequence') {
            // Connect all elements to head SOM
            $sequence = 0;
            foreach ($structure['elements'] as $nodeId) {
                $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
            }
        }
        $targetConstructionName = mb_strtoupper($construction->phrasalCE ?? '', 'UTF-8');
        $targetId = $this->constructionRegistry[$targetConstructionName]['or_id'];
        $this->insertEdge($patternId, $orId, $targetId, [
            'sequence' => 0,
            'label' => 'som',
        ]);

    }

    /**
     * Connect VIP pattern: all alternatives go to head VIP
     */
    private function connectVIPPattern(array $structure, array $nodes, $construction): void
    {
        $patternId = $construction->idConstruction;
        echo $construction->name . "\n";
        $orId = $this->constructionRegistry[$construction->name]['vip_id'];

        if ($structure['type'] === 'alternative') {
            // Connect each alternative to head SOM
            $sequence = 0;
            foreach ($structure['alternatives'] as $alternative) {
                foreach ($alternative as $nodeId) {
                    $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
                }
            }
        } elseif ($structure['type'] === 'sequence') {
            // Connect all elements to head SOM
            $sequence = 0;
            foreach ($structure['elements'] as $nodeId) {
                $this->connectElementToOr($nodeId, $nodes, $orId, $patternId, $sequence++);
            }
        }
        $targetConstructionName = mb_strtoupper($construction->phrasalCE ?? '', 'UTF-8');
        $targetId = $this->constructionRegistry[$targetConstructionName]['som_id'];
        $this->insertEdge($patternId, $orId, $targetId, [
            'sequence' => 0,
            'label' => 'vip',
        ]);

    }

    /**
     * Connect MWE pattern: build AND binary tree and link to target construction
     *
     * MWE constructions:
     * - Build binary tree using AND nodes for the MWE elements
     * - Link the root AND node to the head OR node of target construction (from phrasalCE)
     */
    private function connectMWEPattern(array $structure, array $nodes, $construction): void
    {
        if ($structure['type'] !== 'sequence') {
            // Fallback to phrasal if not a sequence
            $this->connectPhrasalPattern($structure, $nodes, $construction);

            return;
        }

        $elements = $structure['elements'];
        $patternId = $construction->idConstruction;

        if (count($elements) === 0) {
            return;
        }

        $constructionName = $construction->name;
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


        // Get target construction from phrasalCE field (uppercased)
        $targetConstructionName = mb_strtoupper($construction->phrasalCE ?? '', 'UTF-8');
        $targetId = $this->constructionRegistry[$targetConstructionName]['or_id'];
        //$targetSeqColumn = $this->columnRegistry[$targetConstructionName] ?? null;

        //if (!$targetSeqColumn) {
        // No valid target construction, fallback to phrasal pattern
        //    $this->connectPhrasalPattern($structure, $nodes, $construction);

        //    return;
        //}

//        $targetHeadOrId = $targetSeqColumn['head_or_id'];
//        $rootAndNodeId = $seqColumn['and_root_id'];
        $this->insertEdge($patternId, $rootAndNodeId, $targetId, [
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
    private function connectSequencerPattern(array $structure, array $nodes, $construction): void
    {
        $constructionName = $construction->name;
        $patternId = $construction->idConstruction;

        print_r($construction->name . ' -- ' . $structure['type'] . PHP_EOL);
        print_r($structure);
//        if ($structure['type'] !== 'sequence') {
//            // Fallback to phrasal if not a sequence
//            $this->connectPhrasalPattern($structure, $nodes, $construction);
//
//            return;
//        }

        if ($structure['type'] === 'alternative') {
            // Se uma sequencer é alternative é porque tem um elemento opcional
            $elements = [];
            foreach ($structure['alternatives'] as $alternative) {
                foreach ($alternative as $nodeId) {
                    $n = $elements[$nodeId] ?? 0;
                    $elements[$nodeId] = $n + 1;
                }
            }
            // Create DATA/CONSTRUCTION_REF nodes for each element
            $elementNodeIds = [];
            $optional = [];
            foreach ($elements as $clnNodeId => $count) {
                $node = $nodes[$clnNodeId] ?? null;
                if (!$node) {
                    continue;
                }
                $referencedName = $this->getReferencedConstructionName($node);
                $elementNodeId = $this->constructionRegistry[$referencedName]['or_id'];
                $optional[$elementNodeId] = ($count < 2);
                $elementNodeIds[] = $elementNodeId;
            }

            $this->buildSequencerBinaryTree($constructionName, $elementNodeIds, $patternId, $optional);

        } elseif ($structure['type'] === 'sequence') {
            $elements = $structure['elements'];
            if (count($elements) === 0) {
                return;
            }

            $elements = $structure['elements'] ?? [];

            // Create DATA/CONSTRUCTION_REF nodes for each element
            $elementNodeIds = [];
            $optional = [];
            foreach ($elements as $clnNodeId) {
                $node = $nodes[$clnNodeId] ?? null;
                if (!$node) {
                    continue;
                }
                $referencedName = $this->getReferencedConstructionName($node);
                $elementNodeId = $this->constructionRegistry[$referencedName]['or_id'];
                $optional[$elementNodeId] = false;
                $elementNodeIds[] = $elementNodeId;
            }

            if (count($elementNodeIds) === 1) {
                // Create AND node
                $andSpec = [
                    'type' => 'AND',
                    'position' => 'root',
                    'construction_name' => $constructionName,
                ];
                $rootAndNodeId = $this->insertNode($andSpec, $patternId, 'seq_and_0');
            } else {
                // Multiple elements: Build AND binary tree
                $this->buildSequencerBinaryTree($constructionName, $elementNodeIds, $patternId, $optional);
            }
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

            if ($isRoot) {
                $andNodeId = $this->constructionRegistry[$constructionName]['and_id'];
            } else {
                // Create AND node
                $andSpec = [
                    'type' => 'AND',
                    'position' => $isRoot ? 'root' : 'internal',
                    'layer' => 'L5',
                    'construction_name' => $constructionName,
                    'construction_type' => 'mwe'
                ];
                $andNodeId = $this->insertNode($andSpec, $patternId, 'mwe_and_' . $i);
            }

//            $leftOrSpec = [
//                'type' => 'OR',
//                'sequence' => $i,
//                'layer' => 'L23',
//                'position' => 'left',
//                'construction_name' => '',
//            ];
//            $leftOrId = $this->insertNode($leftOrSpec, $patternId, 'L23_OR_left');
//
//            // Left OR → AND
//            $this->insertEdge($patternId, $leftOrId, $andNodeId, [
//                'sequence' => $i,
//                'label' => 'left',
//            ]);
//
//            // Left operand → Left OR
//            $this->insertEdge($patternId, $current, $leftOrId, [
//                'sequence' => 0,
//                'label' => 'left',
//            ]);

            // Left operand → Left OR
            $this->insertEdge($patternId, $current, $andNodeId, [
                'sequence' => 0,
                'label' => 'left',
            ]);


//            $rightOrSpec = [
//                'type' => 'OR',
//                'sequence' => $i,
//                'layer' => 'L23',
//                'position' => 'right',
//                'construction_name' => '',
//            ];
//            $rightOrId = $this->insertNode($rightOrSpec, $patternId, 'L23_OR_right');
//
//            // Right OR → AND
//            $this->insertEdge($patternId, $rightOrId, $andNodeId, [
//                'sequence' => $i,
//                'label' => 'right',
//            ]);
//
//            // Right operand → Right OR
//            $this->insertEdge($patternId, $elementNodeIds[$i], $rightOrId, [
//                'sequence' => $i,
//                'label' => 'right',
//            ]);

            // Right operand → Right OR
            $this->insertEdge($patternId, $elementNodeIds[$i], $andNodeId, [
                'sequence' => $i,
                'label' => 'right',
            ]);

            // Current becomes this AND node for next iteration
            $current = $andNodeId;
        }

        // Return root AND node
        return $current;
    }

    private function buildSequencerBinaryTree(string $constructionName, array $elementNodeIds, int $patternId, array $optional): void
    {
        $andNodeId = $this->constructionRegistry[$constructionName]['and_id'];
        $leftId = $elementNodeIds[0];
        $rightId = $elementNodeIds[1];

        $threshold = (!$optional[$leftId] && !$optional[$rightId]) ? 2 : 1;
        Criteria::table("parser_pattern_node")
            ->where("id", $andNodeId)
            ->update(["value" => $threshold]);

        $this->insertEdge($patternId, $leftId, $andNodeId, [
            'sequence' => 0,
            'label' => 'left',
            'optional' => $optional[$leftId]
        ]);

        $this->insertEdge($patternId, $rightId, $andNodeId, [
            'sequence' => 1,
            'label' => 'right',
            'optional' => $optional[$rightId]
        ]);
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
            $sourceId = $this->constructionRegistry[$referencedName]['or_id'] ?? null;
            if (is_null($sourceId)) {
                $sourceId = $this->constructionRegistry[$referencedName]['and_id'] ?? null;
            }
            //$referencedSeqColumn = $this->columnRegistry[$referencedName] ?? null;

            //if ($referencedSeqColumn) {
            //    $referencedSequencerId = $referencedSeqColumn['sequencer_id'];

            // SEQUENCER → OR edge
            $this->insertEdge($patternId, $sourceId, $orNodeId, [
                'sequence' => $sequence,
                'label' => 'construction_ref',
            ]);
            //}
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

        if ($node['type'] === 'LITERAL') {
            $orSpec = [
                'type' => 'OR',
                'layer' => 'L23',
                'position' => 'head',
                'construction_name' => $dataSpec['value'],
            ];
            $orId = $this->insertNode($orSpec, $patternId, 'or');
            $this->insertEdge($patternId, $nodeId, $orId, [
                'sequence' => 0,
                'label' => 'head',
            ]);
            $this->dataNodeRegistry[$specHash] = $orId;

        } else {
            $this->dataNodeRegistry[$specHash] = $nodeId;
        }

        return $this->dataNodeRegistry[$specHash];
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

    public function findAllPaths(array $nodes, array $edges)
    {
        // Build adjacency list for efficient traversal
        $adjacencyList = [];
        foreach ($edges as $edge) {
            if (!isset($adjacencyList[$edge['from']])) {
                $adjacencyList[$edge['from']] = [];
            }
            $adjacencyList[$edge['from']][] = $edge['to'];
        }

        // Find START and END nodes, and identify INTERMEDIATE nodes
        $startNode = null;
        $endNode = null;
        $intermediateNodes = [];

        foreach ($nodes as $nodeId => $nodeData) {
            if ($nodeData['type'] === 'START') {
                $startNode = $nodeId;
            }
            if ($nodeData['type'] === 'END') {
                $endNode = $nodeId;
            }
            if ($nodeData['type'] === 'INTERMEDIATE') {
                $intermediateNodes[] = $nodeId;
            }
        }

        // Validate that both START and END exist
        if ($startNode === null || $endNode === null) {
            return [];
        }

        // Collect all paths
        $allPaths = [];

        // DFS helper function

        // Start DFS from START node (START is marked as visited but not in path)
        $this->dfs($startNode, $endNode, $adjacencyList, [], [$startNode], $allPaths, $intermediateNodes);

// Deduplicate paths (remove identical paths)
        $uniquePaths = [];
        $pathSignatures = [];

        foreach ($allPaths as $path) {
            // Create a signature for the path
            $signature = implode('|', $path);

            // Only add if this signature hasn't been seen before
            if (!in_array($signature, $pathSignatures)) {
                $pathSignatures[] = $signature;
                $uniquePaths[] = $path;
            }
        }

        return $uniquePaths;
    }

    public function dfs($current, $end, &$adjacencyList, $currentPath, $visited, &$allPaths, $intermediateNodes)
    {
        // If we reached the END node, save the current path
        if ($current === $end) {
            $allPaths[] = $currentPath;
            return;
        }

        // If no outgoing edges from current node, return
        if (!isset($adjacencyList[$current])) {
            return;
        }

        // Explore all neighbors
        foreach ($adjacencyList[$current] as $neighbor) {
            // Avoid cycles by checking if neighbor is already visited
            if (!in_array($neighbor, $visited)) {
                // Create new path and visited arrays for this branch
                $newPath = $currentPath;
                $newVisited = $visited;

                // Add neighbor to path only if it's not the END node
                // and not an INTERMEDIATE node
                if ($neighbor !== $end && !in_array($neighbor, $intermediateNodes)) {
                    $newPath[] = $neighbor;
                }

                // Mark neighbor as visited (even INTERMEDIATE nodes)
                $newVisited[] = $neighbor;

                // Recursively explore from neighbor
                $this->dfs($neighbor, $end, $adjacencyList, $newPath, $newVisited, $allPaths, $intermediateNodes);
            }
        }
    }
}

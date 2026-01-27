<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Column;
use Illuminate\Support\Facades\DB;

/**
 * RNT (Relational Network Type) Pattern Graph Querier
 *
 * Queries the new three-node-type pattern graph structure:
 * - DATA nodes (SLOT/LITERAL): Matching criteria for L1 nodes
 * - OR nodes: Represent constructions with alternatives
 * - AND nodes: Binary composition (left + right operands)
 *
 * Implements aggressive caching for performance optimization.
 */
class RNTGraphQuerier
{
    // Cache keys
    private const CACHE_PREFIX = 'rnt_graph';

    private const CACHE_TTL = 86400; // 24 hours

    // Cache storage
    private array $dataNodesByPos = [];

    private array $dataNodesByWord = [];

    private array $orNodesByConstruction = [];

    private array $andNodeExpectations = [];

    private array $compositionTargets = [];

    private bool $cacheWarmed = false;

    public function __construct()
    {
        // Auto-warmup cache on construction
        $this->warmupCache();
    }

    /**
     * Find DATA nodes matching L1 node features
     *
     * Matches on:
     * - LITERAL: Exact word match
     * - SLOT: POS tag match with optional constraints
     *
     * @param  Column  $l1Node  L1 node to match
     * @return array Array of DATA node objects with node_id, type, pattern_id
     */
    public function findMatchingDataNodes(Column $l1Node): array
    {
        $matches = [];

        // Extract L1 features (InputParserService uses 'value' for word)
        $word = $l1Node->features['value'] ?? null;
        $lemma = $l1Node->features['lemma'] ?? null;
        $pos = $l1Node->features['pos'] ?? null;

        if (! $word || ! $pos) {
            return [];
        }

        // Strategy 1: LITERAL word match
        $literalMatches = $this->findLiteralDataNodes($word);
        $matches = array_merge($matches, $literalMatches);

        // Strategy 2: SLOT POS match
        $slotMatches = $this->findSlotDataNodes($pos);
        $matches = array_merge($matches, $slotMatches);

        return $matches;
    }

    /**
     * Find LITERAL DATA nodes matching exact word
     *
     * @param  string  $word  Word to match
     * @return array Array of DATA node objects
     */
    private function findLiteralDataNodes(string $word): array
    {
        // Check cache first
        if (isset($this->dataNodesByWord[$word])) {
            return $this->dataNodesByWord[$word];
        }

        // Query database using JSON path for word matching
        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->whereRaw("JSON_EXTRACT(specification, '$.specification.value') = ?", [$word])
            ->select('id as node_id', 'specification')
            ->get()
            ->map(function ($node) {
                $spec = json_decode($node->specification, true);

                return [
                    'node_id' => $node->node_id,
                    'type' => 'LITERAL',
                    'value' => $spec['specification']['value'] ?? null,
                    'specification' => $spec,
                ];
            })
            ->toArray();

        // Cache result
        $this->dataNodesByWord[$word] = $nodes;

        return $nodes;
    }

    /**
     * Find SLOT DATA nodes matching POS tag
     *
     * @param  string  $pos  POS tag to match
     * @return array Array of DATA node objects
     */
    private function findSlotDataNodes(string $pos): array
    {
        // Check cache first
        if (isset($this->dataNodesByPos[$pos])) {
            return $this->dataNodesByPos[$pos];
        }

        // Query database using JSON path for POS matching
        $nodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->whereRaw("JSON_EXTRACT(specification, '$.specification.pos') = ?", [$pos])
            ->select('id as node_id', 'specification')
            ->get()
            ->map(function ($node) {
                $spec = json_decode($node->specification, true);

                return [
                    'node_id' => $node->node_id,
                    'type' => 'SLOT',
                    'pos' => $spec['specification']['pos'] ?? null,
                    'specification' => $spec,
                ];
            })
            ->toArray();

        // Cache result
        $this->dataNodesByPos[$pos] = $nodes;

        return $nodes;
    }

    /**
     * Get OR nodes reachable from DATA nodes
     *
     * Follows edges with labels: 'alternative' or 'single'
     *
     * @param  array  $dataNodeIds  Array of DATA node IDs
     * @return array Array of OR node objects with or_node_id, construction_name, pattern_id, label
     */
    public function getOrNodesFromData(array $dataNodeIds): array
    {
        if (empty($dataNodeIds)) {
            return [];
        }

        $orNodes = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.to_node_id', '=', 'n.id')
            ->whereIn('e.from_node_id', $dataNodeIds)
            ->where('n.type', 'OR')
            ->select(
                'n.id as or_node_id',
                'n.construction_name',
                'e.pattern_id',
                'e.properties',
                'n.specification'
            )
            ->get()
            ->filter(function ($node) {
                // Filter by label in properties JSON
                $props = json_decode($node->properties, true);
                $label = $props['label'] ?? null;

                return in_array($label, ['alternative', 'single']);
            })
            ->map(function ($node) {
                $props = json_decode($node->properties, true);

                return [
                    'or_node_id' => $node->or_node_id,
                    'construction_name' => $node->construction_name,
                    'pattern_id' => $node->pattern_id,
                    'label' => $props['label'] ?? null,
                    'specification' => json_decode($node->specification, true),
                ];
            })
            ->values()
            ->toArray();

        return $orNodes;
    }

    /**
     * Get OR nodes reachable from OR or SEQUENCER sources
     *
     * Follows OR→OR and SEQUENCER→OR edges with 'alternative' or 'single' labels
     *
     * @param  int  $sourceNodeId  Source node ID (OR or SEQUENCER)
     * @param  string  $sourceType  'OR' or 'SEQUENCER'
     * @return array Array of OR nodes
     */
    public function getOrNodesFromSource(int $sourceNodeId, string $sourceType = 'OR'): array
    {
        $cacheKey = "or_from_{$sourceType}_{$sourceNodeId}";

        if (isset($this->compositionTargets[$cacheKey])) {
            return $this->compositionTargets[$cacheKey];
        }

        $orNodes = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.to_node_id', '=', 'n.id')
            ->where('e.from_node_id', $sourceNodeId)
            ->where('n.type', 'OR')
            ->whereIn(DB::raw("JSON_EXTRACT(e.properties, '$.label')"), ['"alternative"', '"single"'])
            ->select(
                'n.id as or_node_id',
                'n.specification',
                'e.pattern_id',
                'e.properties'
            )
            ->get()
            ->map(function ($node) {
                $spec = json_decode($node->specification, true);
                $props = json_decode($node->properties, true);

                return [
                    'or_node_id' => $node->or_node_id,
                    'construction_name' => $spec['construction_name'] ?? null,
                    'pattern_id' => $node->pattern_id,
                    'label' => $props['label'] ?? null,
                    'specification' => $spec,
                ];
            })
            ->toArray();

        $this->compositionTargets[$cacheKey] = $orNodes;

        return $orNodes;
    }

    /**
     * Get alternative OR nodes reachable from this OR node
     *
     * Follows OR→OR edges with 'alternative' or 'single' labels
     *
     * @param  int  $orNodeId  Source OR node ID
     * @return array Array of alternative OR nodes
     */
    public function getAlternativeOrNodes(int $orNodeId): array
    {
        $cacheKey = "alt_or_{$orNodeId}";

        if (isset($this->orNodesByConstruction[$cacheKey])) {
            return $this->orNodesByConstruction[$cacheKey];
        }

        $orNodes = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.to_node_id', '=', 'n.id')
            ->where('e.from_node_id', $orNodeId)
            ->where('n.type', 'OR')
            ->select(
                'n.id as or_node_id',
                'n.construction_name',
                'e.pattern_id',
                'e.properties',
                'n.specification'
            )
            ->get()
            ->filter(function ($node) {
                // Filter by label in properties JSON
                $props = json_decode($node->properties, true);
                $label = $props['label'] ?? null;

                return in_array($label, ['alternative', 'single']);
            })
            ->map(function ($node) {
                $props = json_decode($node->properties, true);

                return [
                    'or_node_id' => $node->or_node_id,
                    'construction_name' => $node->construction_name,
                    'pattern_id' => $node->pattern_id,
                    'label' => $props['label'] ?? null,
                    'specification' => json_decode($node->specification, true),
                ];
            })
            ->values()
            ->toArray();

        $this->orNodesByConstruction[$cacheKey] = $orNodes;

        return $orNodes;
    }

    /**
     * Get AND nodes expecting this OR node as left operand
     *
     * @param  int  $orNodeId  OR node ID
     * @param  int|null  $patternId  Optional pattern ID filter
     * @return array Array of AND node expectations
     */
    public function getAndNodesExpectingLeft(int $orNodeId, ?int $patternId = null): array
    {
        $cacheKey = "left_{$orNodeId}_".($patternId ?? 'all');

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        $query = DB::table('parser_pattern_edge as e_left')
            ->join('parser_pattern_node as n', 'e_left.to_node_id', '=', 'n.id')
            ->leftJoin('parser_pattern_edge as e_right', function ($join) {
                $join->on('e_right.to_node_id', '=', 'n.id')
                    ->on('e_right.pattern_id', '=', 'e_left.pattern_id')
                    ->whereRaw("JSON_EXTRACT(e_right.properties, '$.label') = 'right'");
            })
            ->where('e_left.from_node_id', $orNodeId)
            ->where('n.type', 'AND')
            ->whereRaw("JSON_EXTRACT(e_left.properties, '$.label') = 'left'");

        if ($patternId !== null) {
            $query->where('e_left.pattern_id', $patternId);
        }

        $andNodes = $query
            ->select(
                'n.id as and_node_id',
                'e_left.pattern_id',
                'n.specification as and_spec',
                'e_right.from_node_id as expected_right_or_node'
            )
            ->get()
            ->map(function ($node) {
                return [
                    'and_node_id' => $node->and_node_id,
                    'pattern_id' => $node->pattern_id,
                    'expected_right_or_node' => $node->expected_right_or_node,
                    'and_specification' => json_decode($node->and_spec, true),
                ];
            })
            ->toArray();

        $this->andNodeExpectations[$cacheKey] = $andNodes;

        return $andNodes;
    }

    /**
     * Get AND nodes expecting this OR node as right operand
     *
     * @param  int  $orNodeId  OR node ID
     * @param  int|null  $patternId  Optional pattern ID filter
     * @return array Array of AND node expectations
     */
    public function getAndNodesExpectingRight(int $orNodeId, ?int $patternId = null): array
    {
        $cacheKey = "right_{$orNodeId}_".($patternId ?? 'all');

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        $query = DB::table('parser_pattern_edge as e_right')
            ->join('parser_pattern_node as n', 'e_right.to_node_id', '=', 'n.id')
            ->leftJoin('parser_pattern_edge as e_left', function ($join) {
                $join->on('e_left.to_node_id', '=', 'n.id')
                    ->on('e_left.pattern_id', '=', 'e_right.pattern_id')
                    ->whereRaw("JSON_EXTRACT(e_left.properties, '$.label') = 'left'");
            })
            ->where('e_right.from_node_id', $orNodeId)
            ->where('n.type', 'AND')
            ->whereRaw("JSON_EXTRACT(e_right.properties, '$.label') = 'right'");

        if ($patternId !== null) {
            $query->where('e_right.pattern_id', $patternId);
        }

        $andNodes = $query
            ->select(
                'n.id as and_node_id',
                'e_right.pattern_id',
                'n.specification as and_spec',
                'e_left.from_node_id as expected_left_or_node'
            )
            ->get()
            ->map(function ($node) {
                return [
                    'and_node_id' => $node->and_node_id,
                    'pattern_id' => $node->pattern_id,
                    'expected_left_or_node' => $node->expected_left_or_node,
                    'and_specification' => json_decode($node->and_spec, true),
                ];
            })
            ->toArray();

        $this->andNodeExpectations[$cacheKey] = $andNodes;

        return $andNodes;
    }

    /**
     * Get OR node that an AND node composes to
     *
     * Follows 'composition' edge from AND to OR
     *
     * @param  int  $andNodeId  AND node ID
     * @param  int  $patternId  Pattern ID
     * @return array|null OR node object or null if not found
     */
    public function getCompositionTarget(int $andNodeId, int $patternId): ?array
    {
        $cacheKey = "{$andNodeId}_{$patternId}";

        if (isset($this->compositionTargets[$cacheKey])) {
            return $this->compositionTargets[$cacheKey];
        }

        $orNode = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.to_node_id', '=', 'n.id')
            ->where('e.from_node_id', $andNodeId)
            ->where('e.pattern_id', $patternId)
            ->where('n.type', 'OR')
            ->whereRaw("JSON_EXTRACT(e.properties, '$.label') = 'composition'")
            ->select(
                'n.id as or_node_id',
                'n.construction_name',
                'e.pattern_id',
                'n.specification'
            )
            ->first();

        if (! $orNode) {
            $this->compositionTargets[$cacheKey] = null;

            return null;
        }

        $result = [
            'or_node_id' => $orNode->or_node_id,
            'construction_name' => $orNode->construction_name,
            'pattern_id' => $orNode->pattern_id,
            'specification' => json_decode($orNode->specification, true),
        ];

        $this->compositionTargets[$cacheKey] = $result;

        return $result;
    }

    /**
     * Get construction name from OR node
     *
     * @param  int  $orNodeId  OR node ID
     * @return string|null Construction name or null
     */
    public function getConstructionName(int $orNodeId): ?string
    {
        $orNode = DB::table('parser_pattern_node')
            ->where('id', $orNodeId)
            ->where('type', 'OR')
            ->select('construction_name')
            ->first();

        return $orNode->construction_name ?? null;
    }

    /**
     * Check if OR node is a single-element construction
     *
     * Single-element: Has DATA→OR edges with label 'single'
     *
     * @param  int  $orNodeId  OR node ID
     * @param  int  $patternId  Pattern ID
     * @return bool True if single-element construction
     */
    public function isSingleElement(int $orNodeId, int $patternId): bool
    {
        $count = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.from_node_id', '=', 'n.id')
            ->where('e.to_node_id', $orNodeId)
            ->where('e.pattern_id', $patternId)
            ->where('n.type', 'DATA')
            ->whereRaw("JSON_EXTRACT(e.properties, '$.label') = 'single'")
            ->count();

        return $count > 0;
    }

    /**
     * Warmup cache with frequently used patterns
     *
     * Pre-loads common POS tags and constructions into memory cache
     *
     * @param  array  $patternIds  Optional specific pattern IDs to warmup
     */
    public function warmupCache(array $patternIds = []): void
    {
        if ($this->cacheWarmed) {
            return;
        }

        // Common Portuguese POS tags to pre-cache
        $commonPosTags = ['NOUN', 'VERB', 'ADJ', 'DET', 'NUM', 'PROPN', 'AUX'];

        foreach ($commonPosTags as $pos) {
            $this->findSlotDataNodes($pos);
        }

        // Common Portuguese words for MWEs
        $commonWords = ['de', 'que', 'onde', 'roupa', 'jogo', 'ferro', 'banho', 'cama'];

        foreach ($commonWords as $word) {
            $this->findLiteralDataNodes($word);
        }

        $this->cacheWarmed = true;
    }

    /**
     * Get AND nodes expecting a specific DATA node as left operand
     *
     * @param  int  $dataNodeId  DATA node ID
     * @param  int|null  $patternId  Optional pattern ID filter
     * @return array Array of AND node expectations
     */
    public function getAndNodesExpectingDataLeft(int $dataNodeId, ?int $patternId = null): array
    {
        $cacheKey = "data_left_{$dataNodeId}_".($patternId ?? 'all');

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        $query = DB::table('parser_pattern_edge as e_left')
            ->join('parser_pattern_node as n', 'e_left.to_node_id', '=', 'n.id')
            ->leftJoin('parser_pattern_edge as e_right', function ($join) {
                $join->on('e_right.to_node_id', '=', 'n.id')
                    ->on('e_right.pattern_id', '=', 'e_left.pattern_id')
                    ->whereRaw("JSON_EXTRACT(e_right.properties, '$.label') = 'right'");
            })
            ->leftJoin('parser_pattern_node as right_node', 'e_right.from_node_id', '=', 'right_node.id')
            ->where('e_left.from_node_id', $dataNodeId)
            ->where('n.type', 'AND')
            ->whereRaw("JSON_EXTRACT(e_left.properties, '$.label') = 'left'");

        if ($patternId !== null) {
            $query->where('e_left.pattern_id', $patternId);
        }

        $andNodes = $query
            ->select(
                'n.id as and_node_id',
                'e_left.pattern_id',
                'n.specification as and_spec',
                'e_right.from_node_id as expected_right_node_id',
                'right_node.type as expected_right_type',
                'right_node.specification as expected_right_spec'
            )
            ->get()
            ->map(function ($node) {
                return [
                    'and_node_id' => $node->and_node_id,
                    'pattern_id' => $node->pattern_id,
                    'expected_right_node_id' => $node->expected_right_node_id,
                    'expected_right_type' => $node->expected_right_type,
                    'expected_right_spec' => json_decode($node->expected_right_spec, true),
                    'and_specification' => json_decode($node->and_spec, true),
                ];
            })
            ->toArray();

        $this->andNodeExpectations[$cacheKey] = $andNodes;

        return $andNodes;
    }

    /**
     * Get AND nodes expecting a specific DATA node as right operand
     *
     * @param  int  $dataNodeId  DATA node ID
     * @param  int|null  $patternId  Optional pattern ID filter
     * @return array Array of AND node expectations
     */
    public function getAndNodesExpectingDataRight(int $dataNodeId, ?int $patternId = null): array
    {
        $cacheKey = "data_right_{$dataNodeId}_".($patternId ?? 'all');

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        $query = DB::table('parser_pattern_edge as e_right')
            ->join('parser_pattern_node as n', 'e_right.to_node_id', '=', 'n.id')
            ->leftJoin('parser_pattern_edge as e_left', function ($join) {
                $join->on('e_left.to_node_id', '=', 'n.id')
                    ->on('e_left.pattern_id', '=', 'e_right.pattern_id')
                    ->whereRaw("JSON_EXTRACT(e_left.properties, '$.label') = 'left'");
            })
            ->leftJoin('parser_pattern_node as left_node', 'e_left.from_node_id', '=', 'left_node.id')
            ->where('e_right.from_node_id', $dataNodeId)
            ->where('n.type', 'AND')
            ->whereRaw("JSON_EXTRACT(e_right.properties, '$.label') = 'right'");

        if ($patternId !== null) {
            $query->where('e_right.pattern_id', $patternId);
        }

        $andNodes = $query
            ->select(
                'n.id as and_node_id',
                'e_right.pattern_id',
                'n.specification as and_spec',
                'e_left.from_node_id as expected_left_node_id',
                'left_node.type as expected_left_type',
                'left_node.specification as expected_left_spec'
            )
            ->get()
            ->map(function ($node) {
                return [
                    'and_node_id' => $node->and_node_id,
                    'pattern_id' => $node->pattern_id,
                    'expected_left_node_id' => $node->expected_left_node_id,
                    'expected_left_type' => $node->expected_left_type,
                    'expected_left_spec' => json_decode($node->expected_left_spec, true),
                    'and_specification' => json_decode($node->and_spec, true),
                ];
            })
            ->toArray();

        $this->andNodeExpectations[$cacheKey] = $andNodes;

        return $andNodes;
    }

    /**
     * Get AND nodes expecting an intermediate AND node as left operand
     *
     * @param  int  $andNodeId  AND node ID (to be used as left operand)
     * @param  int  $patternId  Pattern ID
     * @return array Array of AND node expectations
     */
    public function getAndNodesExpectingAndLeft(int $andNodeId, int $patternId): array
    {
        $cacheKey = "and_left_{$andNodeId}_{$patternId}";

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        $andNodes = DB::table('parser_pattern_edge as e_left')
            ->join('parser_pattern_node as n', 'e_left.to_node_id', '=', 'n.id')
            ->leftJoin('parser_pattern_edge as e_right', function ($join) use ($patternId) {
                $join->on('e_right.to_node_id', '=', 'n.id')
                    ->where('e_right.pattern_id', $patternId)
                    ->whereRaw("JSON_EXTRACT(e_right.properties, '$.label') = 'right'");
            })
            ->leftJoin('parser_pattern_node as right_node', 'e_right.from_node_id', '=', 'right_node.id')
            ->where('e_left.from_node_id', $andNodeId)
            ->where('e_left.pattern_id', $patternId)
            ->where('n.type', 'AND')
            ->whereRaw("JSON_EXTRACT(e_left.properties, '$.label') = 'left'")
            ->select(
                'n.id as and_node_id',
                'e_left.pattern_id',
                'n.specification as and_spec',
                'e_right.from_node_id as expected_right_node_id',
                'right_node.type as expected_right_type',
                'right_node.specification as expected_right_spec'
            )
            ->get()
            ->map(function ($node) {
                return [
                    'and_node_id' => $node->and_node_id,
                    'pattern_id' => $node->pattern_id,
                    'expected_right_node_id' => $node->expected_right_node_id,
                    'expected_right_type' => $node->expected_right_type,
                    'expected_right_spec' => json_decode($node->expected_right_spec, true),
                    'and_specification' => json_decode($node->and_spec, true),
                ];
            })
            ->toArray();

        $this->andNodeExpectations[$cacheKey] = $andNodes;

        return $andNodes;
    }

    /**
     * Get SEQUENCER nodes reachable from OR nodes (or SEQUENCER→SEQUENCER)
     *
     * Follows edges with label: 'sequence_element'
     * Returns SEQUENCER nodes that expect this OR/SEQUENCER as input
     *
     * @param  int  $sourceNodeId  Source OR or SEQUENCER node ID
     * @param  string  $sourceType  Source node type ('OR' or 'SEQUENCER')
     * @return array Array of SEQUENCER node objects with sequencer_node_id, construction_name, pattern_id, properties
     */
    public function getSequencerNodesFromSource(int $sourceNodeId, string $sourceType = 'OR'): array
    {
        $cacheKey = "seq_from_{$sourceType}_{$sourceNodeId}";

        if (isset($this->compositionTargets[$cacheKey])) {
            return $this->compositionTargets[$cacheKey];
        }

        $sequencerNodes = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.to_node_id', '=', 'n.id')
            ->where('e.from_node_id', $sourceNodeId)
            ->where('n.type', 'SEQUENCER')
            ->whereRaw("JSON_EXTRACT(e.properties, '$.label') = 'sequence_element'")
            ->select(
                'n.id as sequencer_node_id',
                'n.specification',
                'e.pattern_id',
                'e.properties',
                'e.sequence'
            )
            ->get()
            ->map(function ($node) {
                $spec = json_decode($node->specification, true);
                $props = json_decode($node->properties, true);

                return [
                    'sequencer_node_id' => $node->sequencer_node_id,
                    'construction_name' => $spec['construction_name'] ?? null,
                    'pattern_id' => $node->pattern_id,
                    'sequence' => $node->sequence,
                    'optional' => $props['optional'] ?? false,
                    'position' => $props['position'] ?? null,
                    'label' => $props['label'] ?? null,
                    'specification' => $spec,
                ];
            })
            ->toArray();

        $this->compositionTargets[$cacheKey] = $sequencerNodes;

        return $sequencerNodes;
    }

    /**
     * Get all inputs (mandatory and optional) for a SEQUENCER node
     *
     * Returns information about which OR/SEQUENCER nodes feed into this SEQUENCER
     *
     * @param  int  $sequencerNodeId  SEQUENCER node ID
     * @param  int  $patternId  Pattern ID
     * @return array Array with 'mandatory' and 'optional' input lists
     */
    public function getSequencerInputs(int $sequencerNodeId, int $patternId): array
    {
        $cacheKey = "seq_inputs_{$sequencerNodeId}_{$patternId}";

        if (isset($this->andNodeExpectations[$cacheKey])) {
            return $this->andNodeExpectations[$cacheKey];
        }

        // Get all edges pointing TO this SEQUENCER node for this pattern
        $inputs = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as n', 'e.from_node_id', '=', 'n.id')
            ->where('e.to_node_id', $sequencerNodeId)
            ->where('e.pattern_id', $patternId)
            ->whereIn('n.type', ['OR', 'SEQUENCER'])
            ->whereRaw("JSON_EXTRACT(e.properties, '$.label') = 'sequence_element'")
            ->select(
                'n.id as source_node_id',
                'n.type as source_type',
                'n.specification',
                'e.properties',
                'e.sequence'
            )
            ->get()
            ->map(function ($input) {
                $spec = json_decode($input->specification, true);
                $props = json_decode($input->properties, true);

                return [
                    'source_node_id' => $input->source_node_id,
                    'source_type' => $input->source_type,
                    'construction_name' => $spec['construction_name'] ?? null,
                    'optional' => $props['optional'] ?? false,
                    'position' => $props['position'] ?? null,
                    'sequence' => $input->sequence,
                    'specification' => $spec,
                ];
            })
            ->toArray();

        // Separate mandatory and optional inputs
        $mandatory = array_filter($inputs, fn ($i) => ! $i['optional']);
        $optional = array_filter($inputs, fn ($i) => $i['optional']);

        $result = [
            'mandatory' => array_values($mandatory),
            'optional' => array_values($optional),
            'all' => $inputs,
        ];

        $this->andNodeExpectations[$cacheKey] = $result;

        return $result;
    }

    /**
     * Get construction name from SEQUENCER node
     *
     * @param  int  $sequencerNodeId  SEQUENCER node ID
     * @return string|null Construction name or null
     */
    public function getSequencerConstructionName(int $sequencerNodeId): ?string
    {
        $sequencerNode = DB::table('parser_pattern_node')
            ->where('id', $sequencerNodeId)
            ->where('type', 'SEQUENCER')
            ->select('specification')
            ->first();

        if (! $sequencerNode) {
            return null;
        }

        $spec = json_decode($sequencerNode->specification, true);

        return $spec['construction_name'] ?? null;
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->dataNodesByPos = [];
        $this->dataNodesByWord = [];
        $this->orNodesByConstruction = [];
        $this->andNodeExpectations = [];
        $this->compositionTargets = [];
        $this->cacheWarmed = false;
    }

    /**
     * Get cache statistics for debugging
     *
     * @return array Cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'data_nodes_by_pos' => count($this->dataNodesByPos),
            'data_nodes_by_word' => count($this->dataNodesByWord),
            'or_nodes_by_construction' => count($this->orNodesByConstruction),
            'and_node_expectations' => count($this->andNodeExpectations),
            'composition_targets' => count($this->compositionTargets),
            'cache_warmed' => $this->cacheWarmed,
        ];
    }
}

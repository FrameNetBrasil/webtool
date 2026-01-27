<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Node;
use Illuminate\Support\Facades\DB;

/**
 * Graph Pattern Matcher (Shared Graph Optimization)
 *
 * Matches L23 nodes against the shared pattern graph built by GraphBuilder.
 * Instead of checking each construction's individual pattern graph (O(N×M)),
 * this uses the deduplicated shared graph to match multiple patterns in parallel (O(1)).
 *
 * Key optimizations:
 * - Shared nodes: All constructions sharing {NOUN} are checked simultaneously
 * - Single START node: All patterns begin from the same entry point
 * - Edge-based pattern tracking: Each edge knows which pattern(s) it belongs to
 * - In-memory caching: Graph loaded once and reused across all columns
 *
 * Performance: For N constructions and M tokens, reduces from O(N×M) to near O(1)
 * by deduplicating shared pattern nodes (e.g., all {NOUN} slots).
 */
class GraphPatternMatcher
{
    /**
     * In-memory cache of the shared pattern graph
     *
     * Format:
     * [
     *   'nodes' => ['node_id' => node_data],
     *   'edges' => ['edge_id' => edge_data],
     *   'start_node_id' => int,
     *   'end_node_id' => int
     * ]
     */
    private static ?array $graphCache = null;

    /**
     * Pattern matcher for node matching logic
     */
    private PatternMatcher $matcher;

    /**
     * Create new graph pattern matcher
     */
    public function __construct()
    {
        $this->matcher = new PatternMatcher;
    }

    /**
     * Find all patterns that match the given Node (entry point)
     *
     * This is the main public method that L5Layer calls to match patterns.
     *
     * Returns array of matching pattern IDs and their matched starting nodes.
     * L5Layer can then create partial constructions for each matching pattern.
     *
     * @param  Node $node  Node to match against patterns
     * @return array Array of matches: [['pattern_id' => int, 'node_id' => int, 'node' => array], ...]
     */
    public function findMatchingPatternsFromStart(
        Node $node,
    ): array
    {
//        if (empty($l23Nodes)) {
//            return [];
//        }

        // Load graph if not cached
        if (self::$graphCache === null) {
            $this->loadGraph();
        }

        $graph = self::$graphCache;
        if ($graph === null) {
            return []; // Graph not available
        }

        $startNodeId = $graph['start_node_id'] ?? null;
        if ($startNodeId === null) {
            return []; // No START node
        }

        $matches = [];

        // Find all edges from START node
        $startEdges = $this->getEdgesFromNode($startNodeId);

        foreach ($startEdges as $edge) {
            $toNodeId = $edge['to_node_id'];
            $graphNode = $graph['nodes'][$toNodeId] ?? null;

            if ($graphNode === null) {
                continue; // Invalid edge
            }

            // Skip END node (shouldn't happen from START, but defensive)
            if (($graphNode['type'] ?? '') === 'END') {
                continue;
            }

            // Check if L23 nodes match this pattern node
            if ($this->matcher->matchesNode($node, $this->convertNodeToGraphFormat($graphNode))) {
                // MATCH! Record this pattern + starting node
                $matches[] = [
                    'pattern_id' => $edge['pattern_id'],
                    'node_id' => $toNodeId,
                    'node' => $graphNode,
                    'edge' => $edge,
                ];
            }
        }

        return $matches;
    }

    /**
     * Find next possible nodes for a given pattern traversal
     *
     * Used for pattern continuation (when partial constructions advance).
     * Given a current node and pattern ID, returns all possible next nodes
     * for that specific pattern.
     *
     * @param  int  $currentNodeId  Current node ID in graph
     * @param  int  $patternId  Pattern ID to follow
     * @return array Array of possible next nodes with metadata
     */
    public function findNextNodes(int $currentNodeId, int $patternId): array
    {
        if (self::$graphCache === null) {
            $this->loadGraph();
        }

        $graph = self::$graphCache;
        if ($graph === null) {
            return [];
        }

        $nextNodes = [];
        $edges = $this->getEdgesFromNode($currentNodeId);

        foreach ($edges as $edge) {
            // Only follow edges for this specific pattern
            if ($edge['pattern_id'] !== $patternId) {
                continue;
            }

            $toNodeId = $edge['to_node_id'];
            $node = $graph['nodes'][$toNodeId] ?? null;

            if ($node === null) {
                continue;
            }

            // Check if this is END node (pattern complete)
            if (($node['type'] ?? '') === 'END') {
                $nextNodes[] = [
                    'node_id' => $toNodeId,
                    'node' => $node,
                    'edge' => $edge,
                    'is_end' => true,
                ];

                continue;
            }

            $nextNodes[] = [
                'node_id' => $toNodeId,
                'node' => $node,
                'edge' => $edge,
                'is_end' => false,
            ];
        }

        return $nextNodes;
    }

    /**
     * Check if a pattern is complete (current node connects to END)
     *
     * @param  int  $currentNodeId  Current node ID in graph
     * @param  int  $patternId  Pattern ID to check
     * @return bool True if END is reachable for this pattern
     */
    public function isPatternComplete(int $currentNodeId, int $patternId): bool
    {
        $nextNodes = $this->findNextNodes($currentNodeId, $patternId);

        foreach ($nextNodes as $node) {
            if ($node['is_end'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get construction metadata for a pattern ID
     *
     * Returns construction name, ID, and other metadata from parser_construction_v4.
     *
     * @param  int  $patternId  Pattern ID (idConstruction)
     * @return array|null Construction metadata or null
     */
    public function getConstructionMetadata(int $patternId): ?array
    {
        $construction = DB::table('parser_construction_v4')
            ->where('idConstruction', $patternId)
            ->first();

        if (! $construction) {
            return null;
        }

        return [
            'id' => $construction->idConstruction,
            'name' => $construction->name,
            'pattern' => $construction->pattern,
            'compiledPattern' => json_decode($construction->compiledPattern, true),
        ];
    }

    /**
     * Load shared pattern graph from database into memory
     *
     * Loads all nodes and edges from parser_pattern_node and parser_pattern_edge.
     * Caches result in static property for reuse across all columns.
     */
    private function loadGraph(): void
    {
        // Load all nodes
        $nodesData = DB::table('parser_pattern_node')
            ->orderBy('id')
            ->get();

        $nodes = [];
        $startNodeId = null;
        $endNodeId = null;

        foreach ($nodesData as $nodeRow) {
            $nodeId = $nodeRow->id;
            $specification = json_decode($nodeRow->specification, true);

            $nodes[$nodeId] = array_merge($specification, [
                'id' => $nodeId,
                'spec_hash' => $nodeRow->spec_hash,
                'usage_count' => $nodeRow->usage_count,
            ]);

            // Track START and END nodes
            if ($nodeRow->type === 'START') {
                $startNodeId = $nodeId;
            } elseif ($nodeRow->type === 'END') {
                $endNodeId = $nodeId;
            }
        }

        // Load all edges
        $edgesData = DB::table('parser_pattern_edge')
            ->orderBy('pattern_id')
            ->orderBy('sequence')
            ->get();

        $edges = [];
        foreach ($edgesData as $edgeRow) {
            $properties = json_decode($edgeRow->properties, true) ?? [];

            $edges[] = array_merge($properties, [
                'id' => $edgeRow->id,
                'pattern_id' => $edgeRow->pattern_id,
                'from_node_id' => $edgeRow->from_node_id,
                'to_node_id' => $edgeRow->to_node_id,
                'sequence' => $edgeRow->sequence,
            ]);
        }

        // Cache graph
        self::$graphCache = [
            'nodes' => $nodes,
            'edges' => $edges,
            'start_node_id' => $startNodeId,
            'end_node_id' => $endNodeId,
        ];
    }

    /**
     * Get all edges originating from a specific node
     *
     * @param  int  $nodeId  Source node ID
     * @return array Array of edges from this node
     */
    private function getEdgesFromNode(int $nodeId): array
    {
        if (self::$graphCache === null) {
            return [];
        }

        $result = [];
        foreach (self::$graphCache['edges'] as $edge) {
            if ($edge['from_node_id'] === $nodeId) {
                $result[] = $edge;
            }
        }

        return $result;
    }

    /**
     * Convert database node format to PatternMatcher graph format
     *
     * Database stores: ['type' => 'SLOT', 'pos' => 'NOUN', ...]
     * PatternMatcher expects same format, so this is mostly pass-through
     * but ensures compatibility.
     *
     * @param  array  $node  Node from database
     * @return array Node in graph format
     */
    private function convertNodeToGraphFormat(array $node): array
    {
        // Database format already matches PatternMatcher expectations
        // Just ensure we have all required fields
        return [
            'type' => $node['type'] ?? 'UNKNOWN',
            'value' => $node['value'] ?? null,
            'pos' => $node['pos'] ?? null,
            'ce_label' => $node['ce_label'] ?? null,
            'ce_tier' => $node['ce_tier'] ?? null,
            'construction_id' => $node['construction_id'] ?? null,
            'construction_name' => $node['construction_name'] ?? null,
            'constraint' => $node['constraint'] ?? null,
        ];
    }

    /**
     * Clear the graph cache (useful for testing or graph rebuilds)
     */
    public static function clearCache(): void
    {
        self::$graphCache = null;
    }

    /**
     * Get statistics about the cached graph
     *
     * @return array|null Graph statistics or null if not loaded
     */
    public function getGraphStats(): ?array
    {
        if (self::$graphCache === null) {
            return null;
        }

        $nodeTypes = [];
        foreach (self::$graphCache['nodes'] as $node) {
            $type = $node['type'] ?? 'UNKNOWN';
            $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
        }

        return [
            'node_count' => count(self::$graphCache['nodes']),
            'edge_count' => count(self::$graphCache['edges']),
            'node_types' => $nodeTypes,
            'is_loaded' => true,
        ];
    }
}

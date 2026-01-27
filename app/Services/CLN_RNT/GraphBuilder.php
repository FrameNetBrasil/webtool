<?php

namespace App\Services\CLN_RNT;

use Illuminate\Support\Facades\DB;

/**
 * Parser Pattern Graph Builder
 *
 * Builds a shared, deduplicated graph structure from construction patterns.
 * Instead of each construction storing its own duplicate pattern graph,
 * all constructions share a single merged graph where:
 * - Common nodes (e.g., all {NOUN} slots) are deduplicated
 * - Edges are pattern-specific with metadata
 * - Single START and END nodes serve the entire graph
 *
 * This optimization reduces pattern matching complexity from O(N×M) to near O(1)
 * for N constructions and M tokens.
 */
class GraphBuilder
{
    private ?int $sharedStartNodeId = null;

    private ?int $sharedEndNodeId = null;

    private array $nodeCache = [];

    private array $stats = [
        'nodes_created' => 0,
        'nodes_reused' => 0,
        'edges_created' => 0,
        'patterns_processed' => 0,
        'node_types' => [],
    ];

    private bool $dryRun;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    /**
     * Build the complete pattern graph from all enabled constructions
     */
    public function buildGraph(): void
    {
        // Create shared START and END nodes (only once for entire graph)
        $this->sharedStartNodeId = $this->findOrCreateNode(['type' => 'START']);
        $this->sharedEndNodeId = $this->findOrCreateNode(['type' => 'END']);

        // Process all enabled constructions
        $constructions = DB::table('parser_construction_v4')
            ->whereNotNull('compiledPattern')
            ->where('enabled', 1)
            ->orderBy('idConstruction')
            ->get();

        foreach ($constructions as $construction) {
            $this->processConstruction($construction);
        }
    }

    /**
     * Process a single construction pattern
     */
    private function processConstruction($construction): void
    {
        $pattern = json_decode($construction->compiledPattern, true);

        if (! is_array($pattern)) {
            return; // Skip invalid patterns
        }

        $nodes = $pattern['nodes'] ?? [];
        $edges = $pattern['edges'] ?? [];

        // Map: local node ID (from compiledPattern) → global shared node ID
        $localToGlobal = [];

        // Process nodes: create or find shared nodes
        foreach ($nodes as $localId => $nodeSpec) {
            if ($nodeSpec['type'] === 'START') {
                $localToGlobal[$localId] = $this->sharedStartNodeId;
            } elseif ($nodeSpec['type'] === 'END') {
                $localToGlobal[$localId] = $this->sharedEndNodeId;
            } else {
                $localToGlobal[$localId] = $this->findOrCreateNode($nodeSpec);
            }
        }

        // Process edges: create pattern-specific edges with metadata
        // Note: We deduplicate edges because the original compiledPattern may contain duplicates
        $sequence = 0;
        $seenEdges = []; // Track unique edges: "fromId-toId-properties"

        foreach ($edges as $edge) {
            $fromGlobalId = $localToGlobal[$edge['from']] ?? null;
            $toGlobalId = $localToGlobal[$edge['to']] ?? null;

            if (! $fromGlobalId || ! $toGlobalId) {
                continue; // Skip edges with invalid node references
            }

            // Extract edge properties (everything except from/to)
            $properties = array_diff_key($edge, ['from' => 1, 'to' => 1]);
            $propertiesJson = ! empty($properties) ? json_encode($properties) : null;

            // Create unique key for deduplication
            $edgeKey = "{$fromGlobalId}-{$toGlobalId}-{$propertiesJson}";

            if (isset($seenEdges[$edgeKey])) {
                // Skip duplicate edge
                continue;
            }

            $seenEdges[$edgeKey] = true;

            if (! $this->dryRun) {
                DB::table('parser_pattern_edge')->insert([
                    'pattern_id' => $construction->idConstruction,
                    'from_node_id' => $fromGlobalId,
                    'to_node_id' => $toGlobalId,
                    'properties' => $propertiesJson,
                    'sequence' => $sequence,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->stats['edges_created']++;
            $sequence++;
        }

        $this->stats['patterns_processed']++;
    }

    /**
     * Find existing node or create new one (deduplication)
     *
     * Uses spec_hash for uniqueness - identical nodes are reused.
     */
    private function findOrCreateNode(array $nodeSpec): int
    {
        $hash = $this->generateNodeHash($nodeSpec);

        // Check in-memory cache first (for this build session)
        if (isset($this->nodeCache[$hash])) {
            if (! $this->dryRun) {
                DB::table('parser_pattern_node')
                    ->where('id', $this->nodeCache[$hash])
                    ->increment('usage_count');
            }
            $this->stats['nodes_reused']++;

            return $this->nodeCache[$hash];
        }

        // Check database for existing node
        if (! $this->dryRun) {
            $existing = DB::table('parser_pattern_node')
                ->where('spec_hash', $hash)
                ->first();

            if ($existing) {
                DB::table('parser_pattern_node')
                    ->where('id', $existing->id)
                    ->increment('usage_count');

                $this->nodeCache[$hash] = $existing->id;
                $this->stats['nodes_reused']++;

                return $existing->id;
            }
        }

        // Create new node
        $canonicalSpec = $this->buildCanonicalSpec($nodeSpec);

        $nodeData = [
            'type' => $nodeSpec['type'],
            'specification' => json_encode($canonicalSpec),
            'spec_hash' => $hash,
            'value' => $this->extractValue($nodeSpec),
            'pos' => $this->extractPos($nodeSpec),
            'ce_label' => $this->extractCELabel($nodeSpec),
            'ce_tier' => $this->extractCETier($nodeSpec),
            'construction_name' => $this->extractConstructionName($nodeSpec),
            'usage_count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (! $this->dryRun) {
            $nodeId = DB::table('parser_pattern_node')->insertGetId($nodeData);
        } else {
            // In dry-run mode, generate fake IDs
            $nodeId = count($this->nodeCache) + 1000;
        }

        $this->nodeCache[$hash] = $nodeId;
        $this->stats['nodes_created']++;

        // Track node type statistics
        $type = $nodeSpec['type'];
        $this->stats['node_types'][$type] = ($this->stats['node_types'][$type] ?? 0) + 1;

        return $nodeId;
    }

    /**
     * Generate unique hash for node specification
     *
     * Hash is based on canonical (normalized) representation to ensure
     * identical nodes produce the same hash.
     */
    public function generateNodeHash(array $nodeSpec): string
    {
        $canonicalSpec = $this->buildCanonicalSpec($nodeSpec);

        // Sort keys for deterministic hashing
        ksort($canonicalSpec);

        return hash('sha256', json_encode($canonicalSpec, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Build canonical specification for a node
     *
     * Normalizes node specification for consistent hashing:
     * - LITERAL values are lowercased
     * - SLOT POS tags are uppercased
     * - Field order is consistent
     */
    private function buildCanonicalSpec(array $nodeSpec): array
    {
        $type = $nodeSpec['type'];

        return match ($type) {
            'START' => ['type' => 'START'],
            'END' => ['type' => 'END'],

            'LITERAL' => [
                'type' => 'LITERAL',
                'value' => mb_strtolower(trim($nodeSpec['value'] ?? '', '"\''), 'UTF-8'),
            ],

            'SLOT' => [
                'type' => 'SLOT',
                'pos' => mb_strtoupper($nodeSpec['pos'] ?? '', 'UTF-8'),
                'constraint' => $nodeSpec['constraint'] ?? null,
            ],

            'CE_SLOT' => [
                'type' => 'CE_SLOT',
                'ce_label' => $nodeSpec['ce_label'] ?? '',
                'ce_tier' => $nodeSpec['ce_tier'] ?? '',
            ],

            'COMBINED_SLOT' => [
                'type' => 'COMBINED_SLOT',
                'pos' => mb_strtoupper($nodeSpec['pos'] ?? '', 'UTF-8'),
                'ce_label' => $nodeSpec['ce_label'] ?? '',
                'ce_tier' => $nodeSpec['ce_tier'] ?? '',
                'constraint' => $nodeSpec['constraint'] ?? null,
            ],

            'CONSTRUCTION_REF', 'CONSTRUCTION' => [
                'type' => 'CONSTRUCTION_REF',
                'construction_name' => mb_strtoupper($nodeSpec['construction_name'] ?? '', 'UTF-8'),
                'construction_id' => $nodeSpec['construction_id'] ?? null,
            ],

            'WILDCARD' => ['type' => 'WILDCARD'],

            'INTERMEDIATE', 'REP_CHECK' => [
                'type' => $type,
                'spec' => json_encode($nodeSpec['specification'] ?? []),
            ],

            default => [
                'type' => $type,
                'raw' => json_encode($nodeSpec),
            ],
        };
    }

    /**
     * Extract value field for LITERAL nodes
     */
    private function extractValue(array $nodeSpec): ?string
    {
        if ($nodeSpec['type'] === 'LITERAL') {
            return mb_substr(trim($nodeSpec['value'] ?? '', '"\''), 0, 255);
        }

        return null;
    }

    /**
     * Extract POS field for SLOT/COMBINED_SLOT nodes
     */
    private function extractPos(array $nodeSpec): ?string
    {
        if (in_array($nodeSpec['type'], ['SLOT', 'COMBINED_SLOT'])) {
            return mb_substr(mb_strtoupper($nodeSpec['pos'] ?? '', 'UTF-8'), 0, 50);
        }

        return null;
    }

    /**
     * Extract CE label for CE_SLOT/COMBINED_SLOT nodes
     */
    private function extractCELabel(array $nodeSpec): ?string
    {
        if (in_array($nodeSpec['type'], ['CE_SLOT', 'COMBINED_SLOT'])) {
            return mb_substr($nodeSpec['ce_label'] ?? '', 0, 100);
        }

        return null;
    }

    /**
     * Extract CE tier for CE_SLOT/COMBINED_SLOT nodes
     */
    private function extractCETier(array $nodeSpec): ?string
    {
        if (in_array($nodeSpec['type'], ['CE_SLOT', 'COMBINED_SLOT'])) {
            return mb_substr($nodeSpec['ce_tier'] ?? '', 0, 50);
        }

        return null;
    }

    /**
     * Extract construction name for CONSTRUCTION_REF nodes
     */
    private function extractConstructionName(array $nodeSpec): ?string
    {
        if (in_array($nodeSpec['type'], ['CONSTRUCTION_REF', 'CONSTRUCTION'])) {
            return mb_substr($nodeSpec['construction_name'] ?? '', 0, 255);
        }

        return null;
    }

    /**
     * Get statistics from the build process
     */
    public function getStatistics(): array
    {
        $totalNodes = $this->stats['nodes_created'] + $this->stats['nodes_reused'];
        $reusePercentage = $totalNodes > 0 ? ($this->stats['nodes_reused'] / $totalNodes) * 100 : 0;

        return array_merge($this->stats, [
            'total_nodes' => $totalNodes,
            'reuse_percentage' => round($reusePercentage, 1),
        ]);
    }

    /**
     * Clear node cache (useful for chunked processing)
     */
    public function clearCache(): void
    {
        $this->nodeCache = [];
    }
}

<?php

namespace App\Services\CLN_RNT;

use Illuminate\Support\Facades\DB;

/**
 * Pattern Graph Querier for CLN v3
 *
 * Uses existing database schema:
 * - parser_pattern_node: Stores pattern graph nodes (deduplicated)
 * - parser_pattern_edge: Stores edges with pattern_id (construction ID)
 *
 * Schema mapping:
 * - CLN v3 "node_type" → existing "type" field
 * - CLN v3 "element_value" → existing "value" field
 * - CLN v3 "pattern_label" → derived from pattern_id or stored in properties JSON
 * - CLN v3 "source_node_id" → existing "from_node_id"
 * - CLN v3 "target_node_id" → existing "to_node_id"
 */
class PatternGraphQuerier
{
    /**
     * Query patterns for a given element
     *
     * Finds patterns matching the element via:
     * 1. LITERAL match - exact word match (L1 nodes)
     * 2. SLOT match - POS-based match (L1 nodes)
     * 3. CONSTRUCTION_REF match - single-element construction match (L1 nodes)
     *
     * CONSTRUCTION_REF enables L1 nodes representing single-element constructions
     * (like "MOD-", "HEAD") to participate in L2 pair-wise composition.
     *
     * @param  array  $element  Element with 'type', 'value', 'pos', and 'construction_type' fields
     * @return array Array of matching edges with pattern information
     */
    public function queryPatternsForElement(array $element): array
    {
        $nodeType = $element['node_type'] ?? $element['type'] ?? null;
        $elementValue = $element['element_value'] ?? $element['value'] ?? null;
        $elementPOS = $element['pos'] ?? null;
        $constructionType = $element['construction_type'] ?? null;

        $results = [];

        // 1. Query for LITERAL matches (exact word match)
        $literalQuery = "
            SELECT e.pattern_id, e.to_node_id, n.id as from_node_id,
                   e.properties,
                   t.type as target_type, t.value as target_value,
                   t.specification as target_spec,
                   JSON_EXTRACT(t.specification, '$.ce_label') as ce_label,
                   'LITERAL' as match_type
            FROM parser_pattern_node n
            JOIN parser_pattern_edge e ON n.id = e.from_node_id
            JOIN parser_pattern_node t ON e.to_node_id = t.id
            WHERE n.type = 'LITERAL' AND n.value = ?
        ";

        $literalResults = DB::select($literalQuery, [$elementValue]);
        $results = array_merge($results, $literalResults);

        // 2. Query for SLOT matches (POS-based)
        if ($elementPOS !== null) {
            $slotQuery = "
                SELECT e.pattern_id, e.to_node_id, n.id as from_node_id,
                       e.properties,
                       t.type as target_type, t.value as target_value,
                       t.specification as target_spec,
                       JSON_EXTRACT(t.specification, '$.ce_label') as ce_label,
                       'SLOT' as match_type,
                       n.specification as slot_spec
                FROM parser_pattern_node n
                JOIN parser_pattern_edge e ON n.id = e.from_node_id
                JOIN parser_pattern_node t ON e.to_node_id = t.id
                WHERE n.type = 'SLOT'
                  AND JSON_EXTRACT(n.specification, '$.pos') = ?
            ";

            $slotResults = DB::select($slotQuery, [$elementPOS]);
            $results = array_merge($results, $slotResults);
        }

        // 3. Query for CONSTRUCTION_REF matches (construction type match)
        if ($constructionType !== null) {
            $cxnRefQuery = "
                SELECT e.pattern_id, e.to_node_id, n.id as from_node_id,
                       e.properties,
                       t.type as target_type, t.value as target_value,
                       t.specification as target_spec,
                       JSON_EXTRACT(t.specification, '$.ce_label') as ce_label,
                       'CONSTRUCTION_REF' as match_type,
                       n.specification as cxn_ref_spec
                FROM parser_pattern_node n
                JOIN parser_pattern_edge e ON n.id = e.from_node_id
                JOIN parser_pattern_node t ON e.to_node_id = t.id
                WHERE n.type = 'CONSTRUCTION_REF'
                  AND JSON_EXTRACT(n.specification, '$.construction_name') = ?
            ";

            $cxnRefResults = DB::select($cxnRefQuery, [$constructionType]);
            $results = array_merge($results, $cxnRefResults);
        }

        return $results;
    }

    /**
     * Query patterns starting from START node
     *
     * Used for finding single-element patterns like START → SLOT(DET) → END.
     *
     * @param  string  $nodeType  Type of the target node (LITERAL, SLOT, etc.)
     * @param  string|null  $value  Value for LITERAL matches
     * @param  string|null  $pos  POS for SLOT matches
     * @return array Array of matching patterns
     */
    public function queryPatternsFromStart(string $nodeType, ?string $value = null, ?string $pos = null): array
    {
        $startNode = $this->getStartNode();
        if (! $startNode) {
            return [];
        }

        $matches = [];

        if ($nodeType === 'LITERAL' && $value !== null) {
            $query = "
                SELECT e.pattern_id, t.id as target_node_id,
                       t.type as target_type, t.value as target_value,
                       t.specification as target_spec,
                       JSON_EXTRACT(t.specification, '$.ce_label') as ce_label,
                       e2.to_node_id as next_node_id,
                       t2.type as next_node_type
                FROM parser_pattern_edge e
                JOIN parser_pattern_node t ON e.to_node_id = t.id
                LEFT JOIN parser_pattern_edge e2 ON e2.from_node_id = t.id AND e2.pattern_id = e.pattern_id
                LEFT JOIN parser_pattern_node t2 ON e2.to_node_id = t2.id
                WHERE e.from_node_id = ?
                  AND t.type = 'LITERAL'
                  AND t.value = ?
            ";

            $results = DB::select($query, [$startNode->id, $value]);
            foreach ($results as $result) {
                $matches[] = [
                    'pattern_id' => $result->pattern_id,
                    'target_node_id' => $result->target_node_id,
                    'target_type' => $result->next_node_type ?? $result->target_type,
                    'ce_label' => $result->ce_label,
                ];
            }
        }

        if ($nodeType === 'SLOT' && $pos !== null) {
            $query = "
                SELECT e.pattern_id, t.id as target_node_id,
                       t.type as target_type, t.value as target_value,
                       t.specification as target_spec,
                       JSON_EXTRACT(t.specification, '$.ce_label') as ce_label,
                       e2.to_node_id as next_node_id,
                       t2.type as next_node_type
                FROM parser_pattern_edge e
                JOIN parser_pattern_node t ON e.to_node_id = t.id
                LEFT JOIN parser_pattern_edge e2 ON e2.from_node_id = t.id AND e2.pattern_id = e.pattern_id
                LEFT JOIN parser_pattern_node t2 ON e2.to_node_id = t2.id
                WHERE e.from_node_id = ?
                  AND t.type = 'SLOT'
                  AND JSON_EXTRACT(t.specification, '$.pos') = ?
            ";

            $results = DB::select($query, [$startNode->id, $pos]);
            foreach ($results as $result) {
                $matches[] = [
                    'pattern_id' => $result->pattern_id,
                    'target_node_id' => $result->target_node_id,
                    'target_type' => $result->next_node_type ?? $result->target_type,
                    'ce_label' => $result->ce_label,
                ];
            }
        }

        return $matches;
    }

    /**
     * Query next node in a specific pattern
     *
     * @param  int  $currentNodeId  Current node ID
     * @param  int  $patternId  Pattern ID (construction ID)
     * @return object|null Next node information
     */
    public function queryNextInPattern(int $currentNodeId, int $patternId): ?object
    {
        $query = '
            SELECT t.type as node_type, t.value as element_value,
                   e.to_node_id as target_node_id, t.id,
                   e.properties, e.sequence,
                   t.specification as target_spec
            FROM parser_pattern_edge e
            JOIN parser_pattern_node t ON e.to_node_id = t.id
            WHERE e.from_node_id = ? AND e.pattern_id = ?
            ORDER BY e.sequence
        ';

        $results = DB::select($query, [$currentNodeId, $patternId]);

        return $results[0] ?? null;
    }

    /**
     * Check if pattern is complete (reaches END node)
     *
     * @param  int  $currentNodeId  Current node ID
     * @param  int  $patternId  Pattern ID (construction ID)
     * @return bool True if END node is reachable
     */
    public function checkPatternComplete(int $currentNodeId, int $patternId): bool
    {
        $query = "
            SELECT COUNT(*) as count
            FROM parser_pattern_edge e
            JOIN parser_pattern_node t ON e.to_node_id = t.id
            WHERE e.from_node_id = ?
              AND e.pattern_id = ?
              AND t.type = 'END'
        ";

        $result = DB::selectOne($query, [$currentNodeId, $patternId]);

        return $result->count > 0;
    }

    /**
     * Get START node
     *
     * @return object|null START node data
     */
    public function getStartNode(): ?object
    {
        return DB::selectOne("SELECT * FROM parser_pattern_node WHERE type = 'START'");
    }

    /**
     * Get END node
     *
     * @return object|null END node data
     */
    public function getEndNode(): ?object
    {
        return DB::selectOne("SELECT * FROM parser_pattern_node WHERE type = 'END'");
    }

    /**
     * Find node by type and value
     *
     * @param  string  $nodeType  Node type (SLOT, LITERAL, CXN, etc.)
     * @param  string|null  $elementValue  Element value
     * @return object|null Node data
     */
    public function findNodeByTypeAndValue(string $nodeType, ?string $elementValue): ?object
    {
        if ($elementValue === null) {
            return DB::selectOne(
                'SELECT * FROM parser_pattern_node WHERE type = ? AND value IS NULL',
                [$nodeType]
            );
        }

        return DB::selectOne(
            'SELECT * FROM parser_pattern_node WHERE type = ? AND value = ?',
            [$nodeType, $elementValue]
        );
    }

    /**
     * Get pattern sequence for a specific pattern ID
     *
     * @param  int  $patternId  Pattern ID (construction ID)
     * @return array Array of edges in sequence
     */
    public function getPatternSequence(int $patternId): array
    {
        $query = '
            SELECT s.id as source_id, s.type as source_type, s.value as source_value,
                   t.id as target_id, t.type as target_type, t.value as target_value,
                   t.specification as target_spec,
                   JSON_EXTRACT(t.specification, "$.ce_label") as ce_label,
                   e.id as edge_id, e.sequence, e.properties
            FROM parser_pattern_edge e
            JOIN parser_pattern_node s ON e.from_node_id = s.id
            JOIN parser_pattern_node t ON e.to_node_id = t.id
            WHERE e.pattern_id = ?
            ORDER BY e.sequence
        ';

        return DB::select($query, [$patternId]);
    }

    /**
     * Get construction information by pattern ID
     *
     * @param  int  $patternId  Pattern ID (construction ID)
     * @return object|null Construction data
     */
    public function getConstructionByPatternId(int $patternId): ?object
    {
        return DB::selectOne(
            'SELECT * FROM parser_construction_v4 WHERE idConstruction = ?',
            [$patternId]
        );
    }

    /**
     * Get all patterns (constructions) that start with a given node
     *
     * @param  int  $nodeId  Starting node ID
     * @return array Array of pattern IDs
     */
    public function getPatternsStartingWith(int $nodeId): array
    {
        // Get START node
        $startNode = $this->getStartNode();
        if (! $startNode) {
            return [];
        }

        // Find all edges from START to the given node
        $query = "
            SELECT DISTINCT e.pattern_id,
                   c.name as pattern_name,
                   JSON_EXTRACT(n.specification, '$.ce_label') as ce_label
            FROM parser_pattern_edge e
            LEFT JOIN parser_construction_v4 c ON e.pattern_id = c.idConstruction
            JOIN parser_pattern_node n ON e.to_node_id = n.id
            WHERE e.from_node_id = ? AND e.to_node_id = ?
        ";

        return DB::select($query, [$startNode->id, $nodeId]);
    }
}

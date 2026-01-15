<?php

namespace App\Repositories\Parser;

use App\Database\Criteria;

class ParseEdge
{
    /**
     * Retrieve parse edge by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_link', 'idParserLink', $id);
    }

    /**
     * List all edges for a parse graph
     */
    public static function listByParseGraph(int $idParserGraph): array
    {
        return Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->all();
    }

    /**
     * Get edges from a specific source node
     */
    public static function listBySourceNode(int $idSourceNode): array
    {
        return Criteria::table('parser_link')
            ->where('idSourceNode', '=', $idSourceNode)
            ->all();
    }

    /**
     * Get edges to a specific target node
     */
    public static function listByTargetNode(int $idTargetNode): array
    {
        return Criteria::table('parser_link')
            ->where('idTargetNode', '=', $idTargetNode)
            ->all();
    }

    /**
     * Get edges by type
     */
    public static function listByType(int $idParserGraph, string $linkType): array
    {
        return Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->where('linkType', '=', $linkType)
            ->all();
    }

    /**
     * Check if edge exists between two nodes
     */
    public static function exists(int $idSourceNode, int $idTargetNode): bool
    {
        $result = Criteria::table('parser_link')
            ->where('idSourceNode', '=', $idSourceNode)
            ->where('idTargetNode', '=', $idTargetNode)
            ->first();

        return $result !== null;
    }

    /**
     * Get edge between two nodes
     */
    public static function getBetween(int $idSourceNode, int $idTargetNode): ?object
    {
        $result = Criteria::table('parser_link')
            ->where('idSourceNode', '=', $idSourceNode)
            ->where('idTargetNode', '=', $idTargetNode)
            ->first();

        return $result ?: null;
    }

    /**
     * Create new parse edge
     */
    public static function create(array $data): int
    {
        if (! isset($data['linkType'])) {
            $data['linkType'] = 'dependency';
        }

        if (! isset($data['weight'])) {
            $data['weight'] = 1.0;
        }

        return Criteria::create('parser_link', $data);
    }

    /**
     * Create edge if it doesn't exist
     */
    public static function createIfNotExists(array $data): ?int
    {
        if (self::exists($data['idSourceNode'], $data['idTargetNode'])) {
            return null;
        }

        return self::create($data);
    }

    /**
     * Update parse edge
     */
    public static function update(int $id, array $data): void
    {
        Criteria::table('parser_link')
            ->where('idParserLink', '=', $id)
            ->update($data);
    }

    /**
     * Delete parse edge
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_link', 'idParserLink', $id);
    }

    /**
     * Delete all edges from a source node
     */
    public static function deleteBySourceNode(int $idSourceNode): int
    {
        return Criteria::table('parser_link')
            ->where('idSourceNode', '=', $idSourceNode)
            ->delete();
    }

    /**
     * Delete all edges to a target node
     */
    public static function deleteByTargetNode(int $idTargetNode): int
    {
        return Criteria::table('parser_link')
            ->where('idTargetNode', '=', $idTargetNode)
            ->delete();
    }

    /**
     * Delete all edges involving a node (source or target)
     */
    public static function deleteByNode(int $idParserNode): int
    {
        return Criteria::table('parser_link')
            ->where(function ($query) use ($idParserNode) {
                $query->where('idSourceNode', '=', $idParserNode)
                    ->orWhere('idTargetNode', '=', $idParserNode);
            })
            ->delete();
    }

    /**
     * Transfer all edges from one node to another
     */
    public static function transferEdges(int $fromNodeId, int $toNodeId): void
    {
        // Transfer incoming edges (where fromNode is target)
        Criteria::table('parser_link')
            ->where('idTargetNode', '=', $fromNodeId)
            ->update(['idTargetNode' => $toNodeId]);

        // Transfer outgoing edges (where fromNode is source)
        Criteria::table('parser_link')
            ->where('idSourceNode', '=', $fromNodeId)
            ->update(['idSourceNode' => $toNodeId]);
    }

    /**
     * Count edges in parse graph
     */
    public static function count(int $idParserGraph): int
    {
        return Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->count();
    }

    /**
     * Get edge with node details
     */
    public static function getWithNodes(int $id): object
    {
        return Criteria::table('parser_link as pe')
            ->join('parser_node as pn_source', 'pe.idSourceNode', '=', 'pn_source.idParserNode')
            ->join('parser_node as pn_target', 'pe.idTargetNode', '=', 'pn_target.idParserNode')
            ->select(
                'pe.*',
                'pn_source.label as sourceLabel',
                'pn_source.type as sourceType',
                'pn_target.label as targetLabel',
                'pn_target.type as targetType'
            )
            ->where('pe.idParserLink', '=', $id)
            ->first();
    }

    /**
     * List edges with node details for a parse graph
     */
    public static function listWithNodes(int $idParserGraph): array
    {
        return Criteria::table('parser_link as pe')
            ->join('parser_node as pn_source', 'pe.idSourceNode', '=', 'pn_source.idParserNode')
            ->join('parser_node as pn_target', 'pe.idTargetNode', '=', 'pn_target.idParserNode')
            ->select(
                'pe.*',
                'pn_source.label as sourceLabel',
                'pn_source.type as sourceType',
                'pn_source.positionInSentence as sourcePosition',
                'pn_target.label as targetLabel',
                'pn_target.type as targetType',
                'pn_target.positionInSentence as targetPosition'
            )
            ->where('pe.idParserGraph', '=', $idParserGraph)
            ->all();
    }

    /**
     * Create edge with stage and compatibility info
     */
    public static function createWithStage(array $data): int
    {
        // Set defaults for new columns
        if (! isset($data['stage'])) {
            $data['stage'] = 'translation';
        }

        if (! isset($data['compatibilityScore'])) {
            $data['compatibilityScore'] = null;
        }

        if (! isset($data['featureMatch'])) {
            $data['featureMatch'] = null;
        } elseif (is_array($data['featureMatch'])) {
            $data['featureMatch'] = json_encode($data['featureMatch']);
        }

        return self::create($data);
    }

    /**
     * List edges by stage
     */
    public static function listByStage(int $idParserGraph, string $stage): array
    {
        return Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->where('stage', '=', $stage)
            ->all();
    }

    /**
     * List edges by stage with node details
     */
    public static function listByStageWithNodes(int $idParserGraph, string $stage): array
    {
        return Criteria::table('parser_link as pe')
            ->join('parser_node as pn_source', 'pe.idSourceNode', '=', 'pn_source.idParserNode')
            ->join('parser_node as pn_target', 'pe.idTargetNode', '=', 'pn_target.idParserNode')
            ->select(
                'pe.*',
                'pn_source.label as sourceLabel',
                'pn_source.type as sourceType',
                'pn_target.label as targetLabel',
                'pn_target.type as targetType'
            )
            ->where('pe.idParserGraph', '=', $idParserGraph)
            ->where('pe.stage', '=', $stage)
            ->all();
    }

    /**
     * Count edges by source node and stage
     */
    public static function countBySourceAndStage(int $idSourceNode, string $stage): int
    {
        return Criteria::table('parser_link')
            ->where('idSourceNode', '=', $idSourceNode)
            ->where('stage', '=', $stage)
            ->count();
    }

    /**
     * Count edges by target node and stage
     */
    public static function countByTargetAndStage(int $idTargetNode, string $stage): int
    {
        return Criteria::table('parser_link')
            ->where('idTargetNode', '=', $idTargetNode)
            ->where('stage', '=', $stage)
            ->count();
    }

    /**
     * Count all edges to target (any stage)
     */
    public static function countByTarget(int $idTargetNode): int
    {
        return Criteria::table('parser_link')
            ->where('idTargetNode', '=', $idTargetNode)
            ->count();
    }

    /**
     * Set stage for edge
     */
    public static function setStage(int $idParserLink, string $stage): void
    {
        self::update($idParserLink, ['stage' => $stage]);
    }

    /**
     * Count edges by stage
     */
    public static function countByStage(int $idParserGraph, string $stage): int
    {
        return Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->where('stage', '=', $stage)
            ->count();
    }
}

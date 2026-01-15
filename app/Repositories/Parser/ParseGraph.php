<?php

namespace App\Repositories\Parser;

use App\Database\Criteria;

class ParseGraph
{
    /**
     * Retrieve parse graph by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_graph', 'idParserGraph', $id);
    }

    /**
     * Get parse graph with statistics
     */
    public static function byIdWithStats(int $id): object
    {
        return Criteria::table('view_parser_graph_stats')
            ->where('idParserGraph', '=', $id)
            ->first();
    }

    /**
     * List all parse graphs
     */
    public static function list(int $limit = 100): array
    {
        return Criteria::table('view_parser_graph_stats')
            ->orderBy('idParserGraph', 'DESC')
            ->limit($limit)
            ->all();
    }

    /**
     * List parse graphs by grammar
     */
    public static function listByGrammar(int $idGrammarGraph, int $limit = 100): array
    {
        return Criteria::table('view_parser_graph_stats')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('idParserGraph', 'DESC')
            ->limit($limit)
            ->all();
    }

    /**
     * List parse graphs by status
     */
    public static function listByStatus(string $status, int $limit = 100): array
    {
        return Criteria::table('view_parser_graph_stats')
            ->where('status', '=', $status)
            ->orderBy('idParserGraph', 'DESC')
            ->limit($limit)
            ->all();
    }

    /**
     * Get complete parse graph with all nodes and edges
     */
    public static function getComplete(int $id): object
    {
        $parseGraph = self::byId($id);
        $parseGraph->nodes = ParseNode::listByParseGraph($id);
        $parseGraph->edges = ParseEdge::listByParseGraph($id);

        return $parseGraph;
    }

    /**
     * Create new parse graph
     */
    public static function create(array $data): int
    {
        if (! isset($data['status'])) {
            $data['status'] = 'parsing';
        }

        return Criteria::create('parser_graph', $data);
    }

    /**
     * Update parse graph
     */
    public static function update(int $id, array $data): void
    {
        Criteria::table('parser_graph')
            ->where('idParserGraph', '=', $id)
            ->update($data);
    }

    /**
     * Update parse graph status
     */
    public static function updateStatus(int $id, string $status, ?string $errorMessage = null): void
    {
        $data = ['status' => $status];

        if ($errorMessage !== null) {
            $data['errorMessage'] = $errorMessage;
        }

        self::update($id, $data);
    }

    /**
     * Mark parse as complete
     */
    public static function markComplete(int $id): void
    {
        self::updateStatus($id, 'complete');
    }

    /**
     * Mark parse as failed
     */
    public static function markFailed(int $id, string $errorMessage): void
    {
        self::updateStatus($id, 'failed', $errorMessage);
    }

    /**
     * Delete parse graph and all associated nodes/edges
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_graph', 'idParserGraph', $id);
    }

    /**
     * Delete all nodes and edges for a parse graph
     */
    public static function clearStructure(int $idParserGraph): void
    {
        Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->delete();

        Criteria::table('parser_node')
            ->where('idParserGraph', '=', $idParserGraph)
            ->delete();
    }

    /**
     * Count nodes in parse graph
     */
    public static function countNodes(int $idParserGraph): int
    {
        $result = Criteria::table('parser_node')
            ->where('idParserGraph', '=', $idParserGraph)
            ->count();

        return $result;
    }

    /**
     * Count edges in parse graph
     */
    public static function countEdges(int $idParserGraph): int
    {
        $result = Criteria::table('parser_link')
            ->where('idParserGraph', '=', $idParserGraph)
            ->count();

        return $result;
    }

    /**
     * Get focus nodes
     */
    public static function getFocusNodes(int $idParserGraph): array
    {
        return Criteria::table('parser_node')
            ->where('idParserGraph', '=', $idParserGraph)
            ->where('isFocus', '=', true)
            ->orderBy('positionInSentence')
            ->all();
    }

    /**
     * Get nodes that didn't reach threshold (garbage)
     */
    public static function getGarbageNodes(int $idParserGraph): array
    {
        return Criteria::table('parser_node')
            ->where('idParserGraph', '=', $idParserGraph)
            ->whereRaw('activation < threshold')
            ->all();
    }

    /**
     * Check if parse is valid (all nodes connected)
     */
    public static function isValid(int $idParserGraph): bool
    {
        $totalNodes = self::countNodes($idParserGraph);
        $totalEdges = self::countEdges($idParserGraph);

        // A connected graph should have at least (nodes - 1) edges
        // This is a simplified check; a more thorough check would verify actual connectivity
        return $totalEdges >= ($totalNodes - 1);
    }

    /**
     * Set root node for the parse graph
     */
    public static function setRoot(int $idParserGraph, int $idRootNode): void
    {
        self::update($idParserGraph, ['idRootNode' => $idRootNode]);
    }

    /**
     * Get root node for the parse graph
     */
    public static function getRootNode(int $idParserGraph): ?object
    {
        $graph = self::byId($idParserGraph);

        if (empty($graph->idRootNode)) {
            return null;
        }

        return ParseNode::byId($graph->idRootNode);
    }
}

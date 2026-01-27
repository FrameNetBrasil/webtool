<?php

namespace App\Repositories\Parser;

use App\Data\Parser\V5\TypeGraph;
use App\Data\Parser\V5\TypeGraphEdge;
use App\Data\Parser\V5\TypeGraphNode;
use App\Repositories\Criteria;
use Illuminate\Support\Facades\DB;

/**
 * Type Graph Repository
 *
 * Handles persistence and retrieval of Type Graphs.
 */
class TypeGraphRepository
{
    /**
     * Save Type Graph to database
     *
     * @return int Type Graph ID
     */
    public function save(TypeGraph $typeGraph): int
    {
        $data = $typeGraph->toStorageArray();

        // Check if Type Graph already exists for this grammar
        $existing = $this->findByGrammar($typeGraph->idGrammarGraph);

        if ($existing) {
            // Update existing
            DB::table('parser_type_graph_v5')
                ->where('idGrammarGraph', $typeGraph->idGrammarGraph)
                ->update([
                    'graphData' => json_encode($data['graphData']),
                    'nodes' => json_encode($data['nodes']),
                    'edges' => json_encode($data['edges']),
                    'mandatoryElements' => json_encode($data['mandatoryElements']),
                    'ceHierarchy' => json_encode($data['ceHierarchy']),
                    'updatedAt' => now(),
                ]);

            return $existing['idTypeGraph'];
        } else {
            // Insert new
            $id = DB::table('parser_type_graph_v5')->insertGetId([
                'idGrammarGraph' => $typeGraph->idGrammarGraph,
                'graphData' => json_encode($data['graphData']),
                'nodes' => json_encode($data['nodes']),
                'edges' => json_encode($data['edges']),
                'mandatoryElements' => json_encode($data['mandatoryElements']),
                'ceHierarchy' => json_encode($data['ceHierarchy']),
                'version' => 'v5',
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            return $id;
        }
    }

    /**
     * Load Type Graph by grammar ID
     */
    public function loadByGrammar(int $idGrammarGraph): ?TypeGraph
    {
        $row = $this->findByGrammar($idGrammarGraph);

        if (! $row) {
            return null;
        }

        return $this->rowToTypeGraph($row);
    }

    /**
     * Load Type Graph by ID
     */
    public function loadById(int $idTypeGraph): ?TypeGraph
    {
        $criteria = new Criteria;
        $criteria->where('idTypeGraph', '=', $idTypeGraph);

        $row = DB::table('parser_type_graph_v5')
            ->where('idTypeGraph', $idTypeGraph)
            ->first();

        if (! $row) {
            return null;
        }

        return $this->rowToTypeGraph((array) $row);
    }

    /**
     * Delete Type Graph
     */
    public function deleteByGrammar(int $idGrammarGraph): bool
    {
        return DB::table('parser_type_graph_v5')
            ->where('idGrammarGraph', $idGrammarGraph)
            ->delete() > 0;
    }

    /**
     * Save construction relationships
     */
    public function saveRelationships(int $idGrammarGraph, array $relationships): void
    {
        // Clear existing relationships
        DB::table('parser_construction_relationship_v5')
            ->where('idGrammarGraph', $idGrammarGraph)
            ->delete();

        // Insert new relationships
        if (empty($relationships)) {
            return;
        }

        $rows = [];
        foreach ($relationships as $rel) {
            $rows[] = [
                'idGrammarGraph' => $idGrammarGraph,
                'sourceType' => $rel['sourceType'],
                'sourceId' => $rel['sourceId'] ?? null,
                'sourceName' => $rel['sourceName'],
                'relationshipType' => $rel['relationshipType'],
                'targetType' => $rel['targetType'],
                'targetId' => $rel['targetId'] ?? null,
                'targetName' => $rel['targetName'],
                'mandatory' => $rel['mandatory'] ?? false,
                'metadata' => isset($rel['metadata']) ? json_encode($rel['metadata']) : null,
            ];
        }

        DB::table('parser_construction_relationship_v5')->insert($rows);
    }

    /**
     * Load construction relationships
     */
    public function loadRelationships(int $idGrammarGraph): array
    {
        $rows = DB::table('parser_construction_relationship_v5')
            ->where('idGrammarGraph', $idGrammarGraph)
            ->get();

        return $rows->map(function ($row) {
            return [
                'idRelationship' => $row->idRelationship,
                'sourceType' => $row->sourceType,
                'sourceId' => $row->sourceId,
                'sourceName' => $row->sourceName,
                'relationshipType' => $row->relationshipType,
                'targetType' => $row->targetType,
                'targetId' => $row->targetId,
                'targetName' => $row->targetName,
                'mandatory' => (bool) $row->mandatory,
                'metadata' => $row->metadata ? json_decode($row->metadata, true) : null,
            ];
        })->toArray();
    }

    /**
     * Find Type Graph row by grammar ID
     */
    private function findByGrammar(int $idGrammarGraph): ?array
    {
        $row = DB::table('parser_type_graph_v5')
            ->where('idGrammarGraph', $idGrammarGraph)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * Convert database row to TypeGraph object
     */
    private function rowToTypeGraph(array $row): TypeGraph
    {
        $graphData = json_decode($row['graphData'], true);
        $nodesData = json_decode($row['nodes'], true);
        $edgesData = json_decode($row['edges'], true);
        $mandatoryElements = json_decode($row['mandatoryElements'], true);
        $ceHierarchy = json_decode($row['ceHierarchy'], true);

        // Reconstruct nodes
        $nodes = array_map(
            fn ($nodeData) => TypeGraphNode::from($nodeData),
            $nodesData
        );

        // Reconstruct edges
        $edges = array_map(
            fn ($edgeData) => TypeGraphEdge::from($edgeData),
            $edgesData
        );

        // Build indexes
        $constructionIndex = [];
        $ceLabelIndex = [];

        foreach ($nodes as $node) {
            if ($node->isConstruction()) {
                $constructionIndex[$node->name] = $node;
            } elseif ($node->isCELabel()) {
                $level = $node->getCELevel();
                $key = "{$level}_{$node->name}";
                $ceLabelIndex[$key] = $node;
            }
        }

        return new TypeGraph(
            idGrammarGraph: $row['idGrammarGraph'],
            nodes: $nodes,
            edges: $edges,
            mandatoryElements: $mandatoryElements,
            ceHierarchy: $ceHierarchy,
            constructionIndex: $constructionIndex,
            ceLabelIndex: $ceLabelIndex
        );
    }
}

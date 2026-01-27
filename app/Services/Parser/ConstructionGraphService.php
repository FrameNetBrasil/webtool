<?php

namespace App\Services\Parser;

use App\Data\Parser\V5\TypeGraph;
use App\Repositories\Parser\ConstructionV4;
use App\Repositories\Parser\TypeGraphRepository;

/**
 * Construction Graph Service
 *
 * Generates graph visualizations for constructions:
 * - Pattern Graph: Visual representation of BNF pattern structure
 * - Type Graph: V5 unified construction ontology with relationships
 * - Priority Graph: Visual lanes showing construction priorities by type
 */
class ConstructionGraphService
{
    /**
     * Generate pattern graph from construction's compiled pattern
     *
     * Creates a JointJS-compatible graph showing the BNF structure
     */
    public function generatePatternGraph(object $construction): array
    {
        $compiledPattern = ConstructionV4::getCompiledPattern($construction);

        if (! $compiledPattern) {
            return [
                'nodes' => [],
                'edges' => [],
            ];
        }

        $jointJsNodes = [];
        $jointJsEdges = [];

        // Convert compiled pattern nodes to JointJS format
        foreach ($compiledPattern['nodes'] ?? [] as $id => $node) {
            $nodeType = $node['type'] ?? 'UNKNOWN';

            $jointJsNodes[] = [
                'id' => "node_$id",
                'type' => $nodeType,
                'label' => $this->getNodeLabel($node),
                'shape' => $this->getNodeShape($nodeType),
                'color' => $this->getNodeColor($nodeType),
            ];
        }

        // Convert edges to JointJS format
        foreach ($compiledPattern['edges'] ?? [] as $edge) {
            $jointJsEdges[] = [
                'id' => "edge_{$edge['from']}_{$edge['to']}",
                'source' => "node_{$edge['from']}",
                'target' => "node_{$edge['to']}",
                'label' => $edge['label'] ?? '',
            ];
        }

        return [
            'nodes' => $jointJsNodes,
            'edges' => $jointJsEdges,
            'layout' => 'dagre',
            'direction' => 'LR', // Left to right
        ];
    }

    /**
     * Generate Type Graph subgraph for a specific construction
     *
     * Shows the construction's relationships to other constructions and CE labels
     * within the unified Type Graph ontology (V5)
     *
     * @param  object  $construction  Construction object
     * @param  int  $maxDepth  Maximum traversal depth (default: 2)
     * @return array JointJS-compatible graph data
     */
    public function generateTypeGraph(object $construction, int $maxDepth = 2): array
    {
        // Load or build Type Graph for this grammar
        $typeGraph = $this->getOrBuildTypeGraph($construction->idGrammarGraph);

        if (! $typeGraph) {
            return [
                'nodes' => [],
                'edges' => [],
                'error' => 'Failed to load Type Graph',
            ];
        }

        // Extract subgraph centered on this construction
        $subgraph = $typeGraph->getSubgraphForConstruction($construction->idConstruction, $maxDepth);

        // Convert to JointJS format
        $jointJsNodes = [];
        $jointJsEdges = [];

        // Process nodes
        foreach ($subgraph['nodes'] as $node) {
            if ($node->isConstruction()) {
                // Construction node (rectangle)
                $isCenterNode = $node->constructionId === $construction->idConstruction;

                $jointJsNodes[] = [
                    'id' => $node->id,
                    'type' => 'construction',
                    'label' => $node->name,
                    'constructionType' => $node->constructionType,
                    'shape' => 'rect',
                    'color' => $this->getConstructionTypeColor($node->constructionType),
                    'borderWidth' => $isCenterNode ? 4 : 2,
                    'borderColor' => $isCenterNode ? '#000' : '#333',
                ];
            } elseif ($node->isCELabel()) {
                // CE label node (ellipse)
                $level = $node->getCELevel();

                $jointJsNodes[] = [
                    'id' => $node->id,
                    'type' => 'ce_label',
                    'label' => $node->name,
                    'ceLevel' => $level,
                    'shape' => 'ellipse',
                    'color' => $this->getCELabelColor($level),
                    'borderWidth' => 2,
                    'borderColor' => '#666',
                ];
            }
        }

        // Process edges
        foreach ($subgraph['edges'] as $edge) {
            $jointJsEdges[] = [
                'id' => $edge->id,
                'source' => $edge->fromNodeId,
                'target' => $edge->toNodeId,
                'label' => $edge->relationshipType,
                'relationshipType' => $edge->relationshipType,
                'mandatory' => $edge->mandatory,
                'color' => $this->getRelationshipTypeColor($edge->relationshipType),
                'style' => $edge->mandatory ? 'solid' : 'dashed',
            ];
        }

        return [
            'nodes' => $jointJsNodes,
            'edges' => $jointJsEdges,
            'layout' => 'dagre',
            'direction' => 'TB', // Top to bottom
            'centerNodeId' => "construction_{$construction->idConstruction}",
        ];
    }

    /**
     * Get or build Type Graph for a grammar
     */
    private function getOrBuildTypeGraph(int $idGrammarGraph): ?TypeGraph
    {
        $repository = app(TypeGraphRepository::class);

        // Try to load existing Type Graph
        $typeGraph = $repository->loadByGrammar($idGrammarGraph);

        if ($typeGraph) {
            return $typeGraph;
        }

        // Build new Type Graph
        $builder = app(TypeGraphBuilder::class);
        $typeGraph = $builder->buildForGrammar($idGrammarGraph);

        // Save it for future use
        $repository->save($typeGraph);

        return $typeGraph;
    }

    /**
     * Generate priority graph showing visual lanes by construction type
     */
    public function generatePriorityGraph(int $idGrammarGraph): array
    {
        $constructions = ConstructionV4::listByGrammar($idGrammarGraph);

        $lanes = [
            'sentential' => ['min' => 1, 'max' => 19, 'y' => 0, 'constructions' => []],
            'clausal' => ['min' => 20, 'max' => 49, 'y' => 100, 'constructions' => []],
            'phrasal' => ['min' => 50, 'max' => 99, 'y' => 200, 'constructions' => []],
            'mwe' => ['min' => 100, 'max' => 199, 'y' => 300, 'constructions' => []],
        ];

        // Group constructions by type
        foreach ($constructions as $construction) {
            $type = $construction->constructionType;
            if (isset($lanes[$type])) {
                $lanes[$type]['constructions'][] = $construction;
            }
        }

        $nodes = [];

        // Create nodes positioned in lanes
        foreach ($lanes as $type => $lane) {
            $laneMin = $lane['min'];
            $laneMax = $lane['max'];
            $laneY = $lane['y'];

            foreach ($lane['constructions'] as $construction) {
                // Position X based on priority within lane range
                $priority = $construction->priority;
                $rangeWidth = $laneMax - $laneMin;
                $normalizedPriority = ($priority - $laneMin) / $rangeWidth;
                $x = $normalizedPriority * 800; // 800px lane width

                $nodes[] = [
                    'id' => "construction_{$construction->idConstruction}",
                    'label' => $construction->name,
                    'type' => $type,
                    'priority' => $priority,
                    'enabled' => (bool) $construction->enabled,
                    'x' => $x,
                    'y' => $laneY,
                    'color' => $this->getConstructionTypeColor($type),
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'lanes' => [
                ['type' => 'sentential', 'label' => 'Sentential (1-19)', 'y' => 0, 'color' => '#F44336'],
                ['type' => 'clausal', 'label' => 'Clausal (20-49)', 'y' => 100, 'color' => '#FF9800'],
                ['type' => 'phrasal', 'label' => 'Phrasal (50-99)', 'y' => 200, 'color' => '#4CAF50'],
                ['type' => 'mwe', 'label' => 'MWE (100-199)', 'y' => 300, 'color' => '#2196F3'],
            ],
        ];
    }

    /**
     * Get label for pattern node
     */
    private function getNodeLabel(array $node): string
    {
        $type = $node['type'] ?? 'UNKNOWN';

        return match ($type) {
            'START' => 'START',
            'END' => 'END',
            'LITERAL' => $node['value'] ?? 'literal',
            'SLOT' => '{'.($node['pos'] ?? 'POS').'}',
            'WILDCARD' => '{*}',
            default => $type,
        };
    }

    /**
     * Get shape for pattern node type
     */
    private function getNodeShape(string $type): string
    {
        return match ($type) {
            'START', 'END' => 'circle',
            'LITERAL' => 'rect',
            'SLOT' => 'rect',
            'WILDCARD' => 'rect',
            default => 'rect',
        };
    }

    /**
     * Get color for pattern node type
     */
    private function getNodeColor(string $type): string
    {
        return match ($type) {
            'START' => '#4CAF50', // Green
            'END' => '#F44336', // Red
            'LITERAL' => '#2196F3', // Blue
            'SLOT' => '#FFC107', // Yellow
            'WILDCARD' => '#9E9E9E', // Gray
            default => '#757575', // Dark gray
        };
    }

    /**
     * Get color for construction type
     */
    private function getConstructionTypeColor(string $type): string
    {
        return match ($type) {
            'mwe' => '#2196F3', // Blue
            'phrasal' => '#4CAF50', // Green
            'clausal' => '#FF9800', // Orange
            'sentential' => '#F44336', // Red
            default => '#757575', // Gray
        };
    }

    /**
     * Get color for CE label based on level
     */
    private function getCELabelColor(string $level): string
    {
        return match ($level) {
            'phrasal' => '#81C784', // Light Green
            'clausal' => '#FFB74D', // Light Orange
            'sentential' => '#E57373', // Light Red
            default => '#9E9E9E', // Gray
        };
    }

    /**
     * Get color for relationship type
     */
    private function getRelationshipTypeColor(string $type): string
    {
        return match ($type) {
            'produces' => '#4CAF50', // Green
            'requires' => '#2196F3', // Blue
            'inherits' => '#9C27B0', // Purple
            'conflicts_with' => '#F44336', // Red
            default => '#757575', // Gray
        };
    }
}

<?php

namespace App\Services\Parser\V4;

use App\Repositories\Parser\ConstructionV4;

/**
 * Construction Graph Service
 *
 * Generates graph visualizations for constructions:
 * - Pattern Graph: Visual representation of BNF pattern structure
 * - Hierarchy Graph: Relationships between constructions sharing CE labels
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

        if (!$compiledPattern) {
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
     * Generate hierarchy graph showing construction relationships
     *
     * Creates graph based on shared CE labels
     */
    public function generateHierarchyGraph(int $idGrammarGraph): array
    {
        $constructions = ConstructionV4::listByGrammar($idGrammarGraph);

        $nodes = [];
        $edges = [];

        // Create nodes for all constructions
        foreach ($constructions as $construction) {
            $nodes[] = [
                'id' => "construction_{$construction->idConstruction}",
                'label' => $construction->name,
                'type' => $construction->constructionType,
                'color' => $this->getConstructionTypeColor($construction->constructionType),
                'phrasalCE' => $construction->phrasalCE,
                'clausalCE' => $construction->clausalCE,
                'sententialCE' => $construction->sententialCE,
            ];
        }

        // Create edges based on shared CE labels
        foreach ($constructions as $c1) {
            foreach ($constructions as $c2) {
                if ($c1->idConstruction === $c2->idConstruction) {
                    continue;
                }

                // Phrasal CE relationship
                if (!empty($c1->phrasalCE) && $c1->phrasalCE === $c2->phrasalCE) {
                    $edges[] = [
                        'id' => "edge_phrasal_{$c1->idConstruction}_{$c2->idConstruction}",
                        'source' => "construction_{$c1->idConstruction}",
                        'target' => "construction_{$c2->idConstruction}",
                        'label' => "shares_phrasal_ce: {$c1->phrasalCE}",
                        'type' => 'phrasal_ce',
                        'color' => '#4CAF50', // Green
                    ];
                }

                // Clausal CE relationship
                if (!empty($c1->clausalCE) && $c1->clausalCE === $c2->clausalCE) {
                    $edges[] = [
                        'id' => "edge_clausal_{$c1->idConstruction}_{$c2->idConstruction}",
                        'source' => "construction_{$c1->idConstruction}",
                        'target' => "construction_{$c2->idConstruction}",
                        'label' => "shares_clausal_ce: {$c1->clausalCE}",
                        'type' => 'clausal_ce',
                        'color' => '#FF9800', // Orange
                    ];
                }

                // Sentential CE relationship
                if (!empty($c1->sententialCE) && $c1->sententialCE === $c2->sententialCE) {
                    $edges[] = [
                        'id' => "edge_sentential_{$c1->idConstruction}_{$c2->idConstruction}",
                        'source' => "construction_{$c1->idConstruction}",
                        'target' => "construction_{$c2->idConstruction}",
                        'label' => "shares_sentential_ce: {$c1->sententialCE}",
                        'type' => 'sentential_ce',
                        'color' => '#F44336', // Red
                    ];
                }
            }
        }

        // Remove duplicate edges (keep first one)
        $uniqueEdges = [];
        $edgeKeys = [];
        foreach ($edges as $edge) {
            $key = "{$edge['source']}_{$edge['target']}";
            if (!in_array($key, $edgeKeys)) {
                $uniqueEdges[] = $edge;
                $edgeKeys[] = $key;
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $uniqueEdges,
            'layout' => 'dagre',
            'direction' => 'TB', // Top to bottom
        ];
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
            'SLOT' => '{' . ($node['pos'] ?? 'POS') . '}',
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
}

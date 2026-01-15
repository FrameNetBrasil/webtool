<?php

namespace App\Services\Parser;

use App\Repositories\Parser\MWE;

/**
 * Graph Format Converter
 *
 * Converts Construction compiled graphs and MWE component structures
 * to unified JointJS format for interactive visualization.
 */
class GraphConverter
{
    /**
     * Convert Construction compiled graph to JointJS format
     *
     * @param  array  $compiledGraph  Graph from PatternCompiler with nodes/edges
     * @return array JointJS-compatible graph structure
     */
    public function constructionToJointJS(array $compiledGraph): array
    {
        $jointNodes = [];
        $jointLinks = [];

        // Convert nodes
        foreach ($compiledGraph['nodes'] as $nodeId => $node) {
            $jointNodes[$nodeId] = [
                'type' => 'pattern',
                'name' => $this->formatNodeLabel($node),
                'nodeType' => $node['type'],
                'idColor' => $this->getNodeColor($node['type']),
                'shape' => $this->getNodeShape($node['type']),
                'details' => $this->getNodeDetails($node),
            ];
        }

        // Convert edges
        foreach ($compiledGraph['edges'] as $edge) {
            $fromId = $edge['from'];
            $toId = $edge['to'];

            if (! isset($jointLinks[$fromId])) {
                $jointLinks[$fromId] = [];
            }

            $isBypass = $edge['bypass'] ?? false;

            $jointLinks[$fromId][$toId] = [
                'type' => 'pattern',
                'relationEntry' => $edge['label'] ?? ($isBypass ? 'optional' : 'seq'),
                'color' => $isBypass ? '#999' : '#333',
                'style' => $isBypass ? 'dashed' : 'solid',
                'weight' => 1.0,
            ];
        }

        return [
            'nodes' => $jointNodes,
            'links' => $jointLinks,
        ];
    }

    /**
     * Convert MWE components to JointJS graph
     *
     * Creates a linear graph: START → Component1 → Component2 → ... → END
     *
     * @param  object  $mwe  MWE object with components
     * @return array JointJS-compatible graph structure
     */
    public function mweToJointJS(object $mwe): array
    {
        $components = MWE::getParsedComponents($mwe);
        $jointNodes = [];
        $jointLinks = [];

        // Create START node
        $jointNodes['start'] = [
            'type' => 'pattern',
            'name' => 'START',
            'nodeType' => 'START',
            'idColor' => '#4CAF50',
            'shape' => 'circle',
            'details' => 'Pattern start',
        ];

        // Create component nodes
        $prevId = 'start';
        foreach ($components as $idx => $component) {
            $nodeId = "comp_$idx";

            $jointNodes[$nodeId] = [
                'type' => 'pattern',
                'name' => $this->formatMWEComponent($component),
                'nodeType' => 'COMPONENT',
                'idColor' => $this->getMWEComponentColor($component),
                'shape' => 'box',
                'details' => $this->getMWEComponentDetails($component),
            ];

            // Link from previous node
            $jointLinks[$prevId][$nodeId] = [
                'type' => 'pattern',
                'relationEntry' => 'seq',
                'color' => '#333',
                'style' => 'solid',
                'weight' => 1.0,
            ];

            $prevId = $nodeId;
        }

        // Create END node
        $jointNodes['end'] = [
            'type' => 'pattern',
            'name' => 'END',
            'nodeType' => 'END',
            'idColor' => '#F44336',
            'shape' => 'circle',
            'details' => 'Pattern end',
        ];

        $jointLinks[$prevId]['end'] = [
            'type' => 'pattern',
            'relationEntry' => 'complete',
            'color' => '#333',
            'style' => 'solid',
            'weight' => 1.0,
        ];

        return [
            'nodes' => $jointNodes,
            'links' => $jointLinks,
        ];
    }

    /**
     * Format node label for display
     */
    private function formatNodeLabel(array $node): string
    {
        return match ($node['type']) {
            'START' => 'START',
            'END' => 'END',
            'LITERAL' => $node['value'],
            'SLOT' => isset($node['constraint']) && $node['constraint'] !== null
                ? "{{$node['pos']}:{$node['constraint']}}"
                : "{{$node['pos']}}",
            'WILDCARD' => '{*}',
            'OPTIONAL' => '[...]',
            'INTERMEDIATE' => '·',
            'REP_CHECK' => '↺',
            default => $node['type'],
        };
    }

    /**
     * Get node color based on type
     */
    private function getNodeColor(string $type): string
    {
        return match ($type) {
            'START' => '#4CAF50',        // Green
            'END' => '#F44336',          // Red
            'LITERAL' => '#2196F3',      // Blue
            'SLOT' => '#FF9800',         // Orange
            'WILDCARD' => '#9C27B0',     // Purple
            'OPTIONAL' => '#00BCD4',     // Cyan
            'INTERMEDIATE' => '#9E9E9E', // Grey
            'REP_CHECK' => '#FFEB3B',    // Yellow
            default => '#999',
        };
    }

    /**
     * Get node shape based on type
     */
    private function getNodeShape(string $type): string
    {
        return match ($type) {
            'START', 'END' => 'circle',
            'REP_CHECK' => 'diamond',
            default => 'box',
        };
    }

    /**
     * Get node details for tooltip
     */
    private function getNodeDetails(array $node): string
    {
        return match ($node['type']) {
            'START' => 'Pattern matching starts here',
            'END' => 'Pattern matching completes here',
            'LITERAL' => "Match literal word: '{$node['value']}'",
            'SLOT' => isset($node['constraint']) && $node['constraint'] !== null
                ? "Match POS '{$node['pos']}' with constraint '{$node['constraint']}'"
                : "Match any token with POS '{$node['pos']}'",
            'WILDCARD' => 'Match any token (wildcard)',
            'OPTIONAL' => 'Optional element (can skip)',
            'INTERMEDIATE' => 'Intermediate node (pass-through)',
            'REP_CHECK' => 'Repetition check point',
            default => $node['type'],
        };
    }

    /**
     * Format MWE component for display
     */
    private function formatMWEComponent(array $component): string
    {
        // Simple format: just a string
        if (is_string($component)) {
            return $component;
        }

        // Extended format: {type, value}
        $type = $component['type'] ?? 'W';
        $value = $component['value'] ?? '';

        return match ($type) {
            'W' => $value,                    // Word
            'L' => "lemma:$value",            // Lemma
            'P' => "{{$value}}",              // POS tag
            'C' => "CE:$value",               // CE label
            '*' => '{*}',                     // Wildcard
            default => "$type:$value",
        };
    }

    /**
     * Get MWE component color based on type
     */
    private function getMWEComponentColor(array $component): string
    {
        // Simple format
        if (is_string($component)) {
            return '#2196F3'; // Blue for fixed words
        }

        // Extended format
        $type = $component['type'] ?? 'W';

        return match ($type) {
            'W' => '#2196F3',  // Blue for fixed words
            'L' => '#3F51B5',  // Indigo for lemmas
            'P' => '#FF9800',  // Orange for POS tags
            'C' => '#9C27B0',  // Purple for CE labels
            '*' => '#607D8B',  // Grey for wildcard
            default => '#999',
        };
    }

    /**
     * Get MWE component details for tooltip
     */
    private function getMWEComponentDetails(array $component): string
    {
        // Simple format
        if (is_string($component)) {
            return "Match exact word: '$component'";
        }

        // Extended format
        $type = $component['type'] ?? 'W';
        $value = $component['value'] ?? '';

        return match ($type) {
            'W' => "Match exact word: '$value'",
            'L' => "Match lemma: '$value'",
            'P' => "Match POS tag: $value",
            'C' => "Match CE label: $value",
            '*' => 'Match any token',
            default => "$type: $value",
        };
    }

    /**
     * Get graph statistics
     */
    public function getGraphStats(array $graph): array
    {
        return [
            'nodeCount' => count($graph['nodes'] ?? []),
            'edgeCount' => array_sum(array_map('count', $graph['links'] ?? [])),
        ];
    }
}

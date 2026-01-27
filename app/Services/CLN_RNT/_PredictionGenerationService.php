<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\L5Layer;
use App\Models\CLN_RNT\Node;

/**
 * Prediction Generation Service
 *
 * Handles prediction calculation logic for partial constructions.
 * This service enables node-centric prediction generation where partials
 * generate their own predictions instead of having L5Layer iterate over them.
 *
 * Part of Phase 2 of node-centric refactoring.
 */
class PredictionGenerationService
{
    /**
     * Calculate prediction data for a partial construction
     *
     * Determines the next unmatched element, extracts its type and value,
     * calculates prediction strength, and returns prediction data structure.
     *
     * @param Node $partial Partial construction node
     * @param L5Layer $l5 L5 layer for accessing helper methods
     * @return array|null Prediction data or null if no prediction needed
     */
    public static function calculatePrediction(
        Node                  $partial,
        ColumnSequenceManager $manager,
        L5Layer               $l5
    ): void
    {
        // Only partial constructions generate predictions
        if (!($partial->metadata['is_partial'] ?? false)) {
            return;
        }

        // Get pattern matching state
        $graphData = $partial->metadata['graph'] ?? $partial->metadata['graph_nodes'] ?? [];
        $matched = $partial->metadata['matched'] ?? [];
        $constructionId = $partial->metadata['construction_id'] ?? 0;
        $anchorPosition = $partial->metadata['anchor_position'] ?? 0;

        if (empty($graphData) || empty($matched)) {
            return;
        }

        // Find the next unmatched element in the pattern
        $expectedIndex = null;
        foreach ($matched as $idx => $isMatched) {
            if (!$isMatched) {
                $expectedIndex = $idx;
                break;
            }
        }

        // If all elements matched or no unmatched found, skip
        if ($expectedIndex === null) {
            return;
        }

        // Get next graph node using pattern graph navigation
        $nextNode = self::findGraphNodeByIndex($graphData, $expectedIndex);

        if (!$nextNode) {
            return;
        }

        // Extract prediction type and value from graph node
        $predictionType = self::getPredictionType($nextNode);
        $predictionValue = self::extractPredictedValue($nextNode);

        // Calculate strength including boosts
        $strength = self::calculateStrength($partial, $l5);

        // Skip weak predictions
        if ($strength < config('cln.activation.partial_construction_threshold', 0.25)) {
            return;
        }

        // Calculate ACTUAL target position for this prediction
        // Predicted nodes are created at the SAME position as the partial construction
        // The backward search will find these predictions when future tokens arrive
        $actualTargetPosition = $anchorPosition;

        $predictData = [
            'source_position' => $l5->columnPosition,
            'target_position' => $actualTargetPosition,
            'type' => $predictionType,
            'value' => $predictionValue,
            'strength' => $strength,
            'construction_id' => $constructionId,
            'metadata' => [
                'partial_construction_id' => $partial->id,
                'pattern_index' => $expectedIndex,
            ],
        ];
        debug($l5->columnPosition, $predictionValue);
        $manager->registerPrediction(
            constructionName: $predictionValue,
            sourceColumn: $l5->columnPosition,
            type: $predictionType,
            value: $predictionValue,
            strength: $strength,
            sourcePartialId: $partial->id,
            constructionId: $constructionId,
            metadata: [
                'partial_construction_id' => $partial->id,
                'pattern_index' => $expectedIndex,
            ]
        );

        // Create predicted node using factory
//        $predictedNode = $l5->createPredictedNode( $constructionId, $predictData);

        // Add to target L23 WITHOUT activation
//        $l5->addNode($predictedNode);

        // Auto-subscribe predicted node to target position for node-centric prediction checking
//                if (config('cln.node_centric_phases.predictions', false) && $this->eventRegistry) {
//                    $predictedNode->autoSubscribeToPosition($targetPosition, $this->eventRegistry);
//                }

        // Link L5 partial construction → predicted node (prediction link)
//        $partialId = $prediction->metadata['partial_construction_id'] ?? null;
//        if ($partialId !== null) {
//            $partial = $l5->getNode($partialId);
//            if ($partial !== null) {
//                $partial->addInput($predictedNode);
//                $predictedNode->addOutput($partial);
//            }
//        }


//        return [
//            'source_position' => $l5->columnPosition,
//            'target_position' => $actualTargetPosition,
//            'type' => $predictionType,
//            'value' => $predictionValue,
//            'strength' => $strength,
//            'construction_id' => $constructionId,
//            'metadata' => [
//                'partial_construction_id' => $partial->id,
//                'pattern_index' => $expectedIndex,
//            ],
//        ];
    }

    /**
     * Get prediction type from graph node
     *
     * Determines what type of prediction should be generated based on the graph node type.
     * Returns: 'word', 'pos', 'feature', 'ce', 'construction'
     *
     * @param array $graphNode Pattern graph node
     * @return string Prediction type
     */
    public static function getPredictionType(array $graphNode): string
    {
        $type = $graphNode['type'] ?? '';

        return match ($type) {
            'LITERAL' => 'word',             // LITERAL predicts specific word
            'SLOT' => 'pos',                 // SLOT predicts POS tag
            'CE_SLOT' => 'ce',               // CE_SLOT predicts CE label
            'COMBINED_SLOT' => 'pos',        // COMBINED_SLOT predicts POS (CE is secondary)
            'CONSTRAINT' => 'feature',       // CONSTRAINT predicts feature
            'CONSTRUCTION_REF' => 'construction',  // CONSTRUCTION_REF predicts construction
            default => 'word',
        };
    }

    /**
     * Extract predicted value from graph node
     *
     * Converts graph node information into the value that should be predicted.
     * For LITERAL nodes: returns the literal word (without quotes)
     * For SLOT nodes: returns the POS tag
     * For CONSTRAINT nodes: returns the feature constraint
     * For CONSTRUCTION_REF: returns the construction name or ID
     *
     * @param array $graphNode Pattern graph node
     * @return string Predicted value
     */
    public static function extractPredictedValue(array $graphNode): string
    {
        $type = $graphNode['type'] ?? '';

        $value = match ($type) {
            'LITERAL' => $graphNode['value'] ?? '',
            'SLOT' => $graphNode['pos'] ?? '',
            'CONSTRAINT' => $graphNode['constraint'] ?? '',
            'CONSTRUCTION_REF' => $graphNode['construction_name']
                ?? ($graphNode['construction_id'] ? "CXN#{$graphNode['construction_id']}" : ''),
            default => '',
        };

        // Strip quotes from LITERAL values
        if ($type === 'LITERAL') {
            $value = trim($value, '"\'');
        }

        return $value;
    }

    /**
     * Calculate partial construction activation strength
     *
     * Stronger partial constructions (more elements matched + boosts) make stronger predictions.
     * Combines base strength (proportion matched) with confirmation boosts.
     *
     * @param Node $partial The partial construction node
     * @param L5Layer $l5 L5 layer for accessing boost data
     * @return float Strength value (0-1)
     */
    public static function calculateStrength(Node $partial, L5Layer $l5): float
    {
        $matched = $partial->metadata['matched'] ?? [];
        $pattern = $partial->metadata['pattern'] ?? [];

        if (empty($pattern)) {
            return 0.0;
        }

        // Base strength: proportion of elements matched
        $matchedCount = count(array_filter($matched));
        $baseStrength = $matchedCount / count($pattern);

        // Add confirmation boosts
        $boost = $l5->getPartialConstructionBoost($partial->id);

        // Combine (capped at 1.0)
        return min(1.0, $baseStrength + $boost);
    }

    /**
     * Find graph node by pattern index
     *
     * Maps from a linear pattern index (0, 1, 2, ...) back to the actual
     * graph node at that position by walking the pattern graph from START to END.
     * This is needed for prediction generation which uses array indices from the matched array.
     *
     * @param array $graphData Full graph with nodes/edges
     * @param int $index Pattern element index (0-based)
     * @return array|null Graph node at that index, or null if not found
     */
    public static function findGraphNodeByIndex(array $graphData, int $index): ?array
    {
        $nodes = $graphData['nodes'] ?? $graphData;
        $edges = $graphData['edges'] ?? [];

        $patternNodes = [];

        // Find START node
        $currentNodeId = null;
        foreach ($nodes as $id => $node) {
            if (($node['type'] ?? '') === 'START') {
                $currentNodeId = $id;
                break;
            }
        }

        if ($currentNodeId === null) {
            return null;
        }

        // Walk edges from START to END
        $visited = [];
        while ($currentNodeId !== null) {
            if (isset($visited[$currentNodeId])) {
                break; // Cycle detected
            }
            $visited[$currentNodeId] = true;

            // Find next edge from current node
            $nextNodeId = null;
            foreach ($edges as $edge) {
                if ($edge['from'] === $currentNodeId) {
                    $nextNodeId = $edge['to'];
                    break;
                }
            }

            if ($nextNodeId === null) {
                break;
            }

            $node = $nodes[$nextNodeId] ?? null;
            if ($node && !in_array($node['type'] ?? '', ['START', 'END', 'INTERMEDIATE', 'REP_CHECK'])) {
                $patternNodes[] = $node;
            }

            $currentNodeId = $nextNodeId;
        }

        // Return node at specified index
        return $patternNodes[$index] ?? null;
    }

}

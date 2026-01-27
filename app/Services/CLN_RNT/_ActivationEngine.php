<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\Confirmation;
use App\Models\CLN_RNT\L23Layer;
use App\Models\CLN_RNT\L5Layer;

/**
 * Activation Engine
 *
 * Manages activation dynamics within and across columns.
 * Handles boosts, inhibition, and activation propagation.
 *
 * Key responsibilities:
 * - Activate L23 from input tokens
 * - Propagate L23 → L5 (feed-forward)
 * - Apply prediction boosts to L23 activations
 * - Generate lateral confirmations for matched predictions
 * - Apply lateral inhibition between competing constructions
 * - Suppress non-matching partial constructions
 */
class ActivationEngine
{
    /**
     * Activate L23 layer from input token
     *
     * Creates nodes for word, POS, and features from token.
     *
     * @param  L23Layer  $l23  The L23 layer to activate
     * @param  object  $token  UDPipe token
     * @return array Activation result with activated nodes
     */
    public function activateL23FromInput(L23Layer $l23, object $token): array
    {
        $activatedNodes = $l23->activateFromInput($token);

        return [
            'activated_nodes' => $activatedNodes,
            'word_count' => count(array_filter(
                $activatedNodes,
                fn ($n) => ($n->metadata['node_type'] ?? null) === 'word'
            )),
            'feature_count' => count(array_filter(
                $activatedNodes,
                fn ($n) => ($n->metadata['node_type'] ?? null) === 'feature'
            )),
            'pos_count' => count(array_filter(
                $activatedNodes,
                fn ($n) => ($n->metadata['node_type'] ?? null) === 'pos'
                    || (($n->metadata['node_type'] ?? null) === 'construction'
                        && ($n->metadata['is_single_element_pos'] ?? false))
            )),
        ];
    }

    /**
     * Propagate L23 activations to L5 (feed-forward circuit)
     *
     * Sends L23 activations to L5 for pattern matching.
     *
     * @param  L23Layer  $l23  Source layer
     * @param  L5Layer  $l5  Target layer
     * @return array Propagation result
     */
    public function propagateL23ToL5(L23Layer $l23, L5Layer $l5): array
    {
        $l23->propagateToL5($l5);

        return [
            'l23_activation' => $l23->getTotalActivation(),
            'l5_activation' => $l5->getTotalActivation(),
            'partial_construction_count' => count($l5->getPartialConstructions()),
        ];
    }

    /**
     * Apply prediction boost to L23 nodes
     *
     * When predictions match, boost the activation of matching nodes.
     * This reinforces predicted patterns.
     *
     * @param  L23Layer  $l23  The L23 layer
     * @param  array  $predictions  Matched predictions
     * @param  object  $token  Current token
     * @return array Boost results
     */
    public function applyPredictionBoost(L23Layer $l23, array $predictions, object $token): array
    {
        $boostedNodes = [];
        $boostFactor = config('cln.activation.boost_factor', 0.3);

        foreach ($predictions as $prediction) {
            // Find nodes that match this prediction
            $matchingNodes = $this->findMatchingNodes($l23, $prediction, $token);

            foreach ($matchingNodes as $node) {
                // Apply boost (implementation depends on node type)
                // For now, track which nodes were boosted
                $boostedNodes[] = [
                    'node_id' => $node->id,
                    'boost_amount' => $prediction->strength * $boostFactor,
                    'prediction' => $prediction,
                ];
            }
        }

        return [
            'boosted_count' => count($boostedNodes),
            'boosted_nodes' => $boostedNodes,
            'total_boost' => array_sum(array_column($boostedNodes, 'boost_amount')),
        ];
    }

    /**
     * Generate lateral confirmation for matched predictions
     *
     * When predictions match, create confirmation to send back to source column.
     *
     * @param  array  $matchedPredictions  Predictions that matched
     * @param  int  $sourcePosition  Position sending confirmation
     * @return array Array of Confirmation objects
     */
    public function generateLateralConfirmation(array $matchedPredictions, int $sourcePosition): array
    {
        $confirmations = [];

        foreach ($matchedPredictions as $prediction) {
            $confirmations[] = new Confirmation(
                sourcePosition: $sourcePosition,
                targetPosition: $prediction->sourcePosition,
                matchedFeature: $prediction->value,
                strength: $prediction->strength,
                constructionId: $prediction->constructionId,
                metadata: $prediction->metadata
            );
        }

        return $confirmations;
    }

    /**
     * Apply lateral inhibition between competing constructions
     *
     * Mutually exclusive constructions compete for activation.
     * Stronger constructions inhibit weaker ones.
     *
     * @param  L5Layer  $l5  The L5 layer
     * @return array Inhibition results
     */
    public function applyLateralInhibition(L5Layer $l5): array
    {
        $partialConstructions = $l5->getPartialConstructions();
        $inhibitionStrength = config('cln.activation.inhibition_strength', 0.5);

        if (count($partialConstructions) < 2) {
            return ['inhibited_count' => 0];
        }

        // Find competing constructions (overlapping positions/patterns)
        $competitions = $this->findCompetingConstructions($partialConstructions);

        $inhibited = [];

        foreach ($competitions as $competition) {
            // Sort by activation strength (higher = stronger)
            usort($competition, function ($a, $b) use ($l5) {
                return $this->getPartialConstructionActivation($b, $l5) <=> $this->getPartialConstructionActivation($a, $l5);
            });

            // Strongest wins, others are inhibited
            $winner = array_shift($competition);

            foreach ($competition as $loser) {
                $inhibited[] = [
                    'partial_construction_id' => $loser->id,
                    'inhibited_by' => $winner->id,
                    'inhibition_amount' => $inhibitionStrength,
                ];
            }
        }

        return [
            'inhibited_count' => count($inhibited),
            'inhibited_partial_constructions' => $inhibited,
        ];
    }

    /**
     * Suppress non-matching partial constructions
     *
     * When input doesn't match partial construction predictions, reduce activation.
     *
     * @param  L5Layer  $l5  The L5 layer
     * @param  array  $matched  Which partial constructions had predictions match
     * @return array Suppression results
     */
    public function suppressNonMatchingPartialConstructions(L5Layer $l5, array $matched): array
    {
        $allPartialConstructions = $l5->getPartialConstructions();
        $suppressionRate = config('cln.activation.decay_rate', 0.9);

        $suppressed = [];

        foreach ($allPartialConstructions as $partialConstruction) {
            // Check if this partial construction had any predictions match
            $hadMatch = false;
            foreach ($matched as $matchedPartialConstruction) {
                if ($matchedPartialConstruction->id === $partialConstruction->id) {
                    $hadMatch = true;
                    break;
                }
            }

            if (! $hadMatch) {
                // Apply decay to non-matching partial construction
                $suppressed[] = [
                    'partial_construction_id' => $partialConstruction->id,
                    'suppression_factor' => 1.0 - $suppressionRate,
                ];
            }
        }

        return [
            'suppressed_count' => count($suppressed),
            'suppressed_partial_constructions' => $suppressed,
        ];
    }

    /**
     * Calculate total activation energy in column
     *
     * Combines L23 and L5 activation levels.
     *
     * @param  L23Layer  $l23  L23 layer
     * @param  L5Layer  $l5  L5 layer
     * @return array Activation statistics
     */
    public function calculateTotalActivation(L23Layer $l23, L5Layer $l5): array
    {
        $l23Activation = $l23->getTotalActivation();
        $l5Activation = $l5->getTotalActivation();

        return [
            'l23_activation' => $l23Activation,
            'l5_activation' => $l5Activation,
            'total_activation' => $l23Activation + $l5Activation,
            'l23_node_count' => count($l23->getAllNodes()),
            'l5_node_count' => count($l5->getAllNodes()),
            'partial_construction_count' => count($l5->getPartialConstructions()),
        ];
    }

    // ========================================================================
    // Private Helpers
    // ========================================================================

    /**
     * Find L23 nodes that match a prediction
     *
     * @param  L23Layer  $l23  The L23 layer
     * @param  \App\Data\CLN\Prediction  $prediction  The prediction
     * @param  object  $token  Current token
     * @return array Matching nodes
     */
    private function findMatchingNodes(L23Layer $l23, $prediction, object $token): array
    {
        $matchingNodes = [];

        // Get nodes of the predicted type
        $nodes = $l23->getNodesByType($prediction->type);

        foreach ($nodes as $node) {
            // Check if node's value matches prediction
            $nodeValue = $node->metadata['value'] ?? null;

            if ($nodeValue !== null && strcasecmp($nodeValue, $prediction->value) === 0) {
                $matchingNodes[] = $node;
            }
        }

        return $matchingNodes;
    }

    /**
     * Find competing constructions in L5
     *
     * Constructions compete if they overlap in position or are mutually exclusive.
     *
     * @param  array  $partialConstructions  Array of partial construction JNodes
     * @return array Array of competing groups
     */
    private function findCompetingConstructions(array $partialConstructions): array
    {
        // For now, simple grouping: all partial constructions at same anchor position compete
        $groups = [];

        foreach ($partialConstructions as $partialConstruction) {
            $anchorPos = $partialConstruction->metadata['anchor_position'] ?? 0;

            if (! isset($groups[$anchorPos])) {
                $groups[$anchorPos] = [];
            }

            $groups[$anchorPos][] = $partialConstruction;
        }

        // Only return groups with multiple partial constructions (actual competition)
        return array_filter($groups, fn ($group) => count($group) > 1);
    }

    /**
     * Get effective activation of a ghost (including boosts)
     *
     * @param  \App\Models\CLN\JNode  $ghost  Ghost node
     * @param  L5Layer  $l5  L5 layer
     * @return float Activation level
     */
    private function getGhostActivation($ghost, L5Layer $l5): float
    {
        $matched = $ghost->metadata['matched'] ?? [];
        $pattern = $ghost->metadata['pattern'] ?? [];

        if (empty($pattern)) {
            return 0.0;
        }

        // Base activation
        $baseActivation = count(array_filter($matched)) / count($pattern);

        // Add boosts
        $boost = $l5->getPartialConstructionBoost($ghost->id);

        return min(1.0, $baseActivation + $boost);
    }
}

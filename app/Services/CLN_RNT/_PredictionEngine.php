<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\Prediction;
use App\Models\CLN\JNode;

/**
 * Prediction Engine
 *
 * Generates predictions from ghost constructions about expected future input.
 * Implements predictive coding: partially activated patterns predict what comes next.
 *
 * Key responsibilities:
 * - Generate predictions from ghost constructions
 * - Calculate prediction strength based on ghost activation and pattern
 * - Merge and prioritize competing predictions
 * - Determine next expected element in pattern
 */
class PredictionEngine
{
    /**
     * Generate predictions from a partial construction
     *
     * Based on current match state, predicts next element in the pattern.
     *
     * @param  JNode  $partial_construction  Partial construction node
     * @param  int  $targetPosition  Target column position for prediction
     * @return array Array of Prediction objects
     */
    public function generatePredictionsFromPartialConstruction(JNode $partial_construction, int $targetPosition): array
    {
        $pattern = $partial_construction->metadata['pattern'] ?? [];
        $matched = $partial_construction->metadata['matched'] ?? [];
        $constructionId = $partial_construction->metadata['construction_id'] ?? 0;
        $sourcePosition = $partial_construction->metadata['anchor_position'] ?? 0;

        if (empty($pattern)) {
            return [];
        }

        // Find next unmatched element
        $nextIndex = $this->getNextUnmatchedIndex($matched);

        if ($nextIndex === null || ! isset($pattern[$nextIndex])) {
            return [];
        }

        // Calculate prediction strength
        $strength = $this->calculatePredictionStrength($partial_construction);

        // Get next element
        $nextElement = $pattern[$nextIndex];

        // Create prediction
        return [
            new Prediction(
                sourcePosition: $sourcePosition,
                targetPosition: $targetPosition,
                type: $this->determineElementType($nextElement),
                value: $nextElement,
                strength: $strength,
                constructionId: $constructionId,
                metadata: [
                    'partial_construction_id' => $partial_construction->id,
                    'pattern_index' => $nextIndex,
                    'pattern' => $pattern,
                    'matched_count' => count(array_filter($matched)),
                    'total_count' => count($pattern),
                ]
            ),
        ];
    }

    /**
     * Generate predictions from pattern (when no ghost exists yet)
     *
     * @param  array  $pattern  Pattern elements
     * @param  int  $currentPos  Current position in sequence
     * @param  int  $constructionId  Construction ID
     * @return array Array of Prediction objects
     */
    public function generatePredictionsFromPattern(
        array $pattern,
        int $currentPos,
        int $constructionId
    ): array {
        if (empty($pattern)) {
            return [];
        }

        // Predict first element at next position
        $nextPosition = $currentPos + 1;
        $firstElement = $pattern[0];

        return [
            new Prediction(
                sourcePosition: $currentPos,
                targetPosition: $nextPosition,
                type: $this->determineElementType($firstElement),
                value: $firstElement,
                strength: 0.5, // Initial prediction has moderate strength
                constructionId: $constructionId,
                metadata: [
                    'pattern_index' => 0,
                    'pattern' => $pattern,
                ]
            ),
        ];
    }

    /**
     * Get next expected element from pattern
     *
     * Returns the next unmatched element and its index.
     *
     * @param  array  $pattern  Pattern elements
     * @param  array  $matched  Boolean array of matched elements
     * @return array|null ['index' => int, 'element' => string] or null
     */
    public function getNextExpectedElement(array $pattern, array $matched): ?array
    {
        $nextIndex = $this->getNextUnmatchedIndex($matched);

        if ($nextIndex === null || ! isset($pattern[$nextIndex])) {
            return null;
        }

        return [
            'index' => $nextIndex,
            'element' => $pattern[$nextIndex],
            'type' => $this->determineElementType($pattern[$nextIndex]),
        ];
    }

    /**
     * Calculate prediction strength from partial-construction activation
     *
     * Formula: strength = partial-construction_activation * transition_probability * (1 - distance_decay)
     *
     * Components:
     * - partial-construction_activation: proportion of pattern matched (matched_elements / total_elements)
     * - transition_probability: likelihood of next element (currently 1.0)
     * - distance_decay: reduces strength for distant predictions (currently 0)
     *
     * @param  JNode  $partial_construction  Partial construction node
     * @return float Strength value (0-1)
     */
    public function calculatePredictionStrength(JNode $partial_construction): float
    {
        $matched = $partial_construction->metadata['matched'] ?? [];
        $pattern = $partial_construction->metadata['pattern'] ?? [];

        if (empty($pattern)) {
            return 0.0;
        }

        // $partial_construction activation = proportion matched
        $matchedCount = count(array_filter($matched));
        $totalCount = count($pattern);
        $partialConstructionActivation = $matchedCount / $totalCount;

        // Transition probability (TODO: could be learned from corpus)
        $transitionProbability = 1.0;

        // Distance decay (TODO: could decay for longer-range predictions)
        $distanceDecay = 0.0;

        // Combined strength
        $strength = $partialConstructionActivation * $transitionProbability * (1 - $distanceDecay);

        return min(1.0, max(0.0, $strength));
    }

    /**
     * Merge multiple predictions for same target
     *
     * When multiple ghosts predict the same element at the same position,
     * combine their predictions (take the strongest).
     *
     * @param  array  $predictions  Array of Prediction objects
     * @return array Merged predictions (one per unique target+type+value)
     */
    public function mergePredictions(array $predictions): array
    {
        $merged = [];

        foreach ($predictions as $prediction) {
            $key = sprintf(
                '%d:%s:%s',
                $prediction->targetPosition,
                $prediction->type,
                $prediction->value
            );

            // Keep strongest prediction for each unique target
            if (! isset($merged[$key]) || $prediction->strength > $merged[$key]->strength) {
                $merged[$key] = $prediction;
            }
        }

        return array_values($merged);
    }

    /**
     * Prioritize predictions by strength
     *
     * Sort predictions by strength (descending) and optionally limit count.
     *
     * @param  array  $predictions  Array of Prediction objects
     * @param  int|null  $limit  Optional limit on number of predictions
     * @return array Sorted predictions
     */
    public function prioritizePredictions(array $predictions, ?int $limit = null): array
    {
        // Sort by strength descending
        usort($predictions, fn ($a, $b) => $b->strength <=> $a->strength);

        if ($limit !== null && $limit > 0) {
            return array_slice($predictions, 0, $limit);
        }

        return $predictions;
    }

    /**
     * Apply boost factor to prediction strength
     *
     * When a ghost receives confirmation boost, its predictions become stronger.
     *
     * @param  float  $baseStrength  Base prediction strength
     * @param  float  $boost  Boost amount from confirmations
     * @return float Boosted strength (capped at 1.0)
     */
    public function applyBoostToPrediction(float $baseStrength, float $boost): float
    {
        $boostFactor = config('cln.activation.boost_factor', 0.3);

        return min(1.0, $baseStrength + ($boost * $boostFactor));
    }

    // ========================================================================
    // Private Helpers
    // ========================================================================

    /**
     * Get index of next unmatched element in pattern
     *
     * @param  array  $matched  Boolean array of matched elements
     * @return int|null Index of next unmatched, or null if all matched
     */
    private function getNextUnmatchedIndex(array $matched): ?int
    {
        foreach ($matched as $index => $isMatched) {
            if (! $isMatched) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Determine element type from pattern element
     *
     * Heuristics:
     * - ALL_CAPS (with underscores) = POS tag (e.g., "NOUN", "VERB")
     * - Contains "=" = feature (e.g., "Gender=Masc")
     * - Otherwise = word (e.g., "café", "da")
     *
     * @param  string  $element  Pattern element
     * @return string Type ('word', 'pos', 'feature')
     */
    private function determineElementType(string $element): string
    {
        // Check for feature (contains =)
        if (str_contains($element, '=')) {
            return 'feature';
        }

        // Check for POS tag (all caps, allowing underscores)
        if (ctype_upper(str_replace('_', '', $element))) {
            return 'pos';
        }

        // Default to word
        return 'word';
    }
}

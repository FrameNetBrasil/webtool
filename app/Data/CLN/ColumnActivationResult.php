<?php

namespace App\Data\CLN;

/**
 * Column Activation Result
 *
 * Contains the result of processing an input token through a CLN column.
 * Captures all activations, predictions, and confirmations that occurred.
 *
 * This data structure is returned by CLNColumn::processInput() and provides
 * a complete picture of what happened during column processing.
 */
class ColumnActivationResult
{
    /**
     * Create a new Column Activation Result
     *
     * @param  int  $position  Column position in sequence
     * @param  bool  $hasPredictionMatch  Whether any predictions matched input
     * @param  array  $matchedPredictions  Array of Prediction objects that matched
     * @param  array  $activatedL23Nodes  Array of all activated L23 nodes (word, lemma, pos, features)
     * @param  array  $activatedFeatures  Array of activated feature nodes (deprecated, use activatedL23Nodes)
     * @param  array  $activatedPartialConstructions  Array of partial constructions activated
     * @param  array  $confirmedConstructions  Array of fully confirmed constructions
     * @param  array  $generatedPredictions  Array of predictions generated for next column
     * @param  float  $totalActivation  Total activation level (L23 + L5)
     */
    public function __construct(
        public int $position,
        public bool $hasPredictionMatch,
        public array $matchedPredictions,
        public array $activatedL23Nodes,
        public array $activatedFeatures,
        public array $activatedPartialConstructions,
        public array $confirmedConstructions,
        public array $generatedPredictions,
        public float $totalActivation
    ) {}

    /**
     * Convert to array representation
     *
     * @return array Result as array
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'has_prediction_match' => $this->hasPredictionMatch,
            'matched_predictions_count' => count($this->matchedPredictions),
            'activated_l23_nodes_count' => count($this->activatedL23Nodes),
            'activated_features_count' => count($this->activatedFeatures),
            'activated_partial_constructions_count' => count($this->activatedPartialConstructions),
            'confirmed_constructions_count' => count($this->confirmedConstructions),
            'generated_predictions_count' => count($this->generatedPredictions),
            'total_activation' => $this->totalActivation,
            'matched_predictions' => array_map(
                fn ($p) => $p->toArray(),
                $this->matchedPredictions
            ),
            'activated_l23_nodes' => array_map(
                fn ($n) => [
                    'id' => $n->id,
                    'type' => $n instanceof \App\Models\CLN\JNode ? 'JNode' : 'BNode',
                    'node_type' => $n->metadata['node_type'] ?? null,
                    'value' => $n->metadata['value'] ?? null,
                    'feature' => $n->metadata['feature'] ?? null,
                    'activated' => ($n instanceof \App\Models\CLN\JNode) ? $n->isFired() : $n->isActivated(),
                ],
                $this->activatedL23Nodes
            ),
            'activated_partial_constructions' => array_map(
                fn ($p) => [
                    'id' => $p->id,
                    'construction_id' => $p->metadata['construction_id'] ?? null,
                    'name' => $p->metadata['name'] ?? null,
                ],
                $this->activatedPartialConstructions
            ),
            'confirmed_constructions' => array_map(
                fn ($c) => [
                    'id' => $c->id,
                    'construction_id' => $c->metadata['construction_id'] ?? null,
                    'name' => $c->metadata['name'] ?? null,
                ],
                $this->confirmedConstructions
            ),
            'generated_predictions' => array_map(
                fn ($p) => $p->toArray(),
                $this->generatedPredictions
            ),
        ];
    }

    /**
     * Check if column had any significant activity
     *
     * @return bool True if predictions matched, partial constructions activated, or constructions confirmed
     */
    public function hasActivity(): bool
    {
        return $this->hasPredictionMatch
            || count($this->activatedPartialConstructions) > 0
            || count($this->confirmedConstructions) > 0;
    }

    /**
     * Get summary string
     *
     * @return string Human-readable summary
     */
    public function getSummary(): string
    {
        $parts = [];

        if ($this->hasPredictionMatch) {
            $parts[] = sprintf('%d predictions matched', count($this->matchedPredictions));
        }

        if (count($this->activatedPartialConstructions) > 0) {
            $parts[] = sprintf('%d partial constructions activated', count($this->activatedPartialConstructions));
        }

        if (count($this->confirmedConstructions) > 0) {
            $parts[] = sprintf('%d constructions confirmed', count($this->confirmedConstructions));
        }

        if (count($this->generatedPredictions) > 0) {
            $parts[] = sprintf('%d predictions generated', count($this->generatedPredictions));
        }

        if (empty($parts)) {
            return sprintf('Position %d: basic activation only', $this->position);
        }

        return sprintf('Position %d: %s', $this->position, implode(', ', $parts));
    }
}

<?php

namespace App\Data\CLN;

use App\Models\CLN_RNT\L5Layer;

/**
 * Confirmation
 *
 * Represents lateral confirmation sent from L23 to L23 (UNIDIRECTIONAL: posterior → anterior).
 * When predictions match actual input, the posterior column sends confirmation back
 * to boost the construction in the anterior column's L5 layer.
 *
 * Flow: L23[col N+1] matches → Confirmation → L23[col N] → L5[col N] boost
 *
 * This implements the predictive coding loop:
 * 1. L5[N] ghost predicts element at position N+1
 * 2. L23[N+1] receives and matches prediction
 * 3. L23[N+1] sends confirmation BACK to L23[N]
 * 4. L23[N] forwards confirmation to L5[N]
 * 5. L5[N] boosts the ghost construction
 */
class Confirmation
{
    /**
     * Create a new Confirmation
     *
     * @param  int  $sourcePosition  Column sending confirmation (posterior)
     * @param  int  $targetPosition  Column receiving confirmation (anterior)
     * @param  string  $matchedFeature  What matched (word, pos, feature value)
     * @param  float  $strength  Confirmation strength (0-1)
     * @param  int  $constructionId  ID of construction being confirmed
     * @param  array  $metadata  Additional metadata
     */
    public function __construct(
        public int $sourcePosition,
        public int $targetPosition,
        public string $matchedFeature,
        public float $strength,
        public int $constructionId,
        public array $metadata = []
    ) {}

    /**
     * Apply confirmation boost to L5 layer
     *
     * Increases activation of the ghost construction that made the correct prediction.
     * This reinforces the construction hypothesis.
     *
     * @param  L5Layer  $l5  The L5 layer to boost
     */
    public function apply(L5Layer $l5): void
    {
        // Find ghost construction with matching ID
        $ghosts = $l5->getPartialConstructions();

        foreach ($ghosts as $ghost) {
            if (($ghost->metadata['construction_id'] ?? null) === $this->constructionId) {
                // Apply boost - this will be handled by L5Layer's boost mechanism
                // The strength determines how much to boost the ghost's activation
                $l5->boostPartialConstruction($ghost->id, $this->strength);
            }
        }
    }

    /**
     * Convert confirmation to array
     *
     * @return array Confirmation as array
     */
    public function toArray(): array
    {
        return [
            'source_position' => $this->sourcePosition,
            'target_position' => $this->targetPosition,
            'matched_feature' => $this->matchedFeature,
            'strength' => $this->strength,
            'construction_id' => $this->constructionId,
            'metadata' => $this->metadata,
        ];
    }
}

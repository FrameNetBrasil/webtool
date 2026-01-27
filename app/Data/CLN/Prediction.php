<?php

namespace App\Data\CLN;

/**
 * Prediction
 *
 * Represents a prediction from L5 to L23 about expected input elements.
 *
 * NEW FLOW (Same-Column Prediction):
 * Predictions now create actual predicted nodes in L23 at the SAME column where
 * the partial construction exists. When a token arrives at the NEXT column,
 * backward compatibility checking activates matching predicted nodes from the
 * PREVIOUS column.
 *
 * When a partial construction in L5 partially matches, it predicts what should
 * come next based on the construction pattern. If the prediction matches
 * actual input in the next position, it confirms the construction hypothesis.
 */
class Prediction
{
    /**
     * Create a new Prediction
     *
     * @param  int  $sourcePosition  Column position where prediction originates
     * @param  int  $targetPosition  Column position where predicted node is created (now same as source)
     * @param  string  $type  Type of prediction (word, pos, feature, construction)
     * @param  string  $value  Expected value (e.g., "café", "NOUN", "Gender=Masc", "HEAD")
     * @param  float  $strength  Prediction strength (0-1)
     * @param  int  $constructionId  ID of construction making the prediction
     * @param  array  $metadata  Additional metadata
     * @param  array  $nodeMetadata  Full node metadata for creating predicted node in L23
     */
    public function __construct(
        public int $sourcePosition,
        public int $targetPosition,
        public string $type,
        public string $value,
        public float $strength,
        public int $constructionId,
        public array $metadata = [],
        public array $nodeMetadata = []
    ) {}

    /**
     * Check if prediction matches a token
     *
     * @param  object  $token  UDPipe token
     * @return bool True if prediction matches
     */
    public function matches(object $token): bool
    {
        return match ($this->type) {
            'word' => strcasecmp($token->form ?? '', $this->value) === 0,
            'pos' => strcasecmp($token->upos ?? '', $this->value) === 0,
            'feature' => $this->featureMatches($token),
            default => false,
        };
    }

    /**
     * Check if token has the predicted feature
     *
     * @param  object  $token  UDPipe token
     * @return bool True if feature matches
     */
    private function featureMatches(object $token): bool
    {
        if (empty($token->feats)) {
            return false;
        }

        $features = $this->parseFeatures($token->feats);

        if (! str_contains($this->value, '=')) {
            return false;
        }

        [$name, $value] = explode('=', $this->value, 2);

        return isset($features[$name]) && strcasecmp($features[$name], $value) === 0;
    }

    /**
     * Parse UDPipe feature string
     *
     * @param  string  $featsString  Features in format "Gender=Masc|Number=Sing"
     * @return array Associative array
     */
    private function parseFeatures(string $featsString): array
    {
        if (empty($featsString) || $featsString === '_') {
            return [];
        }

        $features = [];
        foreach (explode('|', $featsString) as $feat) {
            if (str_contains($feat, '=')) {
                [$name, $value] = explode('=', $feat, 2);
                $features[$name] = $value;
            }
        }

        return $features;
    }

    /**
     * Calculate boost to apply if prediction matches
     *
     * The boost is proportional to prediction strength.
     * Strong predictions (from fully activated ghosts) provide stronger boosts.
     *
     * @param  object  $token  The matching token
     * @return float Boost value (0-1)
     */
    public function calculateBoost(object $token): float
    {
        if (! $this->matches($token)) {
            return 0.0;
        }

        // Base boost is the prediction strength
        // Can be modified by config boost_factor later
        return $this->strength;
    }

    /**
     * Convert prediction to array
     *
     * @return array Prediction as array
     */
    public function toArray(): array
    {
        return [
            'source_position' => $this->sourcePosition,
            'target_position' => $this->targetPosition,
            'type' => $this->type,
            'value' => $this->value,
            'strength' => $this->strength,
            'construction_id' => $this->constructionId,
            'metadata' => $this->metadata,
            'node_metadata' => $this->nodeMetadata,
        ];
    }
}

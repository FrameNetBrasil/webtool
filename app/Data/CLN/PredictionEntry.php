<?php

namespace App\Data\CLN;

/**
 * Prediction Entry
 *
 * Represents a prediction registered by an L5 partial construction,
 * stored in the ColumnSequenceManager's prediction registry.
 *
 * Predictions are indexed by construction name and stored in stacks (LIFO),
 * allowing the most recent (innermost) predictions to be matched first.
 */
class PredictionEntry
{
    /**
     * Create a new Prediction Entry
     *
     * @param  string  $constructionName  Construction name (index key, e.g., "HEAD", "ARG")
     * @param  int  $sourceColumn  Column position where L5 partial lives
     * @param  string  $type  Prediction type: 'word', 'pos', 'feature', 'construction'
     * @param  string  $value  Expected value
     * @param  float  $strength  Prediction strength (0-1)
     * @param  string  $sourcePartialId  L5 partial construction node ID
     * @param  int  $constructionId  Database construction ID
     * @param  array  $metadata  Additional metadata
     * @param  float  $createdAt  Timestamp for TTL cleanup
     */
    public function __construct(
        public string $constructionName,
        public int $sourceColumn,
        public string $type,
        public string $value,
        public float $strength,
        public string $sourcePartialId,
        public int $constructionId,
        public array $metadata = [],
        public float $createdAt = 0.0
    ) {
        // Set creation timestamp if not provided
        if ($this->createdAt === 0.0) {
            $this->createdAt = microtime(true);
        }
    }

    /**
     * Convert to array for debugging and serialization
     */
    public function toArray(): array
    {
        return [
            'constructionName' => $this->constructionName,
            'sourceColumn' => $this->sourceColumn,
            'type' => $this->type,
            'value' => $this->value,
            'strength' => $this->strength,
            'sourcePartialId' => $this->sourcePartialId,
            'constructionId' => $this->constructionId,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt,
            'age' => microtime(true) - $this->createdAt,
        ];
    }

    /**
     * Check if prediction is expired based on TTL
     *
     * @param  float  $ttl  Time-to-live in seconds
     */
    public function isExpired(float $ttl): bool
    {
        return (microtime(true) - $this->createdAt) > $ttl;
    }
}

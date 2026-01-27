<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Node;

/**
 * Represents a forward-looking expectation
 * This is the "waiting slot" that persists across timesteps
 */
class Prediction {
    public Node $source;
    public Node $predictor;         // Who made the prediction
    public Node $predicted;         // What's predicted (usually a role)
    public float $strength;         // Prediction strength
    public int $createdAt;          // When created
    public int $expiresAt;          // When it expires
    public bool $fulfilled = false; // Has it been satisfied?
    public ?Binding $filledBy = null; // What filled it

    public function __construct(
        Node $source,
        Node $predictor,
        Node $predicted,
        float $strength,
        int $createdAt,
        int $expiresAt
    ) {
        $this->source = $source;
        $this->predictor = $predictor;
        $this->predicted = $predicted;
        $this->strength = $strength;
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
    }

    public function fulfill(Binding $binding): void {
        $this->fulfilled = true;
        $this->filledBy = $binding;
    }

    public function isActive(int $currentTime): bool {
        return !$this->fulfilled &&
            $currentTime >= $this->createdAt &&
            $currentTime <= $this->expiresAt;
    }

    public function getAge(int $currentTime): int {
        return $currentTime - $this->createdAt;
    }
}

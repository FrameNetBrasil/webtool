<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Node;

/**
 * Represents an established connection in a AND node (left + right))
 * This is the working memory representation of conjunction neuron firing
 */
class Binding {
    public ?Node $left;
    public Node $head;
    public ?Node $right;
    public float $strength;         // Binding strength (0-1)
    public array $leftWindow;     // Filler's activation timeline
    public array $headWindow;       // Role's activation timeline
    public array $rightWindow;

    public int $leftTime;
    public int $headTime;
    public int $rightTime;
    public int $boundAt;            // When binding occurred

    // Metadata for querying
    public ?int $leftPeakTime = null;
    public ?float $leftPeakLevel = null;

    public ?int $rightPeakTime = null;
    public ?float $rightPeakLevel = null;

    public bool $activated;

    public function __construct(
        ?Node $left,
        Node $head,
        ?Node $right,
        float $strength,
        int $leftTime,
        int $headTime,
        int $rightTime,
        int $boundAt
    ) {
        $this->left = $left;
        $this->head = $head;
        $this->right = $right;
        $this->strength = $strength;
        $this->leftTime = $leftTime;
        $this->headTime = $headTime;
        $this->rightTime = $rightTime;
//        $this->leftWindow = $leftWindow;
//        $this->headWindow = $headWindow;
//        $this->rightWindow = $rightWindow;
        $this->boundAt = $boundAt;
        $this->activated = false;

        // Compute peak for convenience
        if (!empty($leftWindow)) {
            $this->leftPeakTime = array_keys($leftWindow, max($leftWindow))[0];
            $this->leftPeakLevel = max($leftWindow);
        }
    }

    /**
     * Get temporal distance between filler and role peak activations
     */
    public function getTemporalDistance(): int {
        $fillerPeak = $this->fillerPeakTime ?? $this->boundAt;
        $rolePeak = !empty($this->roleWindow)
            ? array_keys($this->roleWindow, max($this->roleWindow))[0]
            : $this->boundAt;

        return abs($fillerPeak - $rolePeak);
    }

    /**
     * Check if this is backward binding (filler before role)
     */
    public function isBackwardBinding(): bool {
        return $this->leftPeakTime < $this->boundAt;
    }

    /**
     * Check if this is forward binding (role waiting for filler)
     */
    public function isForwardBinding(): bool {
        return $this->rightPeakTime >= $this->boundAt;
    }

    public function updateRight(Node $right, int $rightTime): void {
        $this->right = $right;
        $this->rightTime = $right->time;
        $this->activated = $this->left && $this->right;
        //$this->boundAt = $rightTime;
        // Compute peak for convenience
//        if (!empty($rightWindow)) {
//            $this->rightPeakTime = array_keys($rightWindow, max($rightWindow))[0];
//            $this->rightPeakLevel = max($rightWindow);
//        }

    }

    public function updateLeft(Node $left, int $leftTime): void {
        $this->left = $left;
        $this->leftTime = $left->time;
        $this->activated =  $this->left && $this->right;
        //$this->boundAt = $leftTime;
        // Compute peak for convenience
//        if (!empty($rightWindow)) {
//            $this->rightPeakTime = array_keys($rightWindow, max($rightWindow))[0];
//            $this->rightPeakLevel = max($rightWindow);
//        }

    }

    /**
     * Get human-readable description
     */
    public function describe(): string {
        $direction = $this->isBackwardBinding() ? 'backward' : 'forward';
        $distance = $this->getTemporalDistance();

        return sprintf(
            "%s → %s [strength=%.2f, %s, distance=%d timesteps]",
            $this->filler->label,
            $this->role->label,
            $this->strength,
            $direction,
            $distance
        );
    }
}

<?php

namespace App\Models\CLN;

/**
 * A connection between two columns through specific layers.
 */
class Pathway {
    public FunctionalColumn $source;
    public FunctionalColumn $target;
    public string $sourceLayer;         // 'L5', 'L23', etc.
    public string $targetLayer;
    public PathwayDirection $direction;
    public float $weight = 1.0;         // Connection strength

    // Gating: this pathway may be blocked by an inhibitor
    public ?SOM_Inhibitor $gatingInhibitor = null;

    public function __construct(
        FunctionalColumn $source,
        FunctionalColumn $target,
        string $sourceLayer,
        string $targetLayer,
        PathwayDirection $direction
    ) {
        $this->source = $source;
        $this->target = $target;
        $this->sourceLayer = $sourceLayer;
        $this->targetLayer = $targetLayer;
        $this->direction = $direction;
    }

    public function isBlocked(): bool {
        if ($this->gatingInhibitor === null) {
            return false;
        }
        return $this->gatingInhibitor->isActive();
    }

    public function propagate(float $activation): void {
        if (!$this->isBlocked()) {
            $this->target->L4->activation += $activation;
        }
        // If blocked, activation doesn't flow through
    }
}

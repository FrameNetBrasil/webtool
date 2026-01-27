<?php

namespace App\Models\CLN;

/**
 * Central parsing state manager.
 * Tracks time, activations, pending constructions, and completed bindings.
 */
class ParsingState {
    public int $currentTime = 0;
    public int $windowSize = 5;           // Temporal history window

    // Activation thresholds
    public float $activationThreshold = 0.3;
    public float $bindingThreshold = 0.3;
    public float $decayRate = 0.2;        // Per-timestep decay

    // Track what's in progress and what's completed
    /** @var PendingConstruction[] */
    public array $pendingConstructions = [];

    /** @var Binding[] */
    public array $completedBindings = [];

    // Currently active columns (activation above threshold)
    /** @var FunctionalColumn[] */
    public array $activeColumns = [];

    public function advanceTime(): void {
        $this->currentTime++;
    }

    public function reset(): void {
        $this->currentTime = 0;
        $this->pendingConstructions = [];
        $this->completedBindings = [];
        $this->activeColumns = [];
    }
}

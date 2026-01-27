<?php

namespace App\Models\CLN;

/**
 * State of a single layer within a functional column.
 */
class LayerState
{
    public float $activation = 0.0;       // Current activation level -  0.0 or 1.0 (binary)
    public array $temporalHistory = [];  // [timestep => activation] for binding
    public float $baseline = 0.0;         // Tonic activity level
    public bool $predictedFlag = false;     // Was this layer's input predicted?
    public float $expectedActivation = 0.0; // 0.0 or 1.0 (binary)

    // for L6a
    public ?ReferenceFrame $activeFrame = null;
    public int $positionInFrame = 0;

    public function __construct() {
        $this->activation = 0.0;
        $this->temporalHistory = [];
        $this->baseline = 0.0;
        $this->predictedFlag = false;
        $this->expectedActivation = 0.0;
    }

    public function reset(): void {
        $this->activation = 0.0;
        $this->expectedActivation = 0.0;
        $this->temporalHistory = [];
    }
    public function computeError(): float
    {
        // Returns: 1 (surprise), 0 (match), -1 (omission)
        return $this->activation - $this->expectedActivation;
    }
}

<?php

namespace App\Models\CLN;

/**
 * Represents a binding between a filler and a slot in a construction.
 */
class Binding {
    public FunctionalColumn $filler;
    public FunctionalColumn $slot;       // The AND column where binding occurs
    public string $role;                  // 'left' or 'right'
    public float $strength;
    public int $boundAtTime;

    public function __construct(
        FunctionalColumn $filler,
        FunctionalColumn $slot,
        string $role,
        float $strength,
        int $boundAtTime
    ) {
        $this->filler = $filler;
        $this->slot = $slot;
        $this->role = $role;
        $this->strength = $strength;
        $this->boundAtTime = $boundAtTime;
    }
}

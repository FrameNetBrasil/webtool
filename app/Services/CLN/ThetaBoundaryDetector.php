<?php

namespace App\Services\CLN;

class ThetaBoundaryDetector
{
    public int $gammaCyclesSinceStart = 0;
    const MAX_GAMMA_CYCLES = 4;  // Timeout threshold

    public function checkBoundary(FunctionalColumn $input, SOM_Inhibitor $som): bool
    {
        $this->gammaCyclesSinceStart++;

        // TRIGGER 1: Unexpected input (content-driven)
        if (!$som->expectsType($input->type)) {
            return true;  // Immediate boundary!
        }

        // TRIGGER 2: Timeout (time-driven)
        if ($this->gammaCyclesSinceStart >= self::MAX_GAMMA_CYCLES) {
            return true;  // Maximum duration reached
        }

        return false;  // Continue accumulating
    }
}

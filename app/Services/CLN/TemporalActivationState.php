<?php

namespace App\Services\CLN;

class TemporalActivationState
{
    public int $gammaCycle = 0;           // Increments each word
    public int $thetaCycle = 0;           // Increments each ~8 gamma cycles

    const GAMMA_PER_THETA = 8;     // ~200ms / 25ms

    public function advanceGamma(): void
    {
        $this->gammaCycle++;

        // Update L4 and L2/3 on each gamma cycle
        $this->updateInputLayers();
        $this->computePredictionErrors();

        // Update L5 and L6 on theta boundaries
        if ($this->gammaCycle % self::GAMMA_PER_THETA === 0) {
            $this->thetaCycle++;
            $this->updateRepresentationLayers();
            $this->updatePositionLayers();
        }
    }
}

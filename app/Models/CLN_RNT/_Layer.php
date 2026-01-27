<?php

namespace App\Models\CLN_RNT;

/**
 * CLN Layer Enumeration
 *
 * Represents the two-layer architecture of the Cortical Language Network:
 * - L23: Input layer (superficial cortical layers 2/3)
 * - L5: Output layer (deep cortical layer 5)
 */
enum Layer: string
{
    case L23 = 'L23';  // Input layer: Word, Feature, Pattern nodes
    case L5 = 'L5';    // Output layer: Lemma, Construction nodes

    /**
     * Get human-readable description of the layer
     */
    public function description(): string
    {
        return match ($this) {
            self::L23 => 'Input Layer (L2/3) - Word, Feature, Pattern nodes',
            self::L5 => 'Output Layer (L5) - Lemma, Construction nodes',
        };
    }

    /**
     * Check if this is the input layer
     */
    public function isInputLayer(): bool
    {
        return $this === self::L23;
    }

    /**
     * Check if this is the output layer
     */
    public function isOutputLayer(): bool
    {
        return $this === self::L5;
    }
}

<?php

namespace App\Models\CLN_RNT;

/**
 * Column State Enum
 *
 * Represents the processing state of a cortical column in the CLN architecture.
 *
 * State transitions:
 * EMPTY → PREDICTED → ACTIVATED → CONFIRMED
 *
 * - EMPTY: Column initialized but no input or predictions received
 * - PREDICTED: Column has received predictions from previous column but no input yet
 * - ACTIVATED: Column has received and processed input (L23 activated, L5 processing)
 * - CONFIRMED: Column's predictions were confirmed by next column (lateral confirmation received)
 */
enum ColumnState: string
{
    /**
     * Initial state - no input or predictions
     */
    case EMPTY = 'empty';

    /**
     * Has predictions but no input yet
     */
    case PREDICTED = 'predicted';

    /**
     * Input received and processed
     */
    case ACTIVATED = 'activated';

    /**
     * Predictions confirmed by posterior column
     */
    case CONFIRMED = 'confirmed';

    /**
     * Check if column is ready to receive input
     */
    public function canReceiveInput(): bool
    {
        return match ($this) {
            self::EMPTY, self::PREDICTED => true,
            self::ACTIVATED, self::CONFIRMED => false,
        };
    }

    /**
     * Check if column has been activated
     */
    public function isActivated(): bool
    {
        return match ($this) {
            self::ACTIVATED, self::CONFIRMED => true,
            self::EMPTY, self::PREDICTED => false,
        };
    }

    /**
     * Check if column has predictions
     */
    public function hasPredictions(): bool
    {
        return $this === self::PREDICTED;
    }

    /**
     * Check if column was confirmed
     */
    public function isConfirmed(): bool
    {
        return $this === self::CONFIRMED;
    }
}

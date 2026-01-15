<?php

namespace App\Enums\Parser;

/**
 * Pattern Type Classification
 *
 * Distinguishes between Simple MWEs, Variable MWEs, and BNF Constructions
 * for unified pattern management interface.
 */
enum PatternType: string
{
    case SIMPLE_MWE = 'simple_mwe';
    case VARIABLE_MWE = 'variable_mwe';
    case CONSTRUCTION = 'construction';

    /**
     * Get Fomantic UI icon class for this pattern type
     */
    public function icon(): string
    {
        return match ($this) {
            self::SIMPLE_MWE => 'chain icon',
            self::VARIABLE_MWE => 'random icon',
            self::CONSTRUCTION => 'cogs icon',
        };
    }

    /**
     * Get human-readable label for this pattern type
     */
    public function label(): string
    {
        return match ($this) {
            self::SIMPLE_MWE => 'Simple MWE',
            self::VARIABLE_MWE => 'Variable MWE',
            self::CONSTRUCTION => 'BNF Construction',
        };
    }

    /**
     * Get Fomantic UI color for this pattern type
     */
    public function color(): string
    {
        return match ($this) {
            self::SIMPLE_MWE => 'green',
            self::VARIABLE_MWE => 'purple',
            self::CONSTRUCTION => 'blue',
        };
    }

    /**
     * Get description of this pattern type
     */
    public function description(): string
    {
        return match ($this) {
            self::SIMPLE_MWE => 'Fixed word sequences without variables (e.g., "a fim de", "por outro lado")',
            self::VARIABLE_MWE => 'Patterns with POS slots, lemmas, or wildcards (e.g., [{"type":"P","value":"NOUN"}])',
            self::CONSTRUCTION => 'BNF patterns with optional elements, alternatives, and repetition (e.g., [{NUM}] mil)',
        };
    }
}

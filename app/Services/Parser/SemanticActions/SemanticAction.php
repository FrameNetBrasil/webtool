<?php

namespace App\Services\Parser\SemanticActions;

use App\Data\Parser\ConstructionMatch;

/**
 * Semantic Action Interface
 *
 * Semantic actions compute values from matched construction patterns.
 * Examples: converting number words to integers, parsing dates, extracting named entities.
 */
interface SemanticAction
{
    /**
     * Get the action name
     * Used to reference this action in construction semantics
     */
    public function getName(): string;

    /**
     * Calculate semantic value from match
     *
     * @param  ConstructionMatch  $match  The matched construction
     * @param  array  $semantics  Semantics configuration from construction
     * @return mixed Calculated value (int, string, array, etc.)
     */
    public function calculate(ConstructionMatch $match, array $semantics): mixed;

    /**
     * Derive additional features from semantic value
     *
     * @param  mixed  $value  Calculated semantic value
     * @return array Additional morphological features
     */
    public function deriveFeatures(mixed $value): array;

    /**
     * Validate semantics configuration
     *
     * @param  array  $semantics  Semantics config to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSemantics(array $semantics): array;
}

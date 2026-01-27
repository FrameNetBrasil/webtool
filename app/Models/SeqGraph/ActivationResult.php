<?php

namespace App\Models\SeqGraph;

/**
 * Result object returned by the activation engine after processing input.
 *
 * Encapsulates the outcome of activating sequence graphs with an input element,
 * including which nodes fired, which patterns completed, and what new listeners
 * were activated.
 */
class ActivationResult
{
    /**
     * Nodes that fired during this activation.
     *
     * Array of [patternName, nodeId] pairs identifying nodes that
     * were triggered by the input element.
     *
     * @var array<array{0: string, 1: string}>
     */
    public array $firedNodes;

    /**
     * Patterns that completed during this activation.
     *
     * Array of pattern names whose end nodes were reached, indicating
     * the pattern fully matched the input sequence up to this point.
     *
     * @var array<string>
     */
    public array $completedPatterns;

    /**
     * New listener nodes activated during this activation.
     *
     * Array of [patternName, nodeId] pairs identifying element nodes
     * that became active and are now waiting for future input.
     *
     * @var array<array{0: string, 1: string}>
     */
    public array $newListeners;

    /**
     * Create a new activation result.
     *
     * @param  array<array{0: string, 1: string}>  $firedNodes  Nodes that fired
     * @param  array<string>  $completedPatterns  Patterns that completed
     * @param  array<array{0: string, 1: string}>  $newListeners  New active listeners
     */
    public function __construct(
        array $firedNodes = [],
        array $completedPatterns = [],
        array $newListeners = []
    ) {
        $this->firedNodes = $firedNodes;
        $this->completedPatterns = $completedPatterns;
        $this->newListeners = $newListeners;
    }
}

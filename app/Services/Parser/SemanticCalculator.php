<?php

namespace App\Services\Parser;

use App\Data\Parser\ConstructionMatch;
use App\Services\Parser\SemanticActions\SemanticAction;

/**
 * Semantic Calculator Service
 *
 * Orchestrates semantic actions to compute values from construction matches.
 * Maintains registry of available semantic actions.
 */
class SemanticCalculator
{
    /** @var array<string, SemanticAction> */
    private array $actions = [];

    public function __construct()
    {
        // Register default actions
        $this->registerDefaultActions();
    }

    /**
     * Register a semantic action
     */
    public function register(SemanticAction $action): void
    {
        $this->actions[$action->getName()] = $action;
    }

    /**
     * Calculate semantic value for a match
     *
     * @param  ConstructionMatch  $match  The construction match
     * @param  array|null  $semantics  Semantics configuration from construction
     * @return ConstructionMatch Updated match with semantic value and features
     */
    public function calculate(ConstructionMatch $match, ?array $semantics): ConstructionMatch
    {
        if (empty($semantics)) {
            return $match;
        }

        // Get semantic type/method
        $type = $semantics['type'] ?? null;
        $method = $semantics['method'] ?? $type;

        if (! $method) {
            return $match;
        }

        // Find action
        $action = $this->getAction($method);
        if (! $action) {
            return $match;
        }

        try {
            // Calculate value
            $value = $action->calculate($match, $semantics);

            // Derive features
            $derivedFeatures = $action->deriveFeatures($value);

            // Update match
            $match->semanticValue = $value;
            $match->features = array_merge($match->features, $derivedFeatures);

        } catch (\Exception $e) {
            // Log error but don't fail the match
            if (config('parser.logging.logSemantics', false)) {
                logger()->warning('Semantic calculation failed', [
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'match' => $match->toArray(),
                ]);
            }
        }

        return $match;
    }

    /**
     * Get action by name
     */
    public function getAction(string $name): ?SemanticAction
    {
        return $this->actions[$name] ?? null;
    }

    /**
     * Check if action exists
     */
    public function hasAction(string $name): bool
    {
        return isset($this->actions[$name]);
    }

    /**
     * Get all registered actions
     *
     * @return array<string, SemanticAction>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get action names
     *
     * @return array<string>
     */
    public function getActionNames(): array
    {
        return array_keys($this->actions);
    }

    /**
     * Validate semantics configuration
     */
    public function validate(array $semantics): array
    {
        $errors = [];

        // Check required fields
        if (! isset($semantics['type']) && ! isset($semantics['method'])) {
            $errors[] = 'Semantics must specify "type" or "method"';

            return ['valid' => false, 'errors' => $errors];
        }

        // Get method
        $method = $semantics['method'] ?? $semantics['type'];

        // Check action exists
        $action = $this->getAction($method);
        if (! $action) {
            $errors[] = "Unknown semantic action: $method";

            return ['valid' => false, 'errors' => $errors];
        }

        // Validate with action
        $actionValidation = $action->validateSemantics($semantics);

        return $actionValidation;
    }

    /**
     * Register default semantic actions
     */
    private function registerDefaultActions(): void
    {
        // Register built-in actions
        $this->register(new SemanticActions\PortugueseNumberAction);
        $this->register(new SemanticActions\DateParserAction);
        $this->register(new SemanticActions\GenericSlotExtractor);
    }
}

<?php

namespace App\Services\Parser;

use App\Data\Parser\AlternativeState;
use App\Data\Parser\ConstructionDefinition;
use App\Data\Parser\ParseStateV4;

/**
 * MWE Aggregator Service
 *
 * Handles aggregation of multi-word expressions into single nodes.
 *
 * Process:
 * 1. MWE alternative reaches 'confirmed' status (after lookahead validation)
 * 2. Combine matched components into a single aggregated node
 * 3. Mark component positions as consumed
 * 4. Transfer features and CE labels to aggregated node
 * 5. Preserve links from components to aggregated node
 *
 * Component preservation (when MWE invalidated):
 * - Strategy: hybrid (preserve last component always, others if not structured)
 * - Creates single-word alternatives for preserved components
 * - Allows components to participate in other constructions
 */
class MWEAggregator
{
    public function __construct(
        private AlternativeManager $alternativeManager,
    ) {}

    /**
     * Aggregate an MWE alternative into a single node
     *
     * @param  ParseStateV4  $state  The parse state
     * @param  AlternativeState  $mweAlternative  The confirmed MWE alternative
     * @param  ConstructionDefinition  $construction  The MWE construction
     * @return array The aggregated node
     */
    public function aggregateMWE(
        ParseStateV4 $state,
        AlternativeState $mweAlternative,
        ConstructionDefinition $construction
    ): array {
        // Create aggregated node
        $aggregatedNode = [
            'type' => 'mwe',
            'constructionName' => $mweAlternative->constructionName,
            'constructionId' => $construction->idConstruction,
            'startPosition' => $mweAlternative->startPosition,
            'endPosition' => $mweAlternative->currentPosition,
            'components' => $mweAlternative->matchedComponents,
            'componentCount' => count($mweAlternative->matchedComponents),

            // Aggregated text
            'phrase' => $construction->aggregateAs ?? $this->buildPhrase($mweAlternative),
            'lemma' => $construction->aggregateAs ?? $this->buildPhrase($mweAlternative),

            // CE labels from construction
            'phrasalCE' => $construction->phrasalCE,
            'clausalCE' => $construction->clausalCE,
            'sententialCE' => $construction->sententialCE,

            // Semantic information
            'semanticType' => $construction->semanticType,
            'semantics' => $construction->semantics,

            // Features (merged from components)
            'features' => $this->mergeComponentFeatures($mweAlternative->matchedComponents),

            // Metadata
            'aggregated' => true,
            'priority' => $construction->priority,
        ];

        // Mark positions as consumed
        for ($i = $mweAlternative->startPosition; $i <= $mweAlternative->currentPosition; $i++) {
            $state->consumePosition($i);
        }

        // Add to confirmed nodes
        $state->confirmNode($aggregatedNode);

        // Log aggregation if enabled
        $this->logAggregation($mweAlternative, $aggregatedNode);

        return $aggregatedNode;
    }

    /**
     * Preserve components when MWE is invalidated
     *
     * Strategy: hybrid approach
     * - Always preserve last component (most likely to continue in other constructions)
     * - Preserve other components if they haven't formed other structures
     *
     * @param  ParseStateV4  $state  The parse state
     * @param  AlternativeState  $invalidatedMWE  The invalidated MWE alternative
     * @param  ConstructionDefinition  $construction  The MWE construction
     * @param  string  $preservationStrategy  Strategy: 'all', 'last', or 'hybrid'
     */
    public function preserveComponents(
        ParseStateV4 $state,
        AlternativeState $invalidatedMWE,
        ConstructionDefinition $construction,
        string $preservationStrategy = 'hybrid'
    ): int {
        $preserved = 0;

        $components = $invalidatedMWE->matchedComponents;

        if (empty($components)) {
            return 0;
        }

        switch ($preservationStrategy) {
            case 'all':
                // Preserve all components
                foreach ($components as $index => $component) {
                    $position = $invalidatedMWE->startPosition + $index;
                    if ($this->shouldPreserveComponent($state, $position, $component)) {
                        $this->createComponentAlternative($state, $component, $position);
                        $preserved++;
                    }
                }
                break;

            case 'last':
                // Preserve only last component
                $lastComponent = end($components);
                $lastPosition = $invalidatedMWE->currentPosition;
                if ($this->shouldPreserveComponent($state, $lastPosition, $lastComponent)) {
                    $this->createComponentAlternative($state, $lastComponent, $lastPosition);
                    $preserved++;
                }
                break;

            case 'hybrid':
            default:
                // Always preserve last component
                $lastComponent = end($components);
                $lastPosition = $invalidatedMWE->currentPosition;
                if ($this->shouldPreserveComponent($state, $lastPosition, $lastComponent)) {
                    $this->createComponentAlternative($state, $lastComponent, $lastPosition);
                    $preserved++;
                }

                // Preserve other components if they lack structure
                foreach ($components as $index => $component) {
                    $position = $invalidatedMWE->startPosition + $index;

                    // Skip last component (already preserved)
                    if ($position === $lastPosition) {
                        continue;
                    }

                    if (! $this->hasOtherConfirmedStructure($state, $position)) {
                        $this->createComponentAlternative($state, $component, $position);
                        $preserved++;
                    }
                }
                break;
        }

        return $preserved;
    }

    /**
     * Check if a component should be preserved
     */
    private function shouldPreserveComponent(
        ParseStateV4 $state,
        int $position,
        object $component
    ): bool {
        // Don't preserve if position is already consumed
        if ($state->isPositionConsumed($position)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a position has other confirmed structures
     */
    private function hasOtherConfirmedStructure(ParseStateV4 $state, int $position): bool
    {
        $nodesAtPosition = $state->getNodesAtPosition($position);

        foreach ($nodesAtPosition as $node) {
            // If there's a confirmed node at this position (not MWE), it has structure
            if (($node['type'] ?? '') !== 'mwe' && ! empty($node['phrasalCE'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a single-word alternative for a preserved component
     */
    private function createComponentAlternative(
        ParseStateV4 $state,
        object $component,
        int $position
    ): void {
        // Create a simple phrasal alternative based on POS
        $pos = $component->upos ?? $component->pos ?? '';

        $componentAlt = new AlternativeState(
            id: AlternativeManager::$alternativeIdCounter++,
            constructionName: "HEAD_{$pos}",
            constructionType: 'phrasal',
            priority: 80,
            startPosition: $position,
            currentPosition: $position,
            matchedComponents: [$component],
            expectedNext: [],
            activation: 1.0,
            threshold: 1.0,
            status: 'complete',
            features: $this->extractComponentFeatures($component),
            pendingConstraints: [],
        );

        $state->addAlternative($componentAlt);
    }

    /**
     * Build phrase from MWE components
     */
    private function buildPhrase(AlternativeState $mweAlternative): string
    {
        $words = [];
        foreach ($mweAlternative->matchedComponents as $component) {
            $words[] = $component->word ?? $component->form ?? '';
        }

        return implode('_', $words);
    }

    /**
     * Merge features from all components
     */
    private function mergeComponentFeatures(array $components): array
    {
        $mergedFeatures = [];

        foreach ($components as $component) {
            $features = $this->extractComponentFeatures($component);
            $mergedFeatures = array_merge($mergedFeatures, $features);
        }

        return $mergedFeatures;
    }

    /**
     * Extract features from a component
     */
    private function extractComponentFeatures(object $component): array
    {
        $features = [];

        if (isset($component->features) && is_array($component->features)) {
            $features = $component->features;
        }

        if (isset($component->feats) && is_string($component->feats)) {
            $features = array_merge($features, $this->parseUDFeatures($component->feats));
        }

        return $features;
    }

    /**
     * Parse UD format features
     */
    private function parseUDFeatures(string $featsString): array
    {
        $features = [];

        if (empty($featsString) || $featsString === '_') {
            return $features;
        }

        $pairs = explode('|', $featsString);
        foreach ($pairs as $pair) {
            if (str_contains($pair, '=')) {
                [$key, $value] = explode('=', $pair, 2);
                $features[trim($key)] = trim($value);
            }
        }

        return $features;
    }

    /**
     * Log MWE aggregation for debugging
     */
    private function logAggregation(AlternativeState $mweAlternative, array $aggregatedNode): void
    {
        if (config('parser.v4.mwe.logInvalidations', false)) {
            logger()->info('MWE Aggregated', [
                'construction' => $mweAlternative->constructionName,
                'phrase' => $aggregatedNode['phrase'],
                'startPosition' => $aggregatedNode['startPosition'],
                'endPosition' => $aggregatedNode['endPosition'],
                'componentCount' => $aggregatedNode['componentCount'],
            ]);
        }
    }

    /**
     * Get statistics about MWE aggregation
     */
    public function getAggregationStatistics(ParseStateV4 $state): array
    {
        return [
            'aggregatedMWEs' => count($state->aggregatedMWEs),
            'consumedPositions' => count($state->consumedPositions),
        ];
    }
}

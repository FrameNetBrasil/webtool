<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\L5Layer;
use App\Models\CLN_RNT\Node;

/**
 * Construction Activation Service
 *
 * Handles construction pattern matching and partial construction creation.
 * This service enables node-centric construction activation where L23 nodes
 * trigger pattern matching instead of having layers iterate over all constructions.
 *
 * Part of Phase 1 of node-centric refactoring.
 */
class ConstructionActivationService
{
    /**
     * Check for construction activations
     *
     * Uses GraphPatternMatcher to find matching patterns in O(1) time via shared graph.
     * Creates partial constructions for each match.
     *
     * @param  Node  $node  node activated
     * @param  GraphPatternMatcher  $matcher  Shared graph matcher
     * @param  L5Layer  $l5  L5 layer for construction creation
     * @return array Created partial constructions
     */
    public static function checkActivation(
        Node $node,
        GraphPatternMatcher $matcher,
        L5Layer $l5
    ): void {
        // Check if node-centric construction activation is enabled
        //        if (! config('cln.node_centric_phases.construction_activation', false)) {
        //            return []; // Feature disabled, use legacy path
        //        }

        // Find ALL matching patterns in one pass (parallel matching via shared graph)
        $matches = $matcher->findMatchingPatternsFromStart($node);

        $partials = [];

        foreach ($matches as $match) {
            $patternId = $match['pattern_id'];
            //            $startNodeId = $match['node_id'];
            //            $startNode = $match['node'];

            // Get construction metadata
            $constructionMeta = $matcher->getConstructionMetadata($patternId);
            if ($constructionMeta === null) {
                continue; // Invalid construction
            }

            // check if exists waiting for this construction
            //            $waiting = $l5->getWaitingNodeByName($constructionMeta['name']);

            //            if (!empty($waiting)) {
            //                // Link to predicted construction
            //                $partial = self::linkToPredictedConstruction(
            //                    node: $node,
            //                    predicted: $predicted,
            //                    matchData: $match,
            //                    constructionMeta: $constructionMeta,
            //                    l5: $l5
            //                );
            //            } else {
            // Create and link partial construction from match data
            $partial = self::createPartialConstruction(
                node: $node,
                constructionId: $constructionMeta['id'],
                matchData: $match,
                constructionMeta: $constructionMeta,
                l5: $l5
            );
            //            }
            //            if ($partial) {
            //                $partials[] = $partial;
            //
            //                // Check if pattern is already complete (single-element patterns)
            //                if ($matcher->isPatternComplete($startNodeId, $patternId)) {
            //                    $l5->confirmConstruction($partial->id);
            //                }
            //            }

        }

        //        return $partials;
    }

    /**
     * Create partial construction from pattern match
     *
     * Extracts pattern data, initializes traversal state, and creates Node
     * via L5Layer's createPartialConstruction method.
     *
     * @param  int  $constructionId  Construction ID
     * @param  array  $matchData  Match data from GraphPatternMatcher
     * @param  array  $constructionMeta  Construction metadata
     * @param  L5Layer  $l5  L5 layer for construction creation
     * @return Node|null Created partial construction, or null on failure
     */
    public static function createPartialConstruction(
        Node $node,
        int $constructionId,
        array $matchData,
        array $constructionMeta,
        L5Layer $l5
    ): ?Node {
        $patternId = $matchData['pattern_id'];
        $startNodeId = $matchData['node_id'];
        $startNode = $matchData['node'];

        $constructionName = $constructionMeta['name'];
        $compiledPattern = $constructionMeta['compiledPattern'] ?? [];
        $graph = $compiledPattern;

        // Extract pattern sequence using L5Layer helper method
        $patternSequence = $l5->extractPatternSequence($graph);

        // Initialize matched array: first element is true (we just matched it), rest are false
        $matched = array_fill(0, count($patternSequence), false);
        if (count($matched) > 0) {
            $matched[0] = true; // First element matched
        }

        // Initialize traversal state (using shared graph node ID)
        $traversalState = [
            'current_node_id' => $startNodeId,
            'path_taken' => [$startNodeId],
            'alternative_choices' => [],
            'repetition_state' => [],
            'bypassed_nodes' => [],
            'pattern_id' => $patternId,  // Track which pattern we're following
            'use_shared_graph' => true,  // Flag to use GraphPatternMatcher for advancement
        ];

        // Create partial construction via L5 layer
        $partialConstruction = $l5->createPartialConstruction(
            constructionId: $constructionId,
            metadata: [
                'construction_id' => $constructionId,
                'name' => $constructionName,
                'pattern' => $patternSequence,
                'pattern_id' => $patternId,
                'graph' => $graph,
                'graph_nodes' => $graph['nodes'] ?? [],
                'traversal_state' => $traversalState,
                'matched' => $matched,
                'anchor_position' => $l5->columnPosition,
                'span_length' => 1,
            ]
        );

        // Link matching L23 nodes to this construction
        //        $l5->linkNodeToConstruction(
        //            $node,
        //            $partialConstruction,
        //            $startNode,
        //            new PatternMatcher
        //        );
        $node->linkToConstruction(
            $partialConstruction,
            $startNode,
            new GraphPatternMatcher,
            $l5
        );

        return $partialConstruction;
    }

    /**
     * Link to predicted construction
     *
     * @param  int  $constructionId  Construction ID
     * @param  array  $matchData  Match data from GraphPatternMatcher
     * @param  array  $constructionMeta  Construction metadata
     * @param  L5Layer  $l5  L5 layer for construction creation
     * @return Node|null Created partial construction, or null on failure
     */
    public static function linkToPredictedConstruction(
        Node $node,
        Node $predicted,
        array $matchData,
        array $constructionMeta,
        L5Layer $l5
    ): ?Node {
        //        $patternId = $matchData['pattern_id'];
        //        $startNodeId = $matchData['node_id'];
        $startNode = $matchData['node'];

        //        $constructionName = $predicted->metadata['name'];
        //        $compiledPattern = $predicted->metadata['compiledPattern'] ?? [];
        //        $graph = $compiledPattern;

        // Extract pattern sequence using L5Layer helper method
        //        $patternSequence = $predicted->metadata['pattern'];
        //
        //        $matched = $predicted->metadata['matched'];

        // Link matching nodes to this prediction
        // Note: This delegates to L5Layer's linkL23ToConstruction method
        $node->linkToConstruction(
            $predicted,
            $startNode,
            new GraphPatternMatcher,
            $l5
        );

        $predicted->metadata['prediction_confirmed'] = true;
        $predicted->metadata['prediction_source_column'] = 0;

        return $predicted;
    }

    /**
     * Check for and confirm waiting prediction from centralized manager
     *
     * Queries the ColumnSequenceManager for predictions matching the given
     * construction name. If found, creates cross-column confirmation link.
     *
     * @param  Node  $constructionNode  The L23 construction node just created
     * @param  string  $constructionName  Construction name to match
     */
    public static function checkAndConfirmPrediction(
        Node $node,
        L5Layer $l5
    ): void {
        $column = $l5->getColumn();

        $manager = $l5->getColumn()->getSequenceManager();
        if ($manager === null) {
            return;
        }

        if (($node->metadata['name'] ?? null) === null) {
            return;
        }

        // Query manager for waiting prediction
        debug('--'.$node->metadata['name']);
        $predictionEntry = $manager->checkForPrediction($node->metadata['name']);

        if ($predictionEntry !== null) {
            // Match found! Create cross-column link and update source partial
            debug('Match found!', $predictionEntry);
            $l5Target = $manager->getColumn($predictionEntry->sourceColumn)->getL5();
            $partialConstruction = $l5Target->getNode($predictionEntry->sourcePartialId);
            $node->addOutput($partialConstruction);
            $partialConstruction->addInput($node);
            $partialConstruction->confirmConstruction($l5);
            $partialConstruction->triggerConstructionActivation($l5);

            // $this->confirmPredictionWithCrossColumnLink($constructionNode, $predictionEntry);
            //            $node->linkToConstruction(
            //                $partialConstruction,
            //                $startNode,
            //                new GraphPatternMatcher,
            //                $l5
            //            );
        }
    }
}

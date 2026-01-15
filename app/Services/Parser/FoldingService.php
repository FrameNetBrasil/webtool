<?php

namespace App\Services\Parser;

use App\Enums\Parser\SententialCE;
use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseNode;

/**
 * Folding Stage: Sentential Integration
 *
 * Integrates clauses into complete sentence structures with long-distance dependencies.
 * This is Stage 3 of the three-stage parsing framework (Transcription → Translation → Folding).
 *
 * Biological Analogy: Polypeptide → Protein (Folding)
 * - Identifies clause boundaries
 * - Assigns sentential CE labels (Main, Adv, Comp, Rel)
 * - Creates long-distance dependencies (like disulfide bridges)
 * - Handles non-projective structures (relative clauses, topicalization)
 */
class FoldingService
{
    private FeatureCompatibilityService $compatibilityService;

    private GrammarGraphService $grammarService;

    public function __construct(
        FeatureCompatibilityService $compatibilityService,
        GrammarGraphService $grammarService
    ) {
        $this->compatibilityService = $compatibilityService;
        $this->grammarService = $grammarService;
    }

    /**
     * Fold phrases into sentence structure
     *
     * @param  int  $idParserGraph  Parse graph ID
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @param  string  $language  Language code
     * @return array Array of created edge IDs
     */
    public function fold(
        int $idParserGraph,
        int $idGrammarGraph,
        string $language = 'pt'
    ): array {
        $createdEdges = [];

        if (config('parser.logging.logStages', false)) {
            logger()->info('Folding Stage: Starting', [
                'idParserGraph' => $idParserGraph,
                'language' => $language,
            ]);
        }

        // Get all nodes from previous stages
        $allNodes = ParseNode::listByGraph($idParserGraph);

        if (empty($allNodes)) {
            logger()->warning('Folding Stage: No nodes found');

            return [];
        }

        // Step 1: Identify clause boundaries
        $clauses = $this->identifyClauses($allNodes);

        if (config('parser.logging.logStages', false)) {
            logger()->info('Folding: Identified clauses', [
                'count' => count($clauses),
            ]);
        }

        // Step 2: Assign sentential CE labels to each clause
        $this->assignSententialLabels($clauses, $allNodes);

        // Step 3: Build inter-clausal dependencies
        $interClauseEdges = $this->buildInterClauseDependencies($clauses, $idParserGraph);
        $createdEdges = array_merge($createdEdges, $interClauseEdges);

        // Step 4: Handle long-distance dependencies
        $longDistEdges = $this->buildLongDistanceDependencies($clauses, $allNodes, $idParserGraph);
        $createdEdges = array_merge($createdEdges, $longDistEdges);

        // Step 5: Identify and mark root
        $this->identifyRoot($clauses, $idParserGraph);

        // Step 6: Update all nodes with clause information
        $this->updateNodesWithClauseInfo($clauses);

        if (config('parser.logging.logStages', false)) {
            logger()->info('Folding Stage: Complete', [
                'edgesCreated' => count($createdEdges),
                'clauseCount' => count($clauses),
            ]);
        }

        return $createdEdges;
    }

    /**
     * Identify clause boundaries based on predicates
     *
     * A clause is defined as a predicate with its arguments and modifiers.
     *
     * @return array Array of clause data structures
     */
    private function identifyClauses(array $nodes): array
    {
        $clauses = [];
        $assignedNodes = [];

        // Sort nodes by position
        usort($nodes, fn ($a, $b) => $a->positionInSentence <=> $b->positionInSentence);

        // Find all predicates (finite verbs or verb-like elements)
        $predicates = $this->findPredicates($nodes);

        foreach ($predicates as $predIdx => $predicate) {
            $clause = [
                'id' => $predIdx,
                'predicate' => $predicate,
                'nodes' => [$predicate],
                'marker' => null,
                'sententialCE' => null,
                'isRoot' => false,
                'span' => [
                    'start' => $predicate->positionInSentence,
                    'end' => $predicate->positionInSentence,
                ],
            ];

            $assignedNodes[$predicate->idParserNode] = $predIdx;

            // Find nodes that belong to this clause
            // Use existing translation-stage links
            $translationLinks = ParseEdge::listByStageWithNodes($predicate->idParserGraph, 'translation');

            // Find direct dependents of this predicate
            foreach ($translationLinks as $link) {
                if ($link->idSourceNode === $predicate->idParserNode) {
                    $dependent = ParseNode::byId($link->idTargetNode);
                    if ($dependent && ! isset($assignedNodes[$dependent->idParserNode])) {
                        $clause['nodes'][] = $dependent;
                        $assignedNodes[$dependent->idParserNode] = $predIdx;

                        // Update span
                        $clause['span']['start'] = min($clause['span']['start'], $dependent->positionInSentence);
                        $clause['span']['end'] = max($clause['span']['end'], $dependent->positionInSentence);
                    }
                }
            }

            // Check for subordinating conjunction (clause marker)
            $marker = $this->findClauseMarker($clause['nodes'], $nodes);
            if ($marker) {
                $clause['marker'] = $marker;
                if (! isset($assignedNodes[$marker->idParserNode])) {
                    $clause['nodes'][] = $marker;
                    $assignedNodes[$marker->idParserNode] = $predIdx;
                }
            }

            $clauses[] = $clause;
        }

        // Assign unassigned nodes to nearest clause
        foreach ($nodes as $node) {
            if (! isset($assignedNodes[$node->idParserNode])) {
                $nearestClause = $this->findNearestClause($node, $clauses);
                if ($nearestClause !== null) {
                    $clauses[$nearestClause]['nodes'][] = $node;
                    $clauses[$nearestClause]['span']['start'] = min(
                        $clauses[$nearestClause]['span']['start'],
                        $node->positionInSentence
                    );
                    $clauses[$nearestClause]['span']['end'] = max(
                        $clauses[$nearestClause]['span']['end'],
                        $node->positionInSentence
                    );
                }
            }
        }

        return $clauses;
    }

    /**
     * Find predicate nodes (verbs, auxiliary complexes)
     */
    private function findPredicates(array $nodes): array
    {
        $predicates = [];

        foreach ($nodes as $node) {
            // Check if this is a predicate-type node
            if ($this->isPredicate($node)) {
                $predicates[] = $node;
            }
        }

        return $predicates;
    }

    /**
     * Check if a node is a predicate
     */
    private function isPredicate(object $node): bool
    {
        // Finite verbs are predicates
        if ($node->pos === 'VERB') {
            $features = $this->getNodeFeatures($node);
            if (isset($features['lexical']['VerbForm']) && $features['lexical']['VerbForm'] === 'Fin') {
                return true;
            }
        }

        // Check derived features for phrase type
        $features = $this->getNodeFeatures($node);
        if (isset($features['derived']['phraseType']) && $features['derived']['phraseType'] === 'Pred') {
            return true;
        }

        // Root verbs are always predicates
        if ($node->pos === 'VERB' && $this->isRootVerb($node)) {
            return true;
        }

        return false;
    }

    /**
     * Check if verb is the root verb based on dependency relations
     */
    private function isRootVerb(object $node): bool
    {
        // Check if no incoming dependency edges (potential root)
        $incomingEdges = ParseEdge::listByTargetNode($node->idParserNode);

        return empty($incomingEdges);
    }

    /**
     * Find clause marker (subordinating conjunction) for a clause
     */
    private function findClauseMarker(array $clauseNodes, array $allNodes): ?object
    {
        // Look for SCONJ at the beginning of the clause span
        $positions = array_map(fn ($n) => $n->positionInSentence, $clauseNodes);
        $minPos = min($positions);

        foreach ($allNodes as $node) {
            if ($node->positionInSentence === $minPos - 1 && $node->pos === 'SCONJ') {
                return $node;
            }
            // Also check for marker at clause start
            if ($node->positionInSentence === $minPos && $node->pos === 'SCONJ') {
                return $node;
            }
        }

        return null;
    }

    /**
     * Find the nearest clause for an unassigned node
     */
    private function findNearestClause(object $node, array $clauses): ?int
    {
        if (empty($clauses)) {
            return null;
        }

        $nearestClause = null;
        $minDistance = PHP_INT_MAX;

        foreach ($clauses as $idx => $clause) {
            // Calculate distance to clause span
            $distance = $this->distanceToSpan($node->positionInSentence, $clause['span']);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestClause = $idx;
            }
        }

        return $nearestClause;
    }

    /**
     * Calculate distance from a position to a span
     */
    private function distanceToSpan(int $position, array $span): int
    {
        if ($position < $span['start']) {
            return $span['start'] - $position;
        }
        if ($position > $span['end']) {
            return $position - $span['end'];
        }

        return 0; // Position is within span
    }

    /**
     * Assign sentential CE labels to clauses
     */
    private function assignSententialLabels(array &$clauses, array $allNodes): void
    {
        // First, identify the main clause (typically the one without subordinator)
        $mainClauseIdx = null;

        foreach ($clauses as $idx => &$clause) {
            $sententialCE = $this->determineSententialCE($clause, $allNodes);
            $clause['sententialCE'] = $sententialCE;

            if ($sententialCE === SententialCE::MAIN->value && $mainClauseIdx === null) {
                $clause['isRoot'] = true;
                $mainClauseIdx = $idx;
            }
        }

        // If no main clause found, mark the first clause as main
        if ($mainClauseIdx === null && ! empty($clauses)) {
            $clauses[0]['sententialCE'] = SententialCE::MAIN->value;
            $clauses[0]['isRoot'] = true;
        }
    }

    /**
     * Determine sentential CE type for a clause
     */
    private function determineSententialCE(array $clause, array $allNodes): string
    {
        // If no marker and has finite predicate, it's Main
        if ($clause['marker'] === null && $this->hasFinitePredicate($clause)) {
            return SententialCE::MAIN->value;
        }

        // Check marker type
        if ($clause['marker'] !== null) {
            $markerLemma = strtolower($clause['marker']->label ?? $clause['marker']->word ?? '');

            // Relative clause markers
            $relativeMarkers = ['que', 'quem', 'qual', 'cujo', 'onde', 'who', 'which', 'that', 'whose', 'where'];
            if (in_array($markerLemma, $relativeMarkers)) {
                // Check if modifying a noun
                if ($this->isModifyingNoun($clause, $allNodes)) {
                    return SententialCE::REL->value;
                }
            }

            // Adverbial clause markers
            $adverbialMarkers = [
                'quando', 'enquanto', 'porque', 'como', 'se', 'embora', // Portuguese
                'when', 'while', 'because', 'since', 'if', 'although', 'unless', // English
            ];
            if (in_array($markerLemma, $adverbialMarkers)) {
                return SententialCE::ADV->value;
            }

            // Complement clause markers
            $complementMarkers = ['que', 'that', 'whether', 'if'];
            if (in_array($markerLemma, $complementMarkers)) {
                // Check if argument of complement-taking predicate
                if ($this->isComplementOfCTP($clause, $allNodes)) {
                    return SententialCE::COMP->value;
                }
            }
        }

        // Check for deranked predicate forms
        $features = $this->getNodeFeatures($clause['predicate']);
        $verbForm = $features['lexical']['VerbForm'] ?? null;

        if ($verbForm === 'Inf') {
            // Infinitives are typically complements
            return SententialCE::COMP->value;
        }

        if ($verbForm === 'Ger') {
            // Gerunds can be adverbial or complement
            return SententialCE::ADV->value;
        }

        if ($verbForm === 'Part') {
            // Participles are typically relative
            return SententialCE::REL->value;
        }

        // Default to Main if nothing else matches
        return SententialCE::MAIN->value;
    }

    /**
     * Check if clause has a finite predicate
     */
    private function hasFinitePredicate(array $clause): bool
    {
        $features = $this->getNodeFeatures($clause['predicate']);

        return isset($features['lexical']['VerbForm']) && $features['lexical']['VerbForm'] === 'Fin';
    }

    /**
     * Check if clause is modifying a noun (relative clause)
     */
    private function isModifyingNoun(array $clause, array $allNodes): bool
    {
        // Look for a noun immediately before the clause
        $clauseStart = $clause['span']['start'];

        foreach ($allNodes as $node) {
            if ($node->positionInSentence === $clauseStart - 1) {
                return in_array($node->pos, ['NOUN', 'PROPN', 'PRON']);
            }
        }

        return false;
    }

    /**
     * Check if clause is complement of a complement-taking predicate
     */
    private function isComplementOfCTP(array $clause, array $allNodes): bool
    {
        // Common complement-taking predicates
        $ctpLemmas = [
            // Portuguese
            'dizer', 'falar', 'pensar', 'achar', 'saber', 'querer', 'poder',
            'ver', 'ouvir', 'sentir', 'acreditar', 'esperar', 'desejar',
            // English
            'say', 'tell', 'think', 'know', 'want', 'believe', 'hope', 'expect',
            'see', 'hear', 'feel', 'wish', 'make', 'let',
        ];

        // Look for a CTP before this clause
        $clauseStart = $clause['span']['start'];

        foreach ($allNodes as $node) {
            if ($node->positionInSentence < $clauseStart && $node->pos === 'VERB') {
                $lemma = strtolower($node->label ?? '');
                if (in_array($lemma, $ctpLemmas)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Build inter-clausal dependencies
     */
    private function buildInterClauseDependencies(array $clauses, int $idParserGraph): array
    {
        $createdEdges = [];

        // Find main clause
        $mainClause = null;
        foreach ($clauses as $clause) {
            if ($clause['isRoot']) {
                $mainClause = $clause;
                break;
            }
        }

        if ($mainClause === null) {
            return [];
        }

        // Connect subordinate clauses to main clause
        foreach ($clauses as $clause) {
            if ($clause['isRoot']) {
                continue;
            }

            // Create edge from main predicate to subordinate predicate
            $relation = $this->getClauseRelation($clause['sententialCE']);

            $edgeData = [
                'idParserGraph' => $idParserGraph,
                'idSourceNode' => $mainClause['predicate']->idParserNode,
                'idTargetNode' => $clause['predicate']->idParserNode,
                'linkType' => 'clausal',
                'stage' => 'folding',
                'relation' => $relation,
            ];

            $idEdge = ParseEdge::create($edgeData);
            $createdEdges[] = $idEdge;

            if (config('parser.logging.logStages', false)) {
                logger()->info('Folding: Created inter-clause link', [
                    'main' => $mainClause['predicate']->label,
                    'subordinate' => $clause['predicate']->label,
                    'relation' => $relation,
                ]);
            }
        }

        return $createdEdges;
    }

    /**
     * Get dependency relation type for clause
     */
    private function getClauseRelation(string $sententialCE): string
    {
        return match ($sententialCE) {
            SententialCE::ADV->value => 'advcl',
            SententialCE::COMP->value => 'ccomp',
            SententialCE::REL->value => 'acl:relcl',
            default => 'parataxis',
        };
    }

    /**
     * Build long-distance dependencies
     */
    private function buildLongDistanceDependencies(array $clauses, array $allNodes, int $idParserGraph): array
    {
        $createdEdges = [];

        // Handle relative clauses
        foreach ($clauses as $clause) {
            if ($clause['sententialCE'] === SententialCE::REL->value) {
                $edge = $this->createRelativeClauseLink($clause, $allNodes, $idParserGraph);
                if ($edge) {
                    $createdEdges[] = $edge;
                }
            }
        }

        return $createdEdges;
    }

    /**
     * Create link between relative clause and its antecedent
     */
    private function createRelativeClauseLink(array $relClause, array $allNodes, int $idParserGraph): ?int
    {
        // Find antecedent (noun before relative clause)
        $clauseStart = $relClause['span']['start'];
        $antecedent = null;

        for ($i = $clauseStart - 1; $i >= 0; $i--) {
            foreach ($allNodes as $node) {
                if ($node->positionInSentence === $i && in_array($node->pos, ['NOUN', 'PROPN', 'PRON'])) {
                    $antecedent = $node;
                    break 2;
                }
            }
        }

        if ($antecedent === null) {
            return null;
        }

        // Create non-projective edge from antecedent to relative clause predicate
        $edgeData = [
            'idParserGraph' => $idParserGraph,
            'idSourceNode' => $antecedent->idParserNode,
            'idTargetNode' => $relClause['predicate']->idParserNode,
            'linkType' => 'long_distance',
            'stage' => 'folding',
            'relation' => 'relcl',
            'isNonProjective' => true,
        ];

        $idEdge = ParseEdge::create($edgeData);

        if (config('parser.logging.logStages', false)) {
            logger()->info('Folding: Created relative clause link', [
                'antecedent' => $antecedent->label,
                'relclause' => $relClause['predicate']->label,
            ]);
        }

        return $idEdge;
    }

    /**
     * Identify and mark the root of the parse
     */
    private function identifyRoot(array $clauses, int $idParserGraph): void
    {
        foreach ($clauses as $clause) {
            if ($clause['isRoot']) {
                // Mark predicate as root
                ParseNode::updateDerivedFeatures($clause['predicate']->idParserNode, [
                    'isRoot' => true,
                    'sententialCE' => $clause['sententialCE'],
                ]);

                // Update graph with root node
                \App\Repositories\Parser\ParseGraph::setRoot($idParserGraph, $clause['predicate']->idParserNode);

                return;
            }
        }
    }

    /**
     * Update all nodes with clause information
     */
    private function updateNodesWithClauseInfo(array $clauses): void
    {
        foreach ($clauses as $clauseIdx => $clause) {
            foreach ($clause['nodes'] as $node) {
                ParseNode::updateDerivedFeatures($node->idParserNode, [
                    'clauseId' => $clauseIdx,
                    'sententialCE' => $clause['sententialCE'],
                    'isClausePredicate' => $node->idParserNode === $clause['predicate']->idParserNode,
                ]);
            }
        }
    }

    /**
     * Get features from node
     */
    private function getNodeFeatures(object $node): array
    {
        if (empty($node->features)) {
            return ['lexical' => [], 'derived' => []];
        }

        return json_decode($node->features, true) ?? ['lexical' => [], 'derived' => []];
    }

    /**
     * Get folding stage statistics
     */
    public function getStatistics(int $idParserGraph): array
    {
        $foldingLinks = ParseEdge::listByStage($idParserGraph, 'folding');

        $clauseCount = 0;
        $longDistanceCount = 0;

        foreach ($foldingLinks as $link) {
            if ($link->linkType === 'clausal') {
                $clauseCount++;
            }
            if ($link->linkType === 'long_distance') {
                $longDistanceCount++;
            }
        }

        // Count nodes by sentential CE
        $nodes = ParseNode::listByGraph($idParserGraph);
        $sententialCounts = [];

        foreach ($nodes as $node) {
            $features = $this->getNodeFeatures($node);
            $sentCE = $features['derived']['sententialCE'] ?? 'unassigned';
            $sententialCounts[$sentCE] = ($sententialCounts[$sentCE] ?? 0) + 1;
        }

        return [
            'clauseLinks' => $clauseCount,
            'longDistanceLinks' => $longDistanceCount,
            'sententialCECounts' => $sententialCounts,
        ];
    }
}

<?php

namespace App\Services\Parser\V4;

use App\Data\Parser\ParseStateV4;
use Illuminate\Support\Facades\Log;

/**
 * Link Builder for Parser V4
 *
 * Builds dependency links between confirmed parse nodes based on:
 * - CE label patterns (Mod->Head, Arg->Pred, etc.)
 * - Adjacency and distance
 * - Feature agreement
 * - Construction compatibility
 *
 * This service is called after nodes are confirmed to establish
 * cross-level dependencies between phrasal, clausal, and sentential elements.
 */
class LinkBuilder
{
    private const MAX_LOCAL_DISTANCE = 5;

    private const REQUIRE_AGREEMENT = true;

    /**
     * CTP (Complement-Taking Predicate) verbs that favor complement clauses
     */
    private const CTP_VERBS = [
        'dizer', 'achar', 'pensar', 'acreditar', 'saber',
        'ver', 'perceber', 'notar', 'imaginar', 'esperar',
    ];

    public function __construct(
        private readonly AgreementChecker $agreementChecker,
    ) {}

    /**
     * Build links for nodes at the current position
     *
     * This is called after nodes are confirmed at the current position.
     * We need to check two directions:
     * 1. Can newly confirmed nodes attach to previous nodes? (new node as dependent)
     * 2. Can previous nodes attach to newly confirmed nodes? (new node as head)
     *
     * @param  ParseStateV4  $state  The current parse state
     * @param  int  $position  The current position
     * @param  bool  $logProgress  Whether to log progress
     */
    public function buildLinksAtPosition(
        ParseStateV4 $state,
        int $position,
        bool $logProgress = false
    ): void {
        // Find nodes at current position (newly confirmed)
        $currentNodes = $this->findNodesAtPosition($state, $position);

        if ($logProgress && ! empty($currentNodes)) {
            Log::info("V4 Link Building at position {$position}", [
                'nodeCount' => count($currentNodes),
            ]);
        }

        foreach ($currentNodes as $node) {
            // Skip nodes that are consumed by MWE aggregation
            if (isset($node['consumed']) && $node['consumed']) {
                continue;
            }

            // Direction 1: Can this node attach to previous nodes? (this node as dependent)
            $potentialHeads = $this->findPotentialHeads($node, $state);
            foreach ($potentialHeads as $head) {
                if ($this->shouldCreateLink($node, $head, $state)) {
                    $edge = $this->createEdge($node, $head, $state);
                    if ($edge) {
                        $state->confirmEdge($edge);

                        if ($logProgress) {
                            Log::info('V4 Link Created (node→head)', [
                                'dependent' => $node['id'] ?? $node['constructionName'],
                                'head' => $head['id'] ?? $head['constructionName'],
                                'relation' => $edge['relation'],
                            ]);
                        }
                    }
                }
            }

            // Direction 2: Can previous nodes attach to this node? (this node as head)
            $potentialDependents = $this->findPotentialDependents($node, $state, $position);
            foreach ($potentialDependents as $dependent) {
                if ($this->shouldCreateLink($dependent, $node, $state)) {
                    $edge = $this->createEdge($dependent, $node, $state);
                    if ($edge) {
                        $state->confirmEdge($edge);

                        if ($logProgress) {
                            Log::info('V4 Link Created (dep→node)', [
                                'dependent' => $dependent['id'] ?? $dependent['constructionName'],
                                'head' => $node['id'] ?? $node['constructionName'],
                                'relation' => $edge['relation'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Find nodes at a specific position
     *
     * Includes both single-token nodes and MWE nodes that span the position
     */
    private function findNodesAtPosition(ParseStateV4 $state, int $position): array
    {
        $nodes = [];

        foreach ($state->confirmedNodes as $node) {
            // Single-token node
            if (isset($node['position']) && $node['position'] === $position) {
                $nodes[] = $node;
            }

            // MWE node spanning this position
            if (isset($node['startPosition']) && isset($node['endPosition'])) {
                if ($node['startPosition'] <= $position && $node['endPosition'] >= $position) {
                    $nodes[] = $node;
                }
            }
        }

        return $nodes;
    }

    /**
     * Find potential dependents for a given node (nodes that could attach to this node as head)
     *
     * @param  array  $node  The head node
     * @param  ParseStateV4  $state  The current parse state
     * @param  int  $currentPosition  The current position (to avoid checking future nodes)
     * @return array List of potential dependent nodes
     */
    private function findPotentialDependents(array $node, ParseStateV4 $state, int $currentPosition): array
    {
        $potentialDependents = [];
        $nodePosition = $this->getNodePosition($node);

        foreach ($state->confirmedNodes as $other) {
            // Skip self
            if ($this->isSameNode($node, $other)) {
                continue;
            }

            // Skip consumed nodes
            if (isset($other['consumed']) && $other['consumed']) {
                continue;
            }

            // Only consider nodes before or at current position
            $otherPosition = $this->getNodePosition($other);
            if ($otherPosition > $currentPosition) {
                continue;
            }

            // Check if this node could be dependent on our node
            if ($this->couldAttachTo($other, $node)) {
                $potentialDependents[] = $other;
            }
        }

        return $potentialDependents;
    }

    /**
     * Check if a dependent could attach to a head based on CE patterns
     */
    private function couldAttachTo(array $dependent, array $head): bool
    {
        // Phrasal patterns
        if ($this->matchesPhrasalPattern($dependent, $head)) {
            return true;
        }

        // Clausal patterns
        if ($this->matchesClausalPattern($dependent, $head)) {
            return true;
        }

        // Sentential patterns
        if ($this->matchesSententialPattern($dependent, $head)) {
            return true;
        }

        return false;
    }

    /**
     * Find potential heads for a given node
     *
     * Uses multiple strategies:
     * 1. Adjacency-based (within local distance)
     * 2. CE label patterns (Mod->Head, Arg->Pred, etc.)
     * 3. Sorted by distance (prefer closer heads)
     *
     * @param  array  $node  The dependent node
     * @param  ParseStateV4  $state  The current parse state
     * @return array List of potential head nodes, sorted by distance
     */
    private function findPotentialHeads(array $node, ParseStateV4 $state): array
    {
        $potentialHeads = [];
        $nodePosition = $this->getNodePosition($node);

        foreach ($state->confirmedNodes as $other) {
            // Skip self
            if ($this->isSameNode($node, $other)) {
                continue;
            }

            // Skip consumed nodes
            if (isset($other['consumed']) && $other['consumed']) {
                continue;
            }

            $otherPosition = $this->getNodePosition($other);
            $distance = abs($otherPosition - $nodePosition);

            // Only consider nodes within reasonable distance
            if ($distance > self::MAX_LOCAL_DISTANCE) {
                continue;
            }

            // Check if this node matches any attachment patterns
            if ($this->matchesPhrasalPattern($node, $other)) {
                $potentialHeads[] = $other;

                continue;
            }

            if ($this->matchesClausalPattern($node, $other)) {
                $potentialHeads[] = $other;

                continue;
            }

            if ($this->matchesSententialPattern($node, $other)) {
                $potentialHeads[] = $other;
            }
        }

        // Sort by distance (prefer closer heads)
        usort($potentialHeads, function ($a, $b) use ($nodePosition) {
            $distA = abs($this->getNodePosition($a) - $nodePosition);
            $distB = abs($this->getNodePosition($b) - $nodePosition);

            return $distA <=> $distB;
        });

        return $potentialHeads;
    }

    /**
     * Check if node matches phrasal CE patterns for linking
     */
    private function matchesPhrasalPattern(array $node, array $other): bool
    {
        $nodeCE = $node['phrasalCE'] ?? null;
        $otherCE = $other['phrasalCE'] ?? null;

        // Modifiers attach to heads
        if ($nodeCE === 'Mod' && $otherCE === 'Head') {
            return $this->isInSamePhrase($node, $other);
        }

        // Adpositions attach to heads
        if ($nodeCE === 'Adp' && $otherCE === 'Head') {
            return true;
        }

        return false;
    }

    /**
     * Check if node matches clausal CE patterns for linking
     */
    private function matchesClausalPattern(array $node, array $other): bool
    {
        $nodeCE = $node['clausalCE'] ?? null;
        $otherCE = $other['clausalCE'] ?? null;

        // Arguments attach to predicates
        if ($nodeCE === 'Arg' && $otherCE === 'Pred') {
            return true;
        }

        // Clause peripherals attach to predicates
        if ($nodeCE === 'CPP' && $otherCE === 'Pred') {
            return true;
        }

        // Frame-peripheral modifiers attach to predicates
        if ($nodeCE === 'FPM' && $otherCE === 'Pred') {
            return true;
        }

        // Genitives attach to heads
        if ($nodeCE === 'Gen' && $otherCE === 'Arg') {
            return true;
        }

        return false;
    }

    /**
     * Check if node matches sentential CE patterns for linking
     */
    private function matchesSententialPattern(array $node, array $other): bool
    {
        $nodeCE = $node['sententialCE'] ?? null;
        $otherCE = $other['sententialCE'] ?? null;

        // Relative clauses attach to main clauses
        if ($nodeCE === 'Rel' && $otherCE === 'Main') {
            return true;
        }

        // Complement clauses attach to main clauses
        if ($nodeCE === 'Comp' && $otherCE === 'Main') {
            return true;
        }

        // Adverbial clauses attach to main clauses
        if ($nodeCE === 'Adv' && $otherCE === 'Main') {
            return true;
        }

        return false;
    }

    /**
     * Check if two nodes are in the same phrase (within proximity)
     */
    private function isInSamePhrase(array $node, array $other): bool
    {
        $distance = abs($this->getNodePosition($node) - $this->getNodePosition($other));

        return $distance <= 3; // Within 3 tokens
    }

    /**
     * Check if a link should be created between dependent and head
     *
     * @param  array  $dependent  The dependent node
     * @param  array  $head  The head node
     * @param  ParseStateV4  $state  The current parse state
     * @return bool Whether the link should be created
     */
    private function shouldCreateLink(array $dependent, array $head, ParseStateV4 $state): bool
    {
        // Check if link already exists
        if ($this->linkExists($dependent, $head, $state)) {
            return false;
        }

        // Check valid direction (dependent -> head)
        if (! $this->isValidDirection($dependent, $head)) {
            return false;
        }

        // Check feature agreement if required
        if (self::REQUIRE_AGREEMENT) {
            $requiredFeatures = $this->getRequiredAgreement($dependent, $head);
            if (! empty($requiredFeatures)) {
                $agreement = $this->agreementChecker->checkAgreement(
                    $dependent['features'] ?? [],
                    $head['features'] ?? [],
                    $requiredFeatures
                );

                if (! $agreement['agrees']) {
                    return false;
                }
            }
        }

        // Check construction compatibility
        if (! $this->constructionsCompatible($dependent, $head)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a link already exists between two nodes
     */
    private function linkExists(array $dependent, array $head, ParseStateV4 $state): bool
    {
        $dependentId = $this->getNodeId($dependent);
        $headId = $this->getNodeId($head);

        foreach ($state->confirmedEdges as $edge) {
            if ($edge['targetId'] === $dependentId && $edge['sourceId'] === $headId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the direction is valid (dependent -> head)
     *
     * Generally, modifiers/dependents come before or after heads,
     * but there are language-specific rules
     */
    private function isValidDirection(array $dependent, array $head): bool
    {
        // For now, allow both directions
        // TODO: Add language-specific directionality rules
        return true;
    }

    /**
     * Get required agreement features for a dependency relation
     *
     * @return array List of features that must agree
     */
    private function getRequiredAgreement(array $dependent, array $head): array
    {
        $depClausalCE = $dependent['clausalCE'] ?? null;
        $headClausalCE = $head['clausalCE'] ?? null;

        // Subject-predicate agreement
        if ($depClausalCE === 'Arg' && $headClausalCE === 'Pred') {
            // Check if it's a subject (would need more context)
            return ['Number']; // Subjects must agree in number with predicates
        }

        // Modifier-head agreement in phrases
        $depPhrasalCE = $dependent['phrasalCE'] ?? null;
        $headPhrasalCE = $head['phrasalCE'] ?? null;

        if ($depPhrasalCE === 'Mod' && $headPhrasalCE === 'Head') {
            $depPos = $dependent['pos'] ?? null;
            if ($depPos === 'ADJ') {
                return ['Gender', 'Number']; // Adjectives agree with nouns
            }
        }

        return []; // No agreement required
    }

    /**
     * Check if two constructions are compatible for linking
     */
    private function constructionsCompatible(array $dependent, array $head): bool
    {
        // For now, allow all construction combinations
        // TODO: Add specific compatibility rules if needed
        return true;
    }

    /**
     * Create an edge between dependent and head
     *
     * @param  array  $dependent  The dependent node
     * @param  array  $head  The head node
     * @param  ParseStateV4  $state  The current parse state
     * @return array|null The created edge, or null if creation failed
     */
    private function createEdge(array $dependent, array $head, ParseStateV4 $state): ?array
    {
        $relation = $this->determineRelation($dependent, $head);

        if (! $relation) {
            return null;
        }

        return [
            'id' => $this->generateEdgeId($state),
            'sourceId' => $this->getNodeId($head),
            'targetId' => $this->getNodeId($dependent),
            'relation' => $relation,
            'stage' => 'v4_incremental',
            'position' => $state->currentPosition,
        ];
    }

    /**
     * Determine the relation type based on CE label combinations
     *
     * Maps CE label patterns to Universal Dependencies relations
     *
     * @param  array  $dependent  The dependent node
     * @param  array  $head  The head node
     * @return string|null The relation type, or null if undetermined
     */
    private function determineRelation(array $dependent, array $head): ?string
    {
        $depPos = $dependent['pos'] ?? null;
        $headPos = $head['pos'] ?? null;

        // === PHRASAL RELATIONS ===

        $depPhrasalCE = $dependent['phrasalCE'] ?? null;
        $headPhrasalCE = $head['phrasalCE'] ?? null;

        // Modifiers -> Head
        if ($depPhrasalCE === 'Mod' && $headPhrasalCE === 'Head') {
            if ($depPos === 'DET') {
                return 'det';
            }
            if ($depPos === 'ADJ') {
                return 'amod';
            }
            if ($depPos === 'NUM') {
                return 'nummod';
            }

            return 'mod';
        }

        // Adposition
        if ($depPhrasalCE === 'Adp') {
            return 'case';
        }

        // Coordinating conjunction
        if ($depPhrasalCE === 'Conj') {
            return 'cc';
        }

        // === CLAUSAL RELATIONS ===

        $depClausalCE = $dependent['clausalCE'] ?? null;
        $headClausalCE = $head['clausalCE'] ?? null;

        // Arguments -> Predicate
        if ($depClausalCE === 'Arg' && $headClausalCE === 'Pred') {
            // Determine argument type based on position and features
            if ($this->isSubject($dependent, $head)) {
                return 'nsubj';
            }
            if ($this->isDirectObject($dependent, $head)) {
                return 'obj';
            }
            if ($this->isIndirectObject($dependent, $head)) {
                return 'iobj';
            }

            return 'obl'; // Default oblique argument
        }

        // Clause peripherals -> Predicate
        if ($depClausalCE === 'CPP' && $headClausalCE === 'Pred') {
            if ($depPos === 'AUX') {
                return 'aux';
            }
            if ($depPos === 'ADV') {
                return 'advmod';
            }

            return 'advmod';
        }

        // Frame-peripheral modifiers
        if ($depClausalCE === 'FPM') {
            return 'obl';
        }

        // Genitive
        if ($depClausalCE === 'Gen') {
            return 'nmod:poss';
        }

        // === SENTENTIAL RELATIONS ===

        $depSententialCE = $dependent['sententialCE'] ?? null;

        // Relative clause
        if ($depSententialCE === 'Rel') {
            return 'acl:relcl';
        }

        // Complement clause
        if ($depSententialCE === 'Comp') {
            if ($this->hasSubjectControl($head)) {
                return 'xcomp';
            }

            return 'ccomp';
        }

        // Adverbial clause
        if ($depSententialCE === 'Adv') {
            return 'advcl';
        }

        // No matching pattern - return null to prevent edge creation
        return null;
    }

    /**
     * Check if argument is a subject
     */
    private function isSubject(array $arg, array $pred): bool
    {
        // Subject typically comes before predicate and has nominative case
        $argPos = $this->getNodePosition($arg);
        $predPos = $this->getNodePosition($pred);

        if ($argPos < $predPos) {
            // Check for nominative case if available
            $case = $arg['features']['Case'] ?? null;
            if ($case === 'Nom' || $case === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if argument is a direct object
     */
    private function isDirectObject(array $arg, array $pred): bool
    {
        // Direct object typically comes after predicate
        $argPos = $this->getNodePosition($arg);
        $predPos = $this->getNodePosition($pred);

        if ($argPos > $predPos) {
            // Check for accusative case if available
            $case = $arg['features']['Case'] ?? null;
            if ($case === 'Acc' || $case === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if argument is an indirect object
     */
    private function isIndirectObject(array $arg, array $pred): bool
    {
        // Indirect object typically has dative case or is introduced by preposition
        $case = $arg['features']['Case'] ?? null;

        return $case === 'Dat';
    }

    /**
     * Check if the head has subject control
     *
     * Subject control verbs (like "querer", "tentar") use xcomp
     * instead of ccomp for their complement clauses
     */
    private function hasSubjectControl(array $head): bool
    {
        $lemma = $head['lemma'] ?? null;

        $subjectControlVerbs = [
            'querer', 'tentar', 'conseguir', 'precisar',
            'preferir', 'esperar', 'pretender',
        ];

        return $lemma && in_array($lemma, $subjectControlVerbs);
    }

    /**
     * Get node position (handles both single-token and MWE nodes)
     */
    private function getNodePosition(array $node): int
    {
        if (isset($node['position'])) {
            return $node['position'];
        }

        if (isset($node['startPosition'])) {
            return $node['startPosition'];
        }

        return 0;
    }

    /**
     * Get node ID (generates one if not present)
     */
    private function getNodeId(array $node): string
    {
        if (isset($node['id'])) {
            return $node['id'];
        }

        // Generate ID from construction name and position
        $name = $node['constructionName'] ?? 'unknown';
        $pos = $this->getNodePosition($node);

        return "{$name}_{$pos}";
    }

    /**
     * Check if two nodes are the same
     */
    private function isSameNode(array $node1, array $node2): bool
    {
        return $this->getNodeId($node1) === $this->getNodeId($node2);
    }

    /**
     * Generate unique edge ID
     */
    private function generateEdgeId(ParseStateV4 $state): string
    {
        return 'edge_'.(count($state->confirmedEdges) + 1);
    }
}

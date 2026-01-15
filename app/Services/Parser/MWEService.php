<?php

namespace App\Services\Parser;

use App\Repositories\Parser\MWE;
use App\Repositories\Parser\ParseEdge;
use App\Repositories\Parser\ParseNode;

class MWEService
{
    /**
     * Generate prefix hierarchy for an MWE
     */
    public function generatePrefixHierarchy(object $mwe): array
    {
        $components = MWE::getComponents($mwe);
        $prefixes = [];

        // Generate all prefixes (1-word, 2-word, ..., n-word)
        for ($i = 1; $i <= count($components); $i++) {
            $prefixComponents = array_slice($components, 0, $i);
            $prefixPhrase = implode(' ', $prefixComponents);

            $prefixes[] = (object) [
                'phrase' => $prefixPhrase,
                'components' => $prefixComponents,
                'threshold' => $i,
                'isComplete' => ($i === count($components)),
            ];
        }

        return $prefixes;
    }

    /**
     * Instantiate MWE prefix nodes when first word appears
     */
    public function instantiateMWENodes(
        string $firstWord,
        int $idParserGraph,
        int $idGrammarGraph,
        int $position
    ): array {
        $instantiatedNodes = [];
        $mwes = MWE::getStartingWith($idGrammarGraph, $firstWord);

        foreach ($mwes as $mwe) {
            $components = MWE::getComponents($mwe);

            // Generate all prefix nodes
            for ($i = 2; $i <= count($components); $i++) {
                $prefixComponents = array_slice($components, 0, $i);
                $prefixPhrase = implode(' ', $prefixComponents);

                // Create prefix node with threshold = i, activation = 1
                $idNode = ParseNode::create([
                    'idParserGraph' => $idParserGraph,
                    'label' => $prefixPhrase,
                    'type' => 'MWE',
                    'threshold' => $i,
                    'activation' => 1,
                    'isFocus' => false,
                    'positionInSentence' => $position,
                    'idMWE' => $mwe->idMWE,
                ]);

                $instantiatedNodes[] = ParseNode::byId($idNode);

                if (config('parser.logging.logMWE', false)) {
                    logger()->info('MWE: Instantiated prefix node', [
                        'phrase' => $prefixPhrase,
                        'threshold' => $i,
                        'idMWE' => $mwe->idMWE,
                    ]);
                }
            }
        }

        return $instantiatedNodes;
    }

    /**
     * Increment activation for MWE node
     */
    public function incrementActivation(object $mweNode, string $word): void
    {
        ParseNode::incrementActivation($mweNode->idParserNode);

        if (config('parser.logging.logMWE', false)) {
            logger()->info('MWE: Incremented activation', [
                'phrase' => $mweNode->label,
                'activation' => $mweNode->activation + 1,
                'threshold' => $mweNode->threshold,
                'word' => $word,
            ]);
        }
    }

    /**
     * Check if word matches expected next component
     */
    public function matchesNextComponent(object $mweNode, string $word): bool
    {
        if (! $mweNode->idMWE) {
            return false;
        }

        $mwe = MWE::byId($mweNode->idMWE);
        $components = MWE::getComponents($mwe);

        // Current activation indicates how many components we've seen
        $nextIndex = $mweNode->activation;

        if ($nextIndex >= count($components)) {
            return false;
        }

        return strtolower($components[$nextIndex]) === strtolower($word);
    }

    /**
     * Aggregate MWE when threshold is reached
     */
    public function aggregateMWE(object $mweNode, int $idParserGraph): void
    {
        // Mark as focus
        ParseNode::setFocus($mweNode->idParserNode, true);

        // Transfer all incoming links from first component to MWE node
        // Find the first word node at the same position
        $firstWordNodes = ParseNode::listByParseGraph($idParserGraph);

        foreach ($firstWordNodes as $node) {
            if ($node->positionInSentence === $mweNode->positionInSentence &&
                $node->type !== 'MWE' &&
                $node->idParserNode !== $mweNode->idParserNode) {

                // Transfer edges from first word to MWE
                $this->transferLinks($node, $mweNode, $idParserGraph);
                break;
            }
        }

        if (config('parser.logging.logMWE', false)) {
            logger()->info('MWE: Aggregated', [
                'phrase' => $mweNode->label,
                'activation' => $mweNode->activation,
                'threshold' => $mweNode->threshold,
            ]);
        }
    }

    /**
     * Transfer all links from one node to another
     */
    public function transferLinks(object $fromNode, object $toNode, int $idParserGraph): void
    {
        // Get all incoming edges to fromNode
        $incomingEdges = ParseEdge::listByTargetNode($fromNode->idParserNode);

        foreach ($incomingEdges as $edge) {
            // Create new edge to toNode if it doesn't exist
            ParseEdge::createIfNotExists([
                'idParserGraph' => $idParserGraph,
                'idSourceNode' => $edge->idSourceNode,
                'idTargetNode' => $toNode->idParserNode,
                'edgeType' => $edge->edgeType,
                'weight' => $edge->weight,
            ]);
        }

        // Get all outgoing edges from fromNode
        $outgoingEdges = ParseEdge::listBySourceNode($fromNode->idParserNode);

        foreach ($outgoingEdges as $edge) {
            // Create new edge from toNode if it doesn't exist
            ParseEdge::createIfNotExists([
                'idParserGraph' => $idParserGraph,
                'idSourceNode' => $toNode->idParserNode,
                'idTargetNode' => $edge->idTargetNode,
                'edgeType' => $edge->edgeType,
                'weight' => $edge->weight,
            ]);
        }

        if (config('parser.logging.logMWE', false)) {
            logger()->info('MWE: Transferred links', [
                'from' => $fromNode->label,
                'to' => $toNode->label,
                'incomingEdges' => count($incomingEdges),
                'outgoingEdges' => count($outgoingEdges),
            ]);
        }
    }

    /**
     * Get all active MWE prefixes for a parse graph
     */
    public function getActivePrefixes(int $idParserGraph): array
    {
        return ParseNode::getMWEPrefixes($idParserGraph);
    }

    /**
     * Check if MWE was interrupted
     */
    public function isInterrupted(object $mweNode, int $currentPosition): bool
    {
        // MWE is interrupted if current word position is not sequential
        $expectedPosition = $mweNode->positionInSentence + $mweNode->activation;

        return $currentPosition !== $expectedPosition;
    }

    /**
     * Handle competing MWEs with shared prefixes
     */
    public function resolveCompetition(array $mweNodes): ?object
    {
        if (empty($mweNodes)) {
            return null;
        }

        $strategy = config('parser.mwe.competitionStrategy', 'longest');

        switch ($strategy) {
            case 'longest':
                // Return MWE with highest threshold
                usort($mweNodes, function ($a, $b) {
                    return $b->threshold <=> $a->threshold;
                });

                return $mweNodes[0];

            case 'first':
                // Return first MWE
                return $mweNodes[0];

            case 'all':
                // Return all (for ambiguous parses)
                return $mweNodes;

            default:
                return $mweNodes[0];
        }
    }

    /**
     * Detect simple (fixed-word) MWEs in a PhrasalCENode array
     *
     * Scans the node array for sequential matches of fixed MWE components.
     * Returns match information similar to construction detection.
     *
     * @param  array  $nodes  Array of PhrasalCENode objects
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @return array Array of MWE match objects with startPosition, components, mweData
     */
    public function detectSimpleMWEs(array $nodes, int $idGrammarGraph): array
    {
        $matches = [];
        $nodeCount = count($nodes);

        // Get all simple MWEs for this grammar
        $allMWEs = MWE::listByFormat($idGrammarGraph, 'simple');

        // Scan through nodes looking for MWE matches
        for ($i = 0; $i < $nodeCount; $i++) {
            $node = $nodes[$i];

            // Get MWEs starting with this word
            $candidateMWEs = array_filter($allMWEs, function ($mwe) use ($node) {
                return strtolower($mwe->firstWord) === strtolower($node->word);
            });

            foreach ($candidateMWEs as $mwe) {
                $components = MWE::getComponents($mwe);
                $mweLength = count($components);

                // Check if we have enough remaining nodes
                if ($i + $mweLength > $nodeCount) {
                    continue;
                }

                // Check if all components match sequentially
                $allMatch = true;
                for ($j = 0; $j < $mweLength; $j++) {
                    $expectedWord = strtolower($components[$j]);
                    $actualWord = strtolower($nodes[$i + $j]->word);

                    if ($expectedWord !== $actualWord) {
                        $allMatch = false;
                        break;
                    }
                }

                if ($allMatch) {
                    // Found a match!
                    $matches[] = (object) [
                        'startPosition' => $i,
                        'length' => $mweLength,
                        'mwe' => $mwe,
                        'phrase' => $mwe->phrase,
                        'semanticType' => $mwe->semanticType,
                    ];

                    // Skip ahead past this MWE to avoid overlapping matches
                    // (Only longest match wins - greedy matching)
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Detect variable (extended-format) MWEs in a PhrasalCENode array
     *
     * Uses two-phase detection:
     * - Phase 1: Anchored patterns (with at least one fixed word) - Fast lookup
     * - Phase 2: Fully variable patterns (no fixed words) - Checked at every position
     *
     * Important: Implements greedy matching - once a position is consumed by a match,
     * later patterns cannot overlap that region.
     *
     * @param  array  $nodes  Array of PhrasalCENode objects
     * @param  int  $idGrammarGraph  Grammar graph ID
     * @return array Array of MWE match objects
     */
    public function detectVariableMWEs(array $nodes, int $idGrammarGraph): array
    {
        $matches = [];
        $nodeCount = count($nodes);
        $matchedPositions = []; // Track which positions have been consumed

        // Phase 1: Anchored patterns (with at least one fixed word)
        // These can be looked up efficiently by anchor word
        for ($i = 0; $i < $nodeCount; $i++) {
            // Skip if this position is already part of a match
            if (isset($matchedPositions[$i])) {
                continue;
            }

            $node = $nodes[$i];

            // Get extended MWEs anchored by this word
            $anchoredMWEs = MWE::getByAnchorWord($idGrammarGraph, $node->word);

            foreach ($anchoredMWEs as $mwe) {
                $match = $this->tryMatchVariableMWE($mwe, $nodes, $i);

                if ($match !== null) {
                    // Check if any position in this match is already consumed
                    $overlaps = false;
                    for ($j = $match->startPosition; $j < $match->startPosition + $match->length; $j++) {
                        if (isset($matchedPositions[$j])) {
                            $overlaps = true;
                            break;
                        }
                    }

                    if (!$overlaps) {
                        $matches[] = $match;

                        // Mark all positions in this match as consumed
                        for ($j = $match->startPosition; $j < $match->startPosition + $match->length; $j++) {
                            $matchedPositions[$j] = true;
                        }

                        // Only take first matching pattern at each position (greedy)
                        break;
                    }
                }
            }
        }

        // Phase 2: Fully variable patterns (no fixed word)
        // These must be checked at every position
        $fullyVariableMWEs = MWE::getFullyVariable($idGrammarGraph);

        foreach ($fullyVariableMWEs as $mwe) {
            for ($i = 0; $i < $nodeCount; $i++) {
                // Skip if this position is already part of a match
                if (isset($matchedPositions[$i])) {
                    continue;
                }

                $match = $this->tryMatchVariableMWE($mwe, $nodes, $i);

                if ($match !== null) {
                    // Check if any position in this match is already consumed
                    $overlaps = false;
                    for ($j = $match->startPosition; $j < $match->startPosition + $match->length; $j++) {
                        if (isset($matchedPositions[$j])) {
                            $overlaps = true;
                            break;
                        }
                    }

                    if (!$overlaps) {
                        $matches[] = $match;

                        // Mark all positions in this match as consumed
                        for ($j = $match->startPosition; $j < $match->startPosition + $match->length; $j++) {
                            $matchedPositions[$j] = true;
                        }

                        // Skip past this match to avoid overlaps
                        $i += $match->length - 1;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Try to match a variable MWE pattern at a specific position
     *
     * @param  object  $mwe  MWE database object
     * @param  array  $nodes  Array of PhrasalCENode objects
     * @param  int  $anchorPosition  Position of the anchor word (or start position for fully variable)
     * @return object|null Match object if successful, null otherwise
     */
    private function tryMatchVariableMWE(object $mwe, array $nodes, int $anchorPosition): ?object
    {
        // Get normalized components (handles both simple and extended formats)
        $components = MWE::getParsedComponents($mwe);
        $componentCount = count($components);

        // Calculate pattern start position based on anchor
        $anchorOffset = $mwe->anchorPosition ?? 0;
        $patternStartPosition = $anchorPosition - $anchorOffset;

        // Validate bounds
        if ($patternStartPosition < 0 ||
            $patternStartPosition + $componentCount > count($nodes)) {
            return null;
        }

        // Try to match all components sequentially
        $activation = 0;
        $matchedWords = [];

        for ($i = 0; $i < $componentCount; $i++) {
            $component = $components[$i];
            $node = $nodes[$patternStartPosition + $i];

            // Use the component type's match logic
            if (MWE::componentMatchesToken($component, $node)) {
                $activation++;
                $matchedWords[] = $node->word;
            } else {
                // Sequence interrupted - not a match
                break;
            }
        }

        // Check if fully matched (reached threshold)
        if ($activation !== $componentCount) {
            return null;
        }

        // Complete match found!
        $match = (object) [
            'startPosition' => $patternStartPosition,
            'length' => $componentCount,
            'mwe' => $mwe,
            'phrase' => $mwe->phrase,
            'semanticType' => $mwe->semanticType,
            'matchedWords' => $matchedWords,
        ];

        // Debug logging
        if (config('parser.logging.logMWE', false)) {
            logger()->info('Variable MWE Match Found', [
                'phrase' => $mwe->phrase,
                'startPosition' => $patternStartPosition,
                'length' => $componentCount,
                'matchedWords' => $matchedWords,
                'components' => MWE::getParsedComponents($mwe),
            ]);
        }

        return $match;
    }
}

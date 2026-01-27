<?php

namespace App\Services\Parser;

use App\Models\Parser\PhrasalCENode;

/**
 * BNF Graph Matcher
 *
 * Traverses compiled BNF graphs to match against token sequences.
 * Uses backtracking to handle non-deterministic paths (optionals, alternatives).
 *
 * Supported node types:
 * - LITERAL: Match specific words
 * - SLOT: Match POS tags with optional constraints
 * - CE_SLOT: Match constructional element (CE) labels
 * - COMBINED_SLOT: Match both POS and CE labels
 * - WILDCARD: Match any token
 */
class BNFMatcher
{
    private int $maxBacktrackingDepth = 100;

    /**
     * Match tokens against compiled graph
     *
     * @param  array  $graph  Compiled graph from PatternCompiler
     * @param  array  $tokens  Array of PhrasalCENode objects
     * @param  int  $startPos  Starting position in token array
     * @return array|null Match result or null if no match
     */
    public function match(array $graph, array $tokens, int $startPos = 0): ?array
    {
        $result = [
            'matched' => false,
            'slots' => [],
            'span' => [],
            'endPosition' => $startPos,
            'matchedTokens' => [],
        ];

        // Find START node
        $startNode = $this->findNodeByType($graph, 'START');
        if (! $startNode) {
            return null;
        }

        // Attempt traversal
        if ($this->traverse($graph, $startNode, $tokens, $startPos, $result, 0)) {
            $result['matched'] = true;

            return $result;
        }

        return null;
    }

    /**
     * Traverse graph recursively with backtracking
     *
     * @param  array  $graph  The compiled graph
     * @param  string  $nodeId  Current node ID
     * @param  array  $tokens  Token array
     * @param  int  $tokenIndex  Current token position
     * @param  array  $result  Reference to result array
     * @param  int  $depth  Backtracking depth (prevents infinite loops)
     * @return bool True if path succeeds
     */
    private function traverse(
        array $graph,
        string $nodeId,
        array $tokens,
        int $tokenIndex,
        array &$result,
        int $depth
    ): bool {
        // Prevent infinite backtracking
        if ($depth > $this->maxBacktrackingDepth) {
            return false;
        }

        $node = $graph['nodes'][$nodeId] ?? null;
        if (! $node) {
            return false;
        }

        // END node - success if we can end here
        if ($node['type'] === 'END') {
            $result['endPosition'] = $tokenIndex;

            return true;
        }

        // Match current node against token
        $consumed = $this->matchNode($node, $tokens, $tokenIndex, $result);

        // If match failed, stop this path
        if ($consumed === false) {
            return false;
        }

        // Get all outgoing edges
        $outEdges = $this->getOutgoingEdges($graph, $nodeId);

        // Try each outgoing edge (backtracking)
        foreach ($outEdges as $edge) {
            // Save state for backtracking
            $savedState = $this->saveState($result);

            // Traverse next node
            if ($this->traverse($graph, $edge['to'], $tokens, $tokenIndex + $consumed, $result, $depth + 1)) {
                return true; // Success!
            }

            // Restore state on failure
            $this->restoreState($result, $savedState);
        }

        // All paths failed
        return false;
    }

    /**
     * Match a single node against current token
     *
     * @return int|false Number of tokens consumed, or false if no match
     */
    private function matchNode(array $node, array $tokens, int $tokenIndex, array &$result)
    {
        // No more tokens available
        if ($tokenIndex >= count($tokens)) {
            // Only control nodes can match with no tokens
            return in_array($node['type'], ['START', 'END', 'INTERMEDIATE', 'REP_CHECK']) ? 0 : false;
        }

        $token = $tokens[$tokenIndex];

        return match ($node['type']) {
            'START' => 0, // Control node, consumes nothing
            'END' => 0, // Control node, consumes nothing
            'INTERMEDIATE' => 0, // Control node, consumes nothing (used by optionals/alternatives)
            'REP_CHECK' => 0, // Control node, consumes nothing
            'LITERAL' => $this->matchLiteral($node, $token, $result),
            'SLOT' => $this->matchSlot($node, $token, $result),
            'CE_SLOT' => $this->matchCESlot($node, $token, $result),
            'COMBINED_SLOT' => $this->matchCombinedSlot($node, $token, $result),
            'WILDCARD' => $this->matchWildcard($token, $result),
            default => false,
        };
    }

    /**
     * Match literal word
     */
    private function matchLiteral(array $node, PhrasalCENode $token, array &$result): int|false
    {
        $nodeValue = strtolower($node['value']);
        $tokenWord = strtolower($token->word);

        if ($nodeValue === $tokenWord) {
            $result['matchedTokens'][] = $token->word;

            return 1; // Consumed 1 token
        }

        return false;
    }

    /**
     * Match POS slot
     */
    private function matchSlot(array $node, PhrasalCENode $token, array &$result): int|false
    {
        // Check POS match
        if ($token->pos !== $node['pos']) {
            return false;
        }

        // Check constraint if present
        if (isset($node['constraint']) && $node['constraint'] !== null) {
            if (! $this->checkConstraint($node['constraint'], $token)) {
                return false;
            }
        }

        // Capture slot value
        $slotKey = $node['pos'].(isset($node['constraint']) ? ':'.$node['constraint'] : '');
        $result['slots'][$slotKey] = $token->word;
        $result['matchedTokens'][] = $token->word;

        return 1; // Consumed 1 token
    }

    /**
     * Match CE slot
     */
    private function matchCESlot(array $node, PhrasalCENode $token, array &$result): int|false
    {
        $expectedCE = $node['ce_label'] ?? '';
        $tier = $node['ce_tier'] ?? '';

        if (empty($expectedCE) || empty($tier)) {
            return false;
        }

        // Check CE annotation at the specified tier
        $actualCE = match ($tier) {
            'phrasal' => $token->phrasalCE ?? null,
            'clausal' => $token->clausalCE ?? null,
            'sentential' => $token->sententialCE ?? null,
            default => null,
        };

        if ($actualCE === $expectedCE) {
            $result['matchedTokens'][] = $token->word;

            return 1; // Consumed 1 token
        }

        return false;
    }

    /**
     * Match combined POS+CE slot
     */
    private function matchCombinedSlot(array $node, PhrasalCENode $token, array &$result): int|false
    {
        $expectedPos = $node['pos'] ?? '';
        $expectedCE = $node['ce_label'] ?? '';
        $tier = $node['ce_tier'] ?? '';
        $constraint = $node['constraint'] ?? null;

        if (empty($expectedPos) || empty($expectedCE) || empty($tier)) {
            return false;
        }

        // Check POS match
        if ($token->pos !== $expectedPos) {
            return false;
        }

        // Check constraint if present
        if ($constraint !== null && ! $this->checkConstraint($constraint, $token)) {
            return false;
        }

        // Check CE match
        $actualCE = match ($tier) {
            'phrasal' => $token->phrasalCE ?? null,
            'clausal' => $token->clausalCE ?? null,
            'sentential' => $token->sententialCE ?? null,
            default => null,
        };

        if ($actualCE !== $expectedCE) {
            return false;
        }

        // Both POS and CE matched
        $slotKey = $expectedPos.($constraint ? ':'.$constraint : '').'@'.$expectedCE;
        $result['slots'][$slotKey] = $token->word;
        $result['matchedTokens'][] = $token->word;

        return 1; // Consumed 1 token
    }

    /**
     * Match wildcard (any token)
     */
    private function matchWildcard(PhrasalCENode $token, array &$result): int
    {
        $result['matchedTokens'][] = $token->word;

        return 1; // Consumed 1 token
    }

    /**
     * Check feature constraint on token
     *
     * Supports both simple constraints (e.g., "inf") and complex constraints (e.g., "Gender=Masc,Number=Plur")
     */
    private function checkConstraint(string $constraint, PhrasalCENode $token): bool
    {
        $features = $token->features['lexical'] ?? [];

        // Simple constraint shortcuts
        $simpleConstraints = [
            'inf' => ($features['VerbForm'] ?? null) === 'Inf',
            'fin' => ($features['VerbForm'] ?? null) === 'Fin',
            'part' => ($features['VerbForm'] ?? null) === 'Part',
            'ger' => ($features['VerbForm'] ?? null) === 'Ger',
            'sing' => ($features['Number'] ?? null) === 'Sing',
            'plur' => ($features['Number'] ?? null) === 'Plur',
        ];

        if (isset($simpleConstraints[$constraint])) {
            return $simpleConstraints[$constraint];
        }

        // Complex constraint: Feature=Value or Feature1=Value1,Feature2=Value2
        if (str_contains($constraint, '=')) {
            $constraints = explode(',', $constraint);
            foreach ($constraints as $c) {
                [$featureName, $featureValue] = explode('=', trim($c), 2);
                $featureName = trim($featureName);
                $featureValue = trim($featureValue);

                if (($features[$featureName] ?? null) !== $featureValue) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get all outgoing edges from a node
     */
    private function getOutgoingEdges(array $graph, string $nodeId): array
    {
        $edges = [];

        foreach ($graph['edges'] as $edge) {
            if ($edge['from'] === $nodeId) {
                $edges[] = $edge;
            }
        }

        // Sort: non-bypass edges first (prefer main path)
        usort($edges, function ($a, $b) {
            $aBypass = isset($a['bypass']) && $a['bypass'];
            $bBypass = isset($b['bypass']) && $b['bypass'];

            return $aBypass <=> $bBypass;
        });

        return $edges;
    }

    /**
     * Save matching state for backtracking
     */
    private function saveState(array $result): array
    {
        return [
            'slots' => $result['slots'],
            'span' => $result['span'],
            'matchedTokens' => $result['matchedTokens'],
            'endPosition' => $result['endPosition'],
        ];
    }

    /**
     * Restore matching state after failed path
     */
    private function restoreState(array &$result, array $savedState): void
    {
        $result['slots'] = $savedState['slots'];
        $result['span'] = $savedState['span'];
        $result['matchedTokens'] = $savedState['matchedTokens'];
        $result['endPosition'] = $savedState['endPosition'];
    }

    /**
     * Find first node of given type
     */
    private function findNodeByType(array $graph, string $type): ?string
    {
        foreach ($graph['nodes'] as $nodeId => $node) {
            if ($node['type'] === $type) {
                return $nodeId;
            }
        }

        return null;
    }

    /**
     * Set maximum backtracking depth
     */
    public function setMaxBacktrackingDepth(int $depth): void
    {
        $this->maxBacktrackingDepth = $depth;
    }

    /**
     * Match all occurrences in token array
     *
     * @return array Array of match results
     */
    public function matchAll(array $graph, array $tokens): array
    {
        $matches = [];
        $tokenCount = count($tokens);

        for ($i = 0; $i < $tokenCount; $i++) {
            $match = $this->match($graph, $tokens, $i);

            if ($match && $match['matched']) {
                $matches[] = array_merge($match, ['startPosition' => $i]);

                // Skip past matched tokens to avoid overlaps
                $i = $match['endPosition'] - 1;
            }
        }

        return $matches;
    }

    /**
     * Check if graph matches at specific position
     */
    public function matchesAt(array $graph, array $tokens, int $position): bool
    {
        $match = $this->match($graph, $tokens, $position);

        return $match && $match['matched'];
    }

    /**
     * Get match length (number of tokens consumed)
     */
    public static function getMatchLength(array $match): int
    {
        if (! $match['matched']) {
            return 0;
        }

        return $match['endPosition'] - ($match['startPosition'] ?? 0);
    }

    /**
     * Get matched text (concatenated tokens)
     */
    public static function getMatchedText(array $match): string
    {
        return implode(' ', $match['matchedTokens'] ?? []);
    }
}

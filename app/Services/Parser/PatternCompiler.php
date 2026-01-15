<?php

namespace App\Services\Parser;

use Exception;

/**
 * Pattern Compiler for BNF Constructions
 *
 * Compiles BNF-style patterns into executable graph structures.
 *
 * Supported notation:
 * - Literals: word (fixed word match)
 * - POS Slots: {POS} (match UDPOS tag)
 * - Constrained Slots: {POS:constraint} (match POS with feature)
 * - Wildcards: {*} (match any token)
 * - Optional: [element] (0 or 1 times)
 * - Alternatives: (A | B | C) (one of)
 * - Repetition: A+ (1 or more), A* (0 or more)
 * - Grouping: (A B C) (sequence)
 *
 * Example: [{NUM}] mil [, ] [{NUM}] [e {NUM}]
 */
class PatternCompiler
{
    private int $nodeCounter = 0;

    /**
     * Compile a BNF pattern string into a graph structure
     *
     * @param  string  $pattern  BNF pattern string
     * @return array Graph with 'nodes' and 'edges' arrays
     *
     * @throws Exception If pattern is invalid
     */
    public function compile(string $pattern): array
    {
        $this->nodeCounter = 0;

        // Tokenize pattern
        $tokens = $this->tokenize($pattern);

        // Build graph
        $graph = [
            'nodes' => [],
            'edges' => [],
        ];

        $startNode = $this->newNodeId();
        $endNode = $this->newNodeId();

        $graph['nodes'][$startNode] = ['type' => 'START'];
        $graph['nodes'][$endNode] = ['type' => 'END'];

        // Build from tokens
        $lastNode = $this->buildSequence($tokens, 0, $startNode, $endNode, $graph);

        // Ensure connection to end
        if ($lastNode !== $endNode) {
            $graph['edges'][] = ['from' => $lastNode, 'to' => $endNode];
        }

        return $graph;
    }

    /**
     * Tokenize pattern string into structured tokens
     *
     * @return array Array of token objects
     */
    public function tokenize(string $pattern): array
    {
        $tokens = [];
        $length = strlen($pattern);
        $i = 0;

        while ($i < $length) {
            $char = $pattern[$i];

            // Skip whitespace
            if (ctype_space($char)) {
                $i++;

                continue;
            }

            // Optional [...]
            if ($char === '[') {
                $content = $this->extractBracketed($pattern, $i, '[', ']');
                $tokens[] = [
                    'type' => 'OPTIONAL',
                    'content' => trim($content),
                ];
                $i += strlen($content) + 2; // +2 for brackets

                continue;
            }

            // Slot or wildcard {...}
            if ($char === '{') {
                $content = $this->extractBracketed($pattern, $i, '{', '}');
                $tokens[] = $this->parseSlot($content);
                $i += strlen($content) + 2; // +2 for braces

                continue;
            }

            // Alternative or group (...)
            if ($char === '(') {
                $content = $this->extractBracketed($pattern, $i, '(', ')');
                $tokens[] = $this->parseParenthetical($content);
                $i += strlen($content) + 2; // +2 for parens

                continue;
            }

            // Repetition operators +, *
            if (in_array($char, ['+', '*']) && count($tokens) > 0) {
                $lastToken = array_pop($tokens);
                $tokens[] = [
                    'type' => 'REPETITION',
                    'operator' => $char,
                    'content' => $lastToken,
                ];
                $i++;

                continue;
            }

            // Literal word
            $word = $this->extractWord($pattern, $i);
            if ($word !== '') {
                $tokens[] = [
                    'type' => 'LITERAL',
                    'value' => $word,
                ];
                $i += strlen($word);

                continue;
            }

            // Unknown character - skip
            $i++;
        }

        return $tokens;
    }

    /**
     * Validate pattern syntax
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate(string $pattern): array
    {
        $errors = [];

        try {
            $tokens = $this->tokenize($pattern);

            // Check bracket matching
            $this->checkBracketMatching($pattern, $errors);

            // Validate each token
            foreach ($tokens as $token) {
                $this->validateToken($token, $errors);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Export graph to DOT format for Graphviz visualization
     */
    public function toDot(array $graph): string
    {
        $dot = "digraph BNFPattern {\n";
        $dot .= "  rankdir=LR;\n";
        $dot .= "  node [shape=box];\n\n";

        // Nodes
        foreach ($graph['nodes'] as $nodeId => $node) {
            $label = $this->formatNodeLabel($node);
            $shape = $this->getNodeShape($node);
            $dot .= "  $nodeId [label=\"$label\", shape=$shape];\n";
        }

        $dot .= "\n";

        // Edges
        foreach ($graph['edges'] as $edge) {
            $label = $edge['label'] ?? '';
            $style = isset($edge['bypass']) && $edge['bypass'] ? 'dashed' : 'solid';
            $dot .= "  {$edge['from']} -> {$edge['to']} [style=$style, label=\"$label\"];\n";
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * Export graph to JSON format
     */
    public function toJson(array $graph): string
    {
        return json_encode($graph, JSON_PRETTY_PRINT);
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Build sequence of tokens into graph
     */
    private function buildSequence(array $tokens, int $startIdx, string $fromNode, string $toNode, array &$graph): string
    {
        if ($startIdx >= count($tokens)) {
            $graph['edges'][] = ['from' => $fromNode, 'to' => $toNode];

            return $toNode;
        }

        $currentNode = $fromNode;

        for ($i = $startIdx; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $isLast = ($i === count($tokens) - 1);
            $nextNode = $isLast ? $toNode : null;  // Let buildToken create intermediates

            $currentNode = $this->buildToken($token, $currentNode, $nextNode, $graph);
        }

        // Connect last node to end if needed
        if ($currentNode !== $toNode) {
            $graph['edges'][] = ['from' => $currentNode, 'to' => $toNode];
        }

        return $currentNode;
    }

    /**
     * Build single token into graph
     */
    private function buildToken(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        switch ($token['type']) {
            case 'LITERAL':
                return $this->buildLiteral($token, $fromNode, $toNode, $graph);

            case 'SLOT':
                return $this->buildSlot($token, $fromNode, $toNode, $graph);

            case 'WILDCARD':
                return $this->buildWildcard($fromNode, $toNode, $graph);

            case 'OPTIONAL':
                return $this->buildOptional($token, $fromNode, $toNode, $graph);

            case 'ALTERNATIVE':
                return $this->buildAlternative($token, $fromNode, $toNode, $graph);

            case 'REPETITION':
                return $this->buildRepetition($token, $fromNode, $toNode, $graph);

            default:
                return $toNode;
        }
    }

    /**
     * Build literal word node
     */
    private function buildLiteral(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = [
            'type' => 'LITERAL',
            'value' => strtolower($token['value']),
        ];

        $graph['edges'][] = ['from' => $fromNode, 'to' => $nodeId];

        // If toNode specified, connect to it
        if ($toNode !== null) {
            $graph['edges'][] = ['from' => $nodeId, 'to' => $toNode];
        }

        return $nodeId;
    }

    /**
     * Build POS slot node
     */
    private function buildSlot(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = [
            'type' => 'SLOT',
            'pos' => $token['pos'],
            'constraint' => $token['constraint'] ?? null,
        ];

        $graph['edges'][] = ['from' => $fromNode, 'to' => $nodeId];

        // If toNode specified, connect to it
        if ($toNode !== null) {
            $graph['edges'][] = ['from' => $nodeId, 'to' => $toNode];
        }

        return $nodeId;
    }

    /**
     * Build wildcard node
     */
    private function buildWildcard(string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = ['type' => 'WILDCARD'];

        $graph['edges'][] = ['from' => $fromNode, 'to' => $nodeId];

        // If toNode specified, connect to it
        if ($toNode !== null) {
            $graph['edges'][] = ['from' => $nodeId, 'to' => $toNode];
        }

        return $nodeId;
    }

    /**
     * Build optional element [...]
     * Creates bypass path from fromNode directly to toNode
     */
    private function buildOptional(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        // Create intermediate node if toNode not specified
        if ($toNode === null) {
            $toNode = $this->newNodeId();
            $graph['nodes'][$toNode] = ['type' => 'INTERMEDIATE'];
        }

        // Parse content
        $contentTokens = $this->tokenize($token['content']);

        // Build the optional path
        $this->buildSequence($contentTokens, 0, $fromNode, $toNode, $graph);

        // Add bypass edge (optional means can skip)
        $graph['edges'][] = [
            'from' => $fromNode,
            'to' => $toNode,
            'bypass' => true,
        ];

        return $toNode;
    }

    /**
     * Build alternative (A | B | C)
     * Creates parallel paths from fromNode to toNode
     */
    private function buildAlternative(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        // Create intermediate node if toNode not specified
        if ($toNode === null) {
            $toNode = $this->newNodeId();
            $graph['nodes'][$toNode] = ['type' => 'INTERMEDIATE'];
        }

        foreach ($token['alternatives'] as $alternative) {
            $altTokens = $this->tokenize($alternative);
            $this->buildSequence($altTokens, 0, $fromNode, $toNode, $graph);
        }

        return $toNode;
    }

    /**
     * Build repetition (A+ or A*)
     * Creates loop-back structure
     */
    private function buildRepetition(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        // Create intermediate node if toNode not specified
        if ($toNode === null) {
            $toNode = $this->newNodeId();
            $graph['nodes'][$toNode] = ['type' => 'INTERMEDIATE'];
        }

        $loopNode = $this->newNodeId();
        $graph['nodes'][$loopNode] = ['type' => 'REP_CHECK'];

        // Build the repeated element
        $this->buildToken($token['content'], $fromNode, $loopNode, $graph);

        // Loop back
        $this->buildToken($token['content'], $loopNode, $loopNode, $graph);

        // Exit to toNode
        $graph['edges'][] = ['from' => $loopNode, 'to' => $toNode];

        // If A* (zero or more), add bypass
        if ($token['operator'] === '*') {
            $graph['edges'][] = [
                'from' => $fromNode,
                'to' => $toNode,
                'bypass' => true,
            ];
        }

        return $toNode;
    }

    /**
     * Extract content between matching brackets
     */
    private function extractBracketed(string $pattern, int $start, string $open, string $close): string
    {
        $depth = 0;
        $length = strlen($pattern);
        $content = '';

        for ($i = $start; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === $open) {
                $depth++;
                if ($depth > 1) {
                    $content .= $char;
                }
            } elseif ($char === $close) {
                $depth--;
                if ($depth === 0) {
                    return $content;
                } else {
                    $content .= $char;
                }
            } else {
                if ($depth > 0) {
                    $content .= $char;
                }
            }
        }

        throw new Exception("Unmatched bracket: $open at position $start");
    }

    /**
     * Extract a literal word
     */
    private function extractWord(string $pattern, int $start): string
    {
        $word = '';
        $length = strlen($pattern);

        for ($i = $start; $i < $length; $i++) {
            $char = $pattern[$i];

            // Stop at special characters or whitespace
            if (in_array($char, [' ', '[', ']', '{', '}', '(', ')', '+', '*', '|'])) {
                break;
            }

            $word .= $char;
        }

        return $word;
    }

    /**
     * Parse slot {...}
     */
    private function parseSlot(string $content): array
    {
        $content = trim($content);

        // Wildcard
        if ($content === '*') {
            return ['type' => 'WILDCARD'];
        }

        // Constrained slot: {POS:constraint}
        if (strpos($content, ':') !== false) {
            [$pos, $constraint] = explode(':', $content, 2);

            return [
                'type' => 'SLOT',
                'pos' => trim($pos),
                'constraint' => trim($constraint),
            ];
        }

        // Simple slot: {POS}
        return [
            'type' => 'SLOT',
            'pos' => $content,
            'constraint' => null,
        ];
    }

    /**
     * Parse parenthetical (alternative or group)
     */
    private function parseParenthetical(string $content): array
    {
        // Check if contains | (alternative)
        if (strpos($content, '|') !== false) {
            $alternatives = array_map('trim', explode('|', $content));

            return [
                'type' => 'ALTERNATIVE',
                'alternatives' => $alternatives,
            ];
        }

        // Otherwise it's a group (treat as sequence)
        return [
            'type' => 'GROUP',
            'content' => $content,
        ];
    }

    /**
     * Check bracket matching
     */
    private function checkBracketMatching(string $pattern, array &$errors): void
    {
        $brackets = [
            '[' => ']',
            '{' => '}',
            '(' => ')',
        ];

        $stack = [];
        $length = strlen($pattern);

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if (isset($brackets[$char])) {
                $stack[] = [$char, $i];
            } elseif (in_array($char, array_values($brackets))) {
                if (empty($stack)) {
                    $errors[] = "Unmatched closing bracket '$char' at position $i";
                } else {
                    [$openChar, $openPos] = array_pop($stack);
                    $expectedClose = $brackets[$openChar];
                    if ($char !== $expectedClose) {
                        $errors[] = "Mismatched brackets: '$openChar' at $openPos expects '$expectedClose' but found '$char' at $i";
                    }
                }
            }
        }

        if (! empty($stack)) {
            foreach ($stack as [$char, $pos]) {
                $errors[] = "Unmatched opening bracket '$char' at position $pos";
            }
        }
    }

    /**
     * Validate individual token
     */
    private function validateToken(array $token, array &$errors): void
    {
        // Add validation logic as needed
        // For now, basic checks are sufficient
    }

    /**
     * Format node label for DOT output
     */
    private function formatNodeLabel(array $node): string
    {
        return match ($node['type']) {
            'START' => 'START',
            'END' => 'END',
            'LITERAL' => $node['value'],
            'SLOT' => isset($node['constraint'])
                ? "{{$node['pos']}:{$node['constraint']}}"
                : "{{$node['pos']}}",
            'WILDCARD' => '{*}',
            'REP_CHECK' => 'CHECK',
            default => $node['type'],
        };
    }

    /**
     * Get node shape for DOT output
     */
    private function getNodeShape(array $node): string
    {
        return match ($node['type']) {
            'START', 'END' => 'circle',
            'REP_CHECK' => 'diamond',
            default => 'box',
        };
    }

    /**
     * Generate new unique node ID
     */
    private function newNodeId(): string
    {
        return 'n'.$this->nodeCounter++;
    }
}

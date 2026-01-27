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
 * - CE Slots: {CE:label} (match constructional element role)
 * - Combined POS+CE: {POS@CE}, {POS:constraint@CE} (match both POS and CE)
 * - Construction Refs: {CXN:name}, {CXN#id}, {CXN:name@CE} (reference other constructions)
 * - Wildcards: {*} (match any token)
 * - Optional: [element] (0 or 1 times)
 * - Mandatory: <element> (required, can be ghost in V5)
 * - Alternatives: (A | B | C) (one of)
 * - Repetition: A+ (1 or more), A* (0 or more)
 * - Grouping: (A B C) (sequence)
 *
 * Examples:
 * - Basic POS: [{NUM}] mil [, ] [{NUM}] [e {NUM}]
 * - CE-based: [{CE:Mod}]* <{CE:Head}>
 * - Combined: {ADP@Adp} [{DET|NUM|ADJ}@Mod]* {NOUN@Head}
 * - Construction ref: {CXN:NP} {VERB@Pred}
 */
class PatternCompiler
{
    private int $nodeCounter = 0;

    /**
     * Compile a BNF pattern string into a graph structure
     *
     * @param  string  $pattern  BNF pattern string
     * @param  string|null  $constructionType  Optional construction type (e.g., 'sequencer', 'phrasal', 'mwe')
     * @return array Graph with 'nodes', 'edges', and 'mandatoryElements' arrays
     *
     * @throws Exception If pattern is invalid
     */
    public function compile(string $pattern, ?string $constructionType = null): array
    {
        $this->nodeCounter = 0;

        // Tokenize pattern
        $tokens = $this->tokenize($pattern);

        // Build graph
        $graph = [
            'nodes' => [],
            'edges' => [],
            'mandatoryElements' => [],
        ];

        $startNode = $this->newNodeId();
        $endNode = $this->newNodeId();

        $graph['nodes'][$startNode] = ['type' => 'START'];
        $graph['nodes'][$endNode] = ['type' => 'END'];

        // Build from tokens
        $lastNode = $this->buildSequence($tokens, 0, $startNode, $endNode, $graph, $constructionType);

        // Ensure connection to end
        if ($lastNode !== $endNode) {
            $graph['edges'][] = ['from' => $lastNode, 'to' => $endNode];
        }

        // Extract mandatory elements
        $graph['mandatoryElements'] = $this->extractMandatoryElements($graph);

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

            // Mandatory <...> (V5 ghost nodes)
            if ($char === '<') {
                $content = $this->extractBracketed($pattern, $i, '<', '>');
                $tokens[] = [
                    'type' => 'MANDATORY',
                    'content' => trim($content),
                ];
                $i += strlen($content) + 2; // +2 for angle brackets

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
            // Escape quotes in label for DOT format
            $escapedLabel = str_replace('"', '\\"', $label);
            $dot .= "  $nodeId [label=\"$escapedLabel\", shape=$shape];\n";
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
     *
     * @param  string|null  $constructionType  Optional construction type for special handling
     */
    private function buildSequence(array $tokens, int $startIdx, string $fromNode, string $toNode, array &$graph, ?string $constructionType = null): string
    {
        if ($startIdx >= count($tokens)) {
            $graph['edges'][] = ['from' => $fromNode, 'to' => $toNode];

            return $toNode;
        }

        // Special handling for sequencer constructions with 3 elements
        // Insert INTERMEDIATE nodes between elements regardless of complexity
        if ($constructionType === 'sequencer' && $startIdx === 0 && count($tokens) === 3) {
            return $this->buildSequencerConstruction($tokens, $fromNode, $toNode, $graph);
        }

        $currentNode = $fromNode;
        $lastToNode = null;

        for ($i = $startIdx; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $isLast = ($i === count($tokens) - 1);
            $nextNode = $isLast ? $toNode : null;  // Pass toNode for last token

            $currentNode = $this->buildToken($token, $currentNode, $nextNode, $graph);
            $lastToNode = $nextNode; // Track if we passed toNode to buildToken
        }

        // Connect last node to end ONLY if toNode was NOT passed to buildToken
        // (If toNode was passed, buildToken already connected to it)
        if ($currentNode !== $toNode && $lastToNode === null) {
            $graph['edges'][] = ['from' => $currentNode, 'to' => $toNode];
        }

        return $currentNode;
    }

    /**
     * Build sequencer construction with INTERMEDIATE nodes between elements
     *
     * Sequencer constructions always have 3 elements (left, head, right).
     * This method creates INTERMEDIATE nodes between each element position.
     */
    private function buildSequencerConstruction(array $tokens, string $fromNode, string $toNode, array &$graph): string
    {
        // Create INTERMEDIATE nodes between elements
        $intermediate1 = $this->newNodeId();
        $intermediate2 = $this->newNodeId();

        $graph['nodes'][$intermediate1] = ['type' => 'INTERMEDIATE'];
        $graph['nodes'][$intermediate2] = ['type' => 'INTERMEDIATE'];

        // Build first element (left) -> INTERMEDIATE1
        $this->buildToken($tokens[0], $fromNode, $intermediate1, $graph);

        // Build second element (head) -> INTERMEDIATE2
        $this->buildToken($tokens[1], $intermediate1, $intermediate2, $graph);

        // Build third element (right) -> toNode
        $this->buildToken($tokens[2], $intermediate2, $toNode, $graph);

        return $toNode;
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

            case 'CE_SLOT':
                return $this->buildCESlot($token, $fromNode, $toNode, $graph);

            case 'COMBINED_SLOT':
                return $this->buildCombinedSlot($token, $fromNode, $toNode, $graph);

            case 'CONSTRUCTION_REF':
                return $this->buildConstructionRef($token, $fromNode, $toNode, $graph);

            case 'WILDCARD':
                return $this->buildWildcard($fromNode, $toNode, $graph);

            case 'OPTIONAL':
                return $this->buildOptional($token, $fromNode, $toNode, $graph);

            case 'MANDATORY':
                return $this->buildMandatory($token, $fromNode, $toNode, $graph);

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
     * Build CE slot node
     */
    private function buildCESlot(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = [
            'type' => 'CE_SLOT',
            'ce_label' => $token['ce_label'],
            'ce_tier' => $token['ce_tier'],
        ];

        $graph['edges'][] = ['from' => $fromNode, 'to' => $nodeId];

        // If toNode specified, connect to it
        if ($toNode !== null) {
            $graph['edges'][] = ['from' => $nodeId, 'to' => $toNode];
        }

        return $nodeId;
    }

    /**
     * Build combined POS+CE slot node
     */
    private function buildCombinedSlot(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = [
            'type' => 'COMBINED_SLOT',
            'pos' => $token['pos'],
            'constraint' => $token['constraint'] ?? null,
            'ce_label' => $token['ce_label'],
            'ce_tier' => $token['ce_tier'],
        ];

        $graph['edges'][] = ['from' => $fromNode, 'to' => $nodeId];

        // If toNode specified, connect to it
        if ($toNode !== null) {
            $graph['edges'][] = ['from' => $nodeId, 'to' => $toNode];
        }

        return $nodeId;
    }

    /**
     * Build construction reference node
     */
    private function buildConstructionRef(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        $nodeId = $this->newNodeId();
        $graph['nodes'][$nodeId] = [
            'type' => 'CONSTRUCTION_REF',
            'construction_name' => $token['construction_name'] ?? null,
            'construction_id' => $token['construction_id'] ?? null,
            'ce_label' => $token['ce_label'] ?? null,
            'ce_tier' => $token['ce_tier'] ?? null,
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

        // Build the optional path (no construction type for nested sequences)
        $this->buildSequence($contentTokens, 0, $fromNode, $toNode, $graph, null);

        // Add bypass edge (optional means can skip)
        $graph['edges'][] = [
            'from' => $fromNode,
            'to' => $toNode,
            'bypass' => true,
        ];

        return $toNode;
    }

    /**
     * Build mandatory element <...>
     * Required element that can be fulfilled by ghost node in V5
     */
    private function buildMandatory(array $token, string $fromNode, ?string $toNode, array &$graph): string
    {
        // Create intermediate node if toNode not specified
        if ($toNode === null) {
            $toNode = $this->newNodeId();
            $graph['nodes'][$toNode] = ['type' => 'INTERMEDIATE'];
        }

        // Parse content
        $contentTokens = $this->tokenize($token['content']);

        // Build the mandatory path (no construction type for nested sequences)
        $this->buildSequence($contentTokens, 0, $fromNode, $toNode, $graph, null);

        // Mark nodes in this path as mandatory
        $this->markNodesAsMandatory($graph, $fromNode, $toNode);

        return $toNode;
    }

    /**
     * Mark nodes between fromNode and toNode as mandatory (can create ghosts)
     */
    private function markNodesAsMandatory(array &$graph, string $fromNode, string $toNode): void
    {
        // Find all nodes in the path from fromNode to toNode
        $visited = [];
        $this->markPathNodes($graph, $fromNode, $toNode, $visited);
    }

    /**
     * Recursively mark nodes in path as mandatory
     */
    private function markPathNodes(array &$graph, string $currentNode, string $targetNode, array &$visited): bool
    {
        if ($currentNode === $targetNode) {
            return true;
        }

        if (in_array($currentNode, $visited)) {
            return false;
        }

        $visited[] = $currentNode;

        // Find edges from current node
        foreach ($graph['edges'] as $edge) {
            if ($edge['from'] === $currentNode) {
                if ($this->markPathNodes($graph, $edge['to'], $targetNode, $visited)) {
                    // Mark the target node as mandatory
                    if (isset($graph['nodes'][$edge['to']])) {
                        $graph['nodes'][$edge['to']]['mandatory'] = true;
                        $graph['nodes'][$edge['to']]['canBeGhost'] = true;
                    }

                    return true;
                }
            }
        }

        return false;
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
            // No construction type for nested sequences (alternatives)
            $this->buildSequence($altTokens, 0, $fromNode, $toNode, $graph, null);
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
            if (in_array($char, [' ', '[', ']', '<', '>', '{', '}', '(', ')', '+', '*', '|'])) {
                break;
            }

            $word .= $char;
        }

        return $word;
    }

    /**
     * Parse slot {...}
     *
     * Supports:
     * - Wildcards: {*}
     * - POS slots: {NOUN}, {VERB:inf}
     * - CE slots: {CE:Head}, {CE:Pred}
     * - Combined POS+CE: {NOUN@Head}, {VERB:inf@Pred}
     * - Construction references: {CXN:name}, {CXN#id}, {CXN:name@CE}
     */
    private function parseSlot(string $content): array
    {
        $content = trim($content);

        // Wildcard
        if ($content === '*') {
            return ['type' => 'WILDCARD'];
        }

        // Construction reference: {CXN:name} or {CXN#id}
        if (str_starts_with($content, 'CXN:') || str_starts_with($content, 'CXN#')) {
            return $this->parseConstructionRef($content);
        }

        // CE slot: {CE:label} or combined {CE:label@...}
        if (str_starts_with($content, 'CE:')) {
            return $this->parseCESlot($content);
        }

        // Check for @ separator (combined POS+CE)
        if (str_contains($content, '@')) {
            return $this->parseCombinedSlot($content);
        }

        // Constrained slot: {POS:constraint}
        if (str_contains($content, ':')) {
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
     * Parse CE slot: {CE:label}
     */
    private function parseCESlot(string $content): array
    {
        // Remove 'CE:' prefix
        $label = substr($content, 3);

        // Detect tier
        $tier = $this->detectCETier($label);

        return [
            'type' => 'CE_SLOT',
            'ce_label' => $label,
            'ce_tier' => $tier,
        ];
    }

    /**
     * Parse combined POS+CE slot: {POS@CE} or {POS:constraint@CE}
     */
    private function parseCombinedSlot(string $content): array
    {
        [$posPartConst, $ceLabel] = explode('@', $content, 2);
        $posPartConst = trim($posPartConst);
        $ceLabel = trim($ceLabel);

        $pos = null;
        $constraint = null;

        // Check if POS part has constraint
        if (str_contains($posPartConst, ':')) {
            [$pos, $constraint] = explode(':', $posPartConst, 2);
            $pos = trim($pos);
            $constraint = trim($constraint);
        } else {
            $pos = $posPartConst;
        }

        // Detect CE tier
        $tier = $this->detectCETier($ceLabel);

        return [
            'type' => 'COMBINED_SLOT',
            'pos' => $pos,
            'constraint' => $constraint,
            'ce_label' => $ceLabel,
            'ce_tier' => $tier,
        ];
    }

    /**
     * Parse construction reference: {CXN:name} or {CXN#id}
     * Also supports: {CXN:name@CE} or {CXN#id@CE}
     */
    private function parseConstructionRef(string $content): array
    {
        $ceLabel = null;
        $ceTier = null;

        // Check for combined construction+CE syntax
        if (str_contains($content, '@')) {
            [$constructionPart, $ceLabel] = explode('@', $content, 2);
            $ceLabel = trim($ceLabel);
            $ceTier = $this->detectCETier($ceLabel);
            $content = $constructionPart;
        }

        // Parse construction reference
        if (str_starts_with($content, 'CXN:')) {
            // By name
            $name = substr($content, 4); // Remove 'CXN:' prefix

            return [
                'type' => 'CONSTRUCTION_REF',
                'construction_name' => $name,
                'construction_id' => null,
                'ce_label' => $ceLabel,
                'ce_tier' => $ceTier,
            ];
        } elseif (str_starts_with($content, 'CXN#')) {
            // By ID
            $id = substr($content, 4); // Remove 'CXN#' prefix

            return [
                'type' => 'CONSTRUCTION_REF',
                'construction_name' => null,
                'construction_id' => (int) $id,
                'ce_label' => $ceLabel,
                'ce_tier' => $ceTier,
            ];
        }

        throw new Exception("Invalid construction reference: $content");
    }

    /**
     * Detect CE tier from label
     *
     * Returns 'phrasal', 'clausal', or 'sentential'
     */
    private function detectCETier(string $ceLabel): string
    {
        // Phrasal CEs (word-level)
        $phrasalCEs = ['Head', 'Mod', 'Adm', 'Adp', 'Lnk', 'Clf', 'Idx', 'Conj', 'Punct'];

        // Clausal CEs (phrase-level)
        $clausalCEs = ['Pred', 'Arg', 'CPP', 'Gen', 'FPM', 'Conj'];

        // Sentential CEs (clause-level)
        $sententialCEs = ['Main', 'Adv', 'Rel', 'Comp', 'Dtch', 'Int'];

        if (in_array($ceLabel, $phrasalCEs)) {
            return 'phrasal';
        }

        if (in_array($ceLabel, $clausalCEs)) {
            return 'clausal';
        }

        if (in_array($ceLabel, $sententialCEs)) {
            return 'sentential';
        }

        throw new Exception("Unknown CE label: $ceLabel");
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
            '<' => '>',
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
     * Extract mandatory elements from compiled graph
     *
     * @return array List of mandatory element descriptors
     */
    private function extractMandatoryElements(array $graph): array
    {
        $mandatoryElements = [];

        foreach ($graph['nodes'] as $nodeId => $node) {
            if (isset($node['mandatory']) && $node['mandatory']) {
                $mandatoryElements[] = [
                    'nodeId' => $nodeId,
                    'type' => $node['type'],
                    'canBeGhost' => $node['canBeGhost'] ?? false,
                    'label' => $this->getMandatoryElementLabel($node),
                ];
            }
        }

        return $mandatoryElements;
    }

    /**
     * Get label for mandatory element
     */
    private function getMandatoryElementLabel(array $node): string
    {
        return match ($node['type']) {
            'LITERAL' => $node['value'],
            'SLOT' => isset($node['constraint'])
                ? "{$node['pos']}:{$node['constraint']}"
                : $node['pos'],
            'CE_SLOT' => "CE:{$node['ce_label']}",
            'COMBINED_SLOT' => isset($node['constraint'])
                ? "{$node['pos']}:{$node['constraint']}@{$node['ce_label']}"
                : "{$node['pos']}@{$node['ce_label']}",
            'CONSTRUCTION_REF' => $node['construction_name']
                ? "CXN:{$node['construction_name']}"
                : "CXN#{$node['construction_id']}",
            'WILDCARD' => '*',
            default => $node['type'],
        };
    }

    /**
     * Format node label for DOT output
     */
    private function formatNodeLabel(array $node): string
    {
        $label = match ($node['type']) {
            'START' => 'START',
            'END' => 'END',
            'LITERAL' => $node['value'],
            'SLOT' => isset($node['constraint'])
                ? "{{$node['pos']}:{$node['constraint']}}"
                : "{{$node['pos']}}",
            'CE_SLOT' => "{{CE:{$node['ce_label']}}}",
            'COMBINED_SLOT' => isset($node['constraint'])
                ? "{{$node['pos']}:{$node['constraint']}@{$node['ce_label']}}"
                : "{{$node['pos']}@{$node['ce_label']}}",
            'CONSTRUCTION_REF' => $this->formatConstructionRefLabel($node),
            'WILDCARD' => '{*}',
            'REP_CHECK' => 'CHECK',
            default => $node['type'],
        };

        // Add asterisk for mandatory nodes
        if (isset($node['mandatory']) && $node['mandatory']) {
            $label .= '*';
        }

        return $label;
    }

    /**
     * Format construction reference label
     */
    private function formatConstructionRefLabel(array $node): string
    {
        $base = $node['construction_name']
            ? "CXN:{$node['construction_name']}"
            : "CXN#{$node['construction_id']}";

        if (isset($node['ce_label']) && $node['ce_label'] !== null) {
            $base .= "@{$node['ce_label']}";
        }

        return "{{$base}}";
    }

    /**
     * Get node shape for DOT output
     */
    private function getNodeShape(array $node): string
    {
        // Mandatory nodes use double box
        if (isset($node['mandatory']) && $node['mandatory']) {
            return 'doublebox';
        }

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

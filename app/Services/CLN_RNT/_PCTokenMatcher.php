<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Layer;
use App\Models\CLN_RNT\Node;

/**
 * PC Token Matcher
 *
 * Matches word/POS tokens against pattern graph nodes by creating
 * virtual CLN Node objects compatible with the existing PatternMatcher service.
 *
 * This service acts as an adapter between PC framework's simple token
 * representation (word/POS pairs) and the CLN framework's Node-based
 * pattern matching system.
 */
class PCTokenMatcher
{
    /**
     * Pattern matcher service
     */
    private PatternMatcher $patternMatcher;

    /**
     * Create new PC token matcher
     */
    public function __construct(PatternMatcher $patternMatcher)
    {
        $this->patternMatcher = $patternMatcher;
    }

    /**
     * Check if a token (word/POS) matches a pattern graph node
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     * @param  array  $patternNode  Pattern graph node specification
     * @return bool True if token matches the pattern node
     */
    public function matchesToken(string $word, string $pos, array $patternNode): bool
    {
        // Create appropriate virtual node based on pattern type
        $patternType = $patternNode['type'] ?? null;

        if ($patternType === 'LITERAL') {
            // For LITERAL matching, create a word-type virtual node
            $virtualNode = $this->createVirtualWordNode($word, $pos);
        } else {
            // For SLOT and other types, create a POS-type virtual node
            $virtualNode = $this->createVirtualTokenNode($word, $pos);
        }

        // Use existing PatternMatcher to check if it matches
        return $this->patternMatcher->matchesNode($virtualNode, $patternNode);
    }

    /**
     * Check if a construction name matches a pattern graph node
     *
     * @param  string  $constructionName  Construction name
     * @param  array  $patternNode  Pattern graph node specification
     * @return bool True if construction matches the pattern node
     */
    public function matchesConstruction(string $constructionName, array $patternNode): bool
    {
        // Create virtual node for this construction
        $virtualNode = $this->createVirtualConstructionNode($constructionName);

        // Use existing PatternMatcher to check if it matches
        return $this->patternMatcher->matchesNode($virtualNode, $patternNode);
    }

    /**
     * Create a virtual CLN Node for a word/POS token (public for parser use)
     *
     * Creates a minimal Node structure that PatternMatcher can process.
     * The node has metadata fields that PatternMatcher expects:
     * - word: word text (for LITERAL matching)
     * - pos: POS tag (for SLOT matching)
     * - lemma: word in lowercase (for LITERAL matching with inflections)
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     */
    public function createVirtualNode(string $word, string $pos): Node
    {
        $node = new Node(Layer::L23, "virtual_token_{$pos}");

        // Set metadata that PatternMatcher expects
        // Note: PatternMatcher checks for 'node_type' (underscore) and 'value'
        $node->metadata = [
            'node_type' => 'pos',  // For SLOT matching
            'value' => $pos,        // The POS tag value
            'word' => $word,
            'pos' => $pos,
            'lemma' => strtolower($word), // For LITERAL matching
        ];

        return $node;
    }

    /**
     * Create a virtual CLN Node for LITERAL pattern matching
     *
     * Creates a word-type node that can match against LITERAL pattern nodes.
     * PatternMatcher expects:
     * - node_type: 'word' or 'lemma'
     * - value: the actual word text
     *
     * @param  string  $word  Word text
     * @param  string  $pos  Part-of-speech tag
     */
    private function createVirtualWordNode(string $word, string $pos): Node
    {
        $node = new Node(Layer::L23, "virtual_word_{$word}");

        // Set metadata for LITERAL matching
        $node->metadata = [
            'node_type' => 'word',  // For LITERAL matching
            'value' => $word,       // The actual word text
            'word' => $word,
            'pos' => $pos,          // Keep POS for reference
            'lemma' => strtolower($word),
        ];

        return $node;
    }

    /**
     * Create a virtual CLN Node for a word/POS token (kept private for BC)
     */
    private function createVirtualTokenNode(string $word, string $pos): Node
    {
        return $this->createVirtualNode($word, $pos);
    }

    /**
     * Create a virtual CLN Node for a construction
     *
     * @param  string  $constructionName  Construction name
     */
    private function createVirtualConstructionNode(string $constructionName): Node
    {
        $node = new Node(Layer::L5, "virtual_construction_{$constructionName}");

        // Set metadata for construction matching
        // Note: PatternMatcher expects 'node_type' (underscore) and 'name' for the construction name
        $node->metadata = [
            'node_type' => 'construction',
            'name' => $constructionName,  // PatternMatcher checks this field!
            'construction_name' => $constructionName,
            'is_confirmed' => true,
            'is_from_l5_feedback' => true,
        ];

        return $node;
    }
}

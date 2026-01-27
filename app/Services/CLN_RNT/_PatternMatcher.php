<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\Node;

/**
 * Pattern Matcher
 *
 * Stateless service that checks if L23 nodes match pattern graph requirements.
 *
 * This class implements the core pattern matching logic for the CLN parser.
 * It determines whether activated L23 nodes (words, POS tags, features) match
 * the requirements of compiled pattern graph nodes.
 *
 * Supported node types:
 * - LITERAL: Match specific words
 * - SLOT: Match POS tags with optional constraints
 * - CE_SLOT: Match constructional element (CE) labels
 * - COMBINED_SLOT: Match both POS and CE labels
 * - CONSTRUCTION_REF: Match entire construction patterns
 * - WILDCARD: Match any token
 */
class PatternMatcher
{
    /**
     * Check if L23 nodes match a pattern graph node
     *
     * This is the main entry point for pattern matching.
     * Delegates to specific matchers based on graph node type.
     *
     * @param  Node $node JNode/BNode object
     * @param  array  $graphNode  Pattern graph node with 'type' and type-specific data
     * @return bool True if any L23 node matches the graph node requirements
     */
    public function matchesNode(
        //array $l23Nodes,
        Node $node,
        array $graphNode
    ): bool
    {
        $type = $graphNode['type'] ?? null;

        return match ($type) {
            'LITERAL' => $this->matchLiteral($node, $graphNode['value'] ?? ''),
            'SLOT' => $this->matchSlot($node, $graphNode['pos'] ?? '', $graphNode['constraint'] ?? null),
            'CE_SLOT' => $this->matchCESlot($node, $graphNode['ce_label'] ?? '', $graphNode['ce_tier'] ?? ''),
            'COMBINED_SLOT' => $this->matchCombinedSlot(
                $node,
                $graphNode['pos'] ?? '',
                $graphNode['ce_label'] ?? '',
                $graphNode['ce_tier'] ?? '',
                $graphNode['constraint'] ?? null
            ),
            'WILDCARD' => $this->matchWildcard($node),
            'CONSTRUCTION' => $this->matchConstruction($node, $graphNode['construction_id'] ?? null, $graphNode['construction_name'] ?? null),
            'CONSTRUCTION_REF' => $this->matchConstruction($node, $graphNode['construction_id'] ?? null, $graphNode['construction_name'] ?? null),
            // OPTIONAL, ALTERNATIVE, REPETITION handled in graph traversal
            default => false,
        };
    }

    /**
     * Match LITERAL pattern node against L23 word and lemma nodes
     *
     * LITERAL nodes match against both surface forms and lemmas (case-insensitive).
     * This allows patterns to match inflected forms.
     *
     * Examples:
     * - Pattern "café" matches word "café" (exact match)
     * - Pattern "café" matches word "cafés" via lemma "café" (inflection match)
     * - Pattern "comer" matches word "comemos" via lemma "comer" (verb conjugation)
     * @param Node $node node to check
     * @param  string  $expectedValue  Expected word value from pattern (may have quotes)
     * @return bool True if any word or lemma node matches
     */
    private function matchLiteral(Node $node, string $expectedValue): bool
    {
        if (empty($expectedValue)) {
            return false;
        }

        // Strip surrounding quotes if present (pattern compiler preserves them)
        $expectedValue = trim($expectedValue, '"\'');

        if (empty($expectedValue)) {
            return false;
        }

        // Check each L23 node
//        foreach ($l23Nodes as $node) {
            $nodeType = $node->metadata['node_type'] ?? null;

            // Check both word and lemma nodes
            if (! in_array($nodeType, ['word', 'lemma'])) {
                return false;
            }

            $actualValue = $node->metadata['value'] ?? '';

            // Case-insensitive comparison (Unicode-safe)
            if (mb_strtolower($actualValue, 'UTF-8') === mb_strtolower($expectedValue, 'UTF-8')) {
                return true;
            }
//        }

        return false;
    }

    /**
     * Match SLOT pattern node against L23 POS nodes
     *
     * SLOT nodes match based on POS (part-of-speech) tags.
     * Optionally can include constraints on morphological features.
     *
     * Examples:
     * - Pattern {NOUN} matches any noun (cafés, manhã, etc.)
     * - Pattern {VERB} matches any verb (comemos, come, etc.)
     * - Pattern {NOUN:Gender=Masc} matches only masculine nouns
     *
     * @param Node $node node to check
     * @param  string  $expectedPos  Expected POS tag (NOUN, VERB, ADP, etc.)
     * @param  string|null  $constraint  Optional constraint (e.g., "Gender=Masc")
     * @return bool True if any POS node matches
     */
    private function matchSlot(Node $node, string $expectedPos, ?string $constraint = null): bool
    {
        if (empty($expectedPos)) {
            return false;
        }

        // Check each L23 node
//        foreach ($l23Nodes as $node) {
            $nodeType = $node->metadata['node_type'] ?? null;

            // Check both traditional POS nodes AND single-element POS construction nodes
            if ($nodeType === 'pos') {
                // Traditional POS node
                $actualPos = $node->metadata['value'] ?? '';
            } elseif ($nodeType === 'construction' && ($node->metadata['is_single_element_pos'] ?? false)) {
                // Single-element POS construction node
                $actualPos = $node->metadata['pos_value'] ?? '';
            } else {
                // Not a POS-related node
                return false;
            }

            // Case-insensitive POS comparison
            if (mb_strtoupper($actualPos, 'UTF-8') !== mb_strtoupper($expectedPos, 'UTF-8')) {
                return false;
            }

            // If no constraint, POS match is sufficient
            if ($constraint === null) {
                return true;
            }

            // Check constraint against feature nodes
            if ($this->matchConstraint($node, $constraint)) {
                return true;
            }
//        }

        return false;
    }

    /**
     * Match WILDCARD pattern node against any L23 node
     *
     * WILDCARD ({*}) matches any token, regardless of word, POS, or features.
     * Always returns true if there are any activated nodes.
     *
     * @param  array  $l23Nodes  L23 nodes to check
     * @return bool True if any node exists
     */
    private function matchWildcard(Node $node): bool
    {
        // Wildcard matches if there are any nodes at all
        // We check for word, POS nodes, or single-element POS constructions (the primary indicators of a token)
//        foreach ($l23Nodes as $node) {
            $nodeType = $node->metadata['node_type'] ?? null;
            if (in_array($nodeType, ['word', 'pos'])) {
                return true;
            }
            // Also match single-element POS construction nodes
            if ($nodeType === 'construction' && ($node->metadata['is_single_element_pos'] ?? false)) {
                return true;
            }
//        }

        return false;
    }

    /**
     * Check if constraint matches feature nodes
     *
     * Constraints are in format "Feature=Value" (e.g., "Gender=Masc", "Number=Plur")
     *
     * @param Node $node node to check
     * @param  string  $constraint  Constraint string (e.g., "Gender=Masc")
     * @return bool True if constraint is satisfied
     */
    private function matchConstraint(Node $node, string $constraint): bool
    {
        if (empty($constraint)) {
            return true;
        }

        // Parse constraint: "Feature=Value"
        if (! str_contains($constraint, '=')) {
            return false;
        }

        [$featureName, $featureValue] = explode('=', $constraint, 2);
        $featureName = trim($featureName);
        $featureValue = trim($featureValue);

        // Check each L23 node for matching feature
//        foreach ($l23Nodes as $node) {
            // Only check feature nodes
            if (($node->metadata['node_type'] ?? null) !== 'feature') {
                return false;
            }

            $nodeFeat = $node->metadata['feature'] ?? '';
            $nodeValue = $node->metadata['value'] ?? '';

            // Case-insensitive comparison
            if (mb_strtolower($nodeFeat, 'UTF-8') === mb_strtolower($featureName, 'UTF-8') &&
                mb_strtolower($nodeValue, 'UTF-8') === mb_strtolower($featureValue, 'UTF-8')) {
                return true;
            }
//        }

        return false;
    }

    /**
     * Extract predicted value from graph node for prediction generation
     *
     * Converts graph node information into the value that should be predicted.
     * For LITERAL nodes: returns the literal word (without quotes)
     * Future: For SLOT nodes: returns the POS tag, etc.
     *
     * @param  array  $graphNode  Pattern graph node
     * @return string Predicted value
     */
    public function extractPredictedValue(array $graphNode): string
    {
        $type = $graphNode['type'] ?? '';

        $value = match ($type) {
            'LITERAL' => $graphNode['value'] ?? '',
            'SLOT' => $graphNode['pos'] ?? '',
            'CONSTRAINT' => $graphNode['constraint'] ?? '',
            'CONSTRUCTION_REF' => $graphNode['construction_name']
                ?? ($graphNode['construction_id'] ? "CXN#{$graphNode['construction_id']}" : ''),
            default => '',
        };

        // Strip quotes from LITERAL values
        if ($type === 'LITERAL') {
            $value = trim($value, '"\'');
        }

        return $value;
    }

    /**
     * Match CE_SLOT pattern node against L23 CE annotation nodes
     *
     * CE_SLOT nodes match based on constructional element (CE) labels at a specific tier.
     * The tier (phrasal, clausal, or sentential) determines which CE annotation to check.
     *
     * Examples:
     * - Pattern {CE:Head} (phrasal) matches any word with phrasal CE = Head
     * - Pattern {CE:Pred} (clausal) matches any phrase with clausal CE = Pred
     * - Pattern {CE:Main} (sentential) matches any clause with sentential CE = Main
     *
     * @param Node $node node to check
     * @param  string  $expectedCE  Expected CE label (e.g., "Head", "Pred", "Main")
     * @param  string  $tier  CE tier: 'phrasal', 'clausal', or 'sentential'
     * @return bool True if any CE node matches
     */
    private function matchCESlot(Node $node, string $expectedCE, string $tier): bool
    {
        if (empty($expectedCE) || empty($tier)) {
            return false;
        }

        // Check each L23 node for CE annotations
//        foreach ($l23Nodes as $node) {
            // Look for CE annotation nodes at the specified tier
            $nodeType = $node->metadata['node_type'] ?? null;

            // CE annotations stored as separate nodes with type 'ce_{tier}'
            // e.g., 'ce_phrasal', 'ce_clausal', 'ce_sentential'
            $expectedNodeType = "ce_{$tier}";

            if ($nodeType !== $expectedNodeType) {
                return false;
            }

            $actualCE = $node->metadata['value'] ?? '';

            // Case-sensitive CE comparison (CE labels are capitalized)
            if ($actualCE === $expectedCE) {
                return true;
            }
//        }

        return false;
    }

    /**
     * Match COMBINED_SLOT pattern node against L23 POS and CE nodes
     *
     * COMBINED_SLOT nodes require BOTH POS and CE to match.
     * Both constraints must be satisfied for the match to succeed.
     *
     * Examples:
     * - Pattern {NOUN@Head} requires POS=NOUN AND phrasal CE=Head
     * - Pattern {VERB:inf@Pred} requires POS=VERB, VerbForm=inf, AND clausal CE=Pred
     *
     * @param  array  $l23Nodes  L23 nodes to check
     * @param  string  $expectedPos  Expected POS tag (e.g., "NOUN", "VERB")
     * @param  string  $expectedCE  Expected CE label (e.g., "Head", "Pred")
     * @param  string  $tier  CE tier: 'phrasal', 'clausal', or 'sentential'
     * @param  string|null  $constraint  Optional POS constraint (e.g., "inf", "Gender=Masc")
     * @return bool True if both POS and CE match
     */
    private function matchCombinedSlot(
        Node $node,
        string $expectedPos,
        string $expectedCE,
        string $tier,
        ?string $constraint = null
    ): bool {
        if (empty($expectedPos) || empty($expectedCE) || empty($tier)) {
            return false;
        }

        // BOTH POS and CE must match
        $posMatches = $this->matchSlot($node, $expectedPos, $constraint);
        $ceMatches = $this->matchCESlot($node, $expectedCE, $tier);

        return $posMatches && $ceMatches;
    }

    /**
     * Match CONSTRUCTION pattern node against L23 construction nodes
     *
     * CONSTRUCTION nodes match against completed constructions that have been
     * fed back to L23 from L5. This enables hierarchical composition:
     * - Pattern: {CONSTRUCTION:mwe_por_favor} matches a specific completed MWE
     * - Pattern: {CONSTRUCTION:*} matches any completed construction
     *
     * Examples:
     * - {CONSTRUCTION:123} - matches construction with database ID 123
     * - {CONSTRUCTION:mwe_name} - matches construction with specific name
     * - {CONSTRUCTION:*} - matches any construction (both params null)
     *
     * @param Node $node node to check
     * @param  int|null  $constructionId  Expected construction ID (null = any)
     * @param  string|null  $constructionName  Expected construction name (null = any)
     * @return bool True if any construction node matches
     */
    private function matchConstruction(Node $node, ?int $constructionId = null, ?string $constructionName = null): bool
    {
//        foreach ($l23Nodes as $node) {
            // Only check construction nodes from L5 feedback
            if (($node->metadata['node_type'] ?? null) !== 'construction') {
                return false;
            }

            // Must be from L5 feedback (not other sources)
//            if (! ($node->metadata['is_from_l5_feedback'] ?? false)) {
//                continue;
//            }

            // If specific construction ID required, check it
            if ($constructionId !== null) {
                $nodeConstructionId = $node->metadata['construction_id'] ?? null;
                if ($nodeConstructionId !== $constructionId) {
                    return false;
                }
            }

            // If specific construction name required, check it
            if ($constructionName !== null) {
                $nodeName = $node->metadata['name'] ?? '';
                if (mb_strtolower($nodeName, 'UTF-8') !== mb_strtolower($constructionName, 'UTF-8')) {
                    return false;
                }
            }

            // Match found
            return true;
//        }

//        return false;
    }

    /**
     * Get prediction type from graph node
     *
     * Determines what type of prediction should be generated.
     * Returns: 'word', 'pos', 'feature', 'ce'
     *
     * @param  array  $graphNode  Pattern graph node
     * @return string Prediction type
     */
    public function getPredictionType(array $graphNode): string
    {
        $type = $graphNode['type'] ?? '';

        return match ($type) {
            'LITERAL' => 'word',         // LITERAL predicts specific word
            'SLOT' => 'pos',             // SLOT predicts POS tag
            'CE_SLOT' => 'ce',           // CE_SLOT predicts CE label
            'COMBINED_SLOT' => 'pos',    // COMBINED_SLOT predicts POS (CE is secondary)
            'CONSTRAINT' => 'feature',   // CONSTRAINT predicts feature
            'CONSTRUCTION_REF' => 'construction',  // CONSTRUCTION_REF predicts construction
            default => 'word',
        };
    }
}

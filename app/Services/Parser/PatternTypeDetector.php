<?php

namespace App\Services\Parser;

use App\Enums\Parser\PatternType;
use Exception;

/**
 * Pattern Type Auto-Detection Service
 *
 * Automatically detects whether a pattern is a Simple MWE, Variable MWE,
 * or BNF Construction based on syntax analysis.
 */
class PatternTypeDetector
{
    /**
     * Detect pattern type from pattern string
     *
     * @throws Exception If pattern syntax is invalid or ambiguous
     */
    public function detectType(string $pattern): PatternType
    {
        $pattern = trim($pattern);

        if (empty($pattern)) {
            throw new Exception('Pattern cannot be empty');
        }

        // 1. Try parsing as JSON (MWE component format)
        $json = json_decode($pattern, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $this->detectMWEFormat($json);
        }

        // 2. Check for BNF syntax operators
        if ($this->hasBNFSyntax($pattern)) {
            return PatternType::CONSTRUCTION;
        }

        // 3. Check if it's a simple word list
        if ($this->isSimpleWordList($pattern)) {
            return PatternType::SIMPLE_MWE;
        }

        // 4. Ambiguous or invalid pattern
        throw new Exception('Cannot detect pattern type. Please use valid Simple MWE (words), Variable MWE (JSON), or Construction (BNF) syntax.');
    }

    /**
     * Detect pattern type from existing MWE object
     */
    public function detectFromMWE(object $mwe): PatternType
    {
        if ($mwe->componentFormat === 'simple') {
            return PatternType::SIMPLE_MWE;
        }

        return PatternType::VARIABLE_MWE;
    }

    /**
     * Check if pattern contains BNF syntax operators
     */
    private function hasBNFSyntax(string $pattern): bool
    {
        // BNF operators to detect
        $bnfPatterns = [
            '/\{[A-Z_]+\}/',           // {POS} slots like {NUM}, {NOUN}
            '/\{[A-Z_]+:[a-z]+\}/',    // {POS:constraint} like {VERB:inf}
            '/\{\*\}/',                 // {*} wildcard
            '/\[/',                     // [ optional start
            '/\]/',                     // ] optional end
            '/\|/',                     // | alternatives
            '/[a-zA-Z]\+/',             // + repetition (one or more)
            '/[a-zA-Z]\*/',             // * repetition (zero or more)
            '/\([^)]+\|[^)]+\)/',      // (A | B) alternatives in parens
        ];

        foreach ($bnfPatterns as $regex) {
            if (preg_match($regex, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if pattern is a simple word list (no special syntax)
     */
    private function isSimpleWordList(string $pattern): bool
    {
        // Allow: letters (any language), spaces, hyphens, apostrophes
        // This matches patterns like: "a fim de", "por outro lado", "by-product"
        return preg_match('/^[\p{L}\s\'\-]+$/u', $pattern) === 1;
    }

    /**
     * Detect MWE format from JSON component array
     */
    private function detectMWEFormat(array $components): PatternType
    {
        if (empty($components)) {
            throw new Exception('MWE components array cannot be empty');
        }

        // Check first element structure
        $firstElement = $components[0];

        // Extended format: [{type: "W", value: "word"}, ...]
        if (is_array($firstElement) && isset($firstElement['type'])) {
            return PatternType::VARIABLE_MWE;
        }

        // Simple format: ["word1", "word2", ...]
        if (is_string($firstElement)) {
            // Verify all elements are strings
            foreach ($components as $component) {
                if (!is_string($component)) {
                    throw new Exception('Invalid MWE format: all components must be strings for Simple MWE');
                }
            }

            return PatternType::SIMPLE_MWE;
        }

        throw new Exception('Invalid MWE component format');
    }

    /**
     * Validate and normalize pattern for storage
     *
     * @return array [type, normalizedPattern]
     */
    public function validateAndNormalize(string $pattern): array
    {
        $type = $this->detectType($pattern);

        // For JSON MWE patterns, validate and re-encode
        $json = json_decode($pattern, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $normalized = json_encode($json, JSON_UNESCAPED_UNICODE);
        } else {
            // For Simple MWE and Construction, just trim
            $normalized = trim($pattern);
        }

        return [$type, $normalized];
    }
}

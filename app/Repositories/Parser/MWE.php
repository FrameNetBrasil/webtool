<?php

namespace App\Repositories\Parser;

use App\Database\Criteria;
use App\Enums\Parser\MWEComponentType;
use App\Models\Parser\PhrasalCENode;

class MWE
{
    /**
     * Retrieve MWE by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_mwe', 'idMWE', $id);
    }

    /**
     * List all MWEs for a grammar graph
     */
    public static function listByGrammar(int $idGrammarGraph): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('phrase')
            ->all();
    }

    /**
     * Get MWEs that start with a specific word
     */
    public static function getStartingWith(int $idGrammarGraph, string $firstWord): array
    {
        $firstWord = strtolower($firstWord);

        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('firstWord', '=', $firstWord)
            ->all();
    }

    /**
     * Get MWEs containing a specific component at any position
     */
    public static function getContaining(int $idGrammarGraph, string $word): array
    {
        $word = strtolower($word);

        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->whereRaw('JSON_CONTAINS(components, JSON_QUOTE(?))', [$word])
            ->all();
    }

    /**
     * Get MWE by exact phrase
     */
    public static function getByPhrase(int $idGrammarGraph, string $phrase): ?object
    {
        $result = Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('phrase', '=', $phrase)
            ->first();

        return $result ?: null;
    }

    /**
     * Get MWEs by length
     */
    public static function listByLength(int $idGrammarGraph, int $length): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('length', '=', $length)
            ->orderBy('phrase')
            ->all();
    }

    /**
     * Get MWEs by semantic type
     */
    public static function listBySemanticType(int $idGrammarGraph, string $semanticType): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('semanticType', '=', $semanticType)
            ->orderBy('phrase')
            ->all();
    }

    /**
     * Create new MWE
     */
    public static function create(array $data): int
    {
        // Ensure components is JSON encoded
        if (isset($data['components']) && is_array($data['components'])) {
            $data['length'] = count($data['components']);
            $data['components'] = json_encode($data['components']);
        }

        return Criteria::create('parser_mwe', $data);
    }

    /**
     * Update MWE
     */
    public static function update(int $id, array $data): void
    {
        // Ensure components is JSON encoded
        if (isset($data['components']) && is_array($data['components'])) {
            $data['length'] = count($data['components']);
            $data['components'] = json_encode($data['components']);
        }

        Criteria::table('parser_mwe')
            ->where('idMWE', '=', $id)
            ->update($data);
    }

    /**
     * Delete MWE
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_mwe', 'idMWE', $id);
    }

    /**
     * Get components array from MWE
     */
    public static function getComponents(object $mwe): array
    {
        if (is_string($mwe->components)) {
            return json_decode($mwe->components, true);
        }

        return $mwe->components;
    }

    /**
     * Check if word matches expected component at position
     */
    public static function matchesComponent(object $mwe, string $word, int $position): bool
    {
        $components = self::getComponents($mwe);

        if (! isset($components[$position])) {
            return false;
        }

        return strtolower($components[$position]) === strtolower($word);
    }

    /**
     * Get POS for an MWE from view_lemma_pos
     *
     * MWEs are stored in the lexicon with space-separated components (e.g., "a não ser que")
     *
     * @param  string  $mwePhrase  Space-separated MWE (e.g., "a fim de")
     * @param  int  $idLanguage  Language ID (default 1 for Portuguese)
     * @return string|null POS tag or null if not found
     */
    public static function getPOS(string $mwePhrase, int $idLanguage = 1): ?string
    {
        // MWEs are stored with spaces in the lexicon (view_lemma_pos)
        $result = Criteria::table('view_lemma_pos')
            ->where('name', '=', $mwePhrase)
            ->where('idLanguage', '=', $idLanguage)
            ->first();

        return $result ? $result->POS : null;
    }

    /**
     * Get all possible prefixes for MWEs starting with a word
     */
    public static function getPrefixesStartingWith(int $idGrammarGraph, string $firstWord): array
    {
        $mwes = self::getStartingWith($idGrammarGraph, $firstWord);
        $prefixes = [];

        foreach ($mwes as $mwe) {
            $components = self::getComponents($mwe);

            // Generate all prefixes (1-word, 2-word, ..., n-word)
            for ($i = 1; $i <= count($components); $i++) {
                $prefixComponents = array_slice($components, 0, $i);
                $prefixPhrase = implode(' ', $prefixComponents);

                $prefixes[] = (object) [
                    'idMWE' => $mwe->idMWE,
                    'phrase' => $prefixPhrase,
                    'components' => $prefixComponents,
                    'threshold' => $i,
                    'semanticType' => $mwe->semanticType,
                    'isComplete' => ($i === count($components)),
                    'fullPhrase' => $mwe->phrase,
                ];
            }
        }

        return $prefixes;
    }

    // =========================================================================
    // Variable Component Pattern Methods
    // =========================================================================

    /**
     * Get extended-format MWEs anchored by a specific word
     *
     * Returns MWEs where anchorWord matches the given word.
     * Used for efficient lookup of patterns with at least one fixed word component.
     */
    public static function getByAnchorWord(int $idGrammarGraph, string $anchorWord): array
    {
        $anchorWord = strtolower($anchorWord);

        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('componentFormat', '=', 'extended')
            ->where('anchorWord', '=', $anchorWord)
            ->all();
    }

    /**
     * Get fully variable MWEs (no fixed word anchor)
     *
     * These patterns have no fixed word component and must be checked
     * against every token position in the sentence.
     */
    public static function getFullyVariable(int $idGrammarGraph): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('componentFormat', '=', 'extended')
            ->whereNull('anchorWord')
            ->all();
    }

    /**
     * Get parsed components, normalizing both formats to extended structure
     *
     * Simple format: ["word1", "word2"] -> [{"type": "W", "value": "word1"}, ...]
     * Extended format: returned as-is
     *
     * @return array<array{type: string, value: string}>
     */
    public static function getParsedComponents(object $mwe): array
    {
        $components = self::getComponents($mwe);
        $format = $mwe->componentFormat ?? 'simple';

        if ($format === 'extended') {
            return $components;
        }

        // Convert simple format to extended format for uniform processing
        return array_map(fn ($word) => [
            'type' => 'W',
            'value' => $word,
        ], $components);
    }

    /**
     * Check if a component matches a token
     *
     * @param  array{type: string, value: string}  $component
     */
    public static function componentMatchesToken(array $component, PhrasalCENode $token): bool
    {
        $type = MWEComponentType::from($component['type']);

        return $type->matchesToken($component['value'] ?? '', $token);
    }

    /**
     * Calculate anchor position and word from extended components
     *
     * Finds the first fixed-word (W type) component and returns its position and value.
     * Returns null values if no fixed word component exists.
     *
     * @return array{position: int|null, word: string|null}
     */
    public static function calculateAnchor(array $components): array
    {
        foreach ($components as $position => $component) {
            // Handle both simple (string) and extended (array) formats
            if (is_string($component)) {
                return [
                    'position' => $position,
                    'word' => strtolower($component),
                ];
            }

            if (is_array($component) && ($component['type'] ?? '') === 'W') {
                return [
                    'position' => $position,
                    'word' => strtolower($component['value']),
                ];
            }
        }

        // No fixed word found (fully variable pattern)
        return ['position' => null, 'word' => null];
    }

    /**
     * Detect component format from components array
     *
     * Simple format: All elements are strings
     * Extended format: All elements are arrays with type/value keys
     */
    public static function detectComponentFormat(array $components): string
    {
        if (empty($components)) {
            return 'simple';
        }

        $firstComponent = $components[0];

        return is_array($firstComponent) ? 'extended' : 'simple';
    }

    /**
     * Create MWE with automatic anchor calculation
     *
     * Handles both simple and extended formats, automatically
     * calculating anchor position and word.
     */
    public static function createExtended(array $data): int
    {
        $components = $data['components'];
        $format = $data['componentFormat'] ?? self::detectComponentFormat($components);

        $data['componentFormat'] = $format;

        if ($format === 'extended') {
            $anchor = self::calculateAnchor($components);
            $data['anchorPosition'] = $anchor['position'];
            $data['anchorWord'] = $anchor['word'];
        } else {
            // Simple format: first word is always anchor
            $data['anchorPosition'] = 0;
            $data['anchorWord'] = strtolower($components[0]);
        }

        return self::create($data);
    }

    /**
     * Get all MWEs for a grammar (both simple and extended formats)
     */
    public static function listAllByGrammar(int $idGrammarGraph): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('componentFormat')
            ->orderBy('phrase')
            ->all();
    }

    /**
     * Get MWEs by component format
     */
    public static function listByFormat(int $idGrammarGraph, string $format): array
    {
        return Criteria::table('parser_mwe')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('componentFormat', '=', $format)
            ->orderBy('phrase')
            ->all();
    }
}

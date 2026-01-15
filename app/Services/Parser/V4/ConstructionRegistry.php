<?php

namespace App\Services\Parser\V4;

use App\Data\Parser\ConstructionDefinition;
use App\Repositories\Criteria;

/**
 * Construction Registry Service
 *
 * Manages the registry of all construction patterns for the V4 parser.
 * Provides efficient access to constructions by various criteria:
 * - By construction type (mwe, phrasal, clausal, sentential)
 * - By POS tag (what constructions can start with this POS)
 * - By priority (for resolution when multiple constructions match)
 *
 * Constructions are cached in memory for performance.
 */
class ConstructionRegistry
{
    private array $constructionsByType = [];

    private array $constructionsByName = [];

    private array $allConstructions = [];

    private bool $loaded = false;

    public function __construct() {}

    /**
     * Load constructions for a given grammar graph
     */
    public function loadConstructions(int $idGrammarGraph): void
    {
        if ($this->loaded) {
            return;
        }

        $constructions = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', $idGrammarGraph)
            ->where('enabled', true)
            ->orderBy('priority', 'desc')
            ->all();

        foreach ($constructions as $construction) {
            $def = ConstructionDefinition::from([
                'idConstruction' => $construction->idConstruction,
                'idGrammarGraph' => $construction->idGrammarGraph,
                'name' => $construction->name,
                'constructionType' => $construction->constructionType,
                'pattern' => $construction->pattern,
                'compiledPattern' => json_decode($construction->compiledPattern ?? '[]', true),
                'priority' => $construction->priority,
                'enabled' => $construction->enabled,
                'phrasalCE' => $construction->phrasalCE,
                'clausalCE' => $construction->clausalCE,
                'sententialCE' => $construction->sententialCE,
                'constraints' => json_decode($construction->constraints ?? '[]', true),
                'aggregateAs' => $construction->aggregateAs,
                'semanticType' => $construction->semanticType,
                'semantics' => json_decode($construction->semantics ?? 'null', true),
                'lookaheadEnabled' => $construction->lookaheadEnabled,
                'lookaheadMaxDistance' => $construction->lookaheadMaxDistance,
                'invalidationPatterns' => json_decode($construction->invalidationPatterns ?? '[]', true),
                'confirmationPatterns' => json_decode($construction->confirmationPatterns ?? '[]', true),
                'description' => $construction->description,
                'examples' => json_decode($construction->examples ?? 'null', true),
            ]);

            $this->allConstructions[] = $def;
            $this->constructionsByName[$def->name] = $def;

            if (! isset($this->constructionsByType[$def->constructionType])) {
                $this->constructionsByType[$def->constructionType] = [];
            }
            $this->constructionsByType[$def->constructionType][] = $def;
        }

        $this->loaded = true;
    }

    /**
     * Get all constructions that could start with a given token
     *
     * @param  object  $token  Token with POS, lemma, features, etc.
     * @return array<ConstructionDefinition> Constructions ordered by priority
     */
    public function getConstructionsForToken(object $token): array
    {
        $matching = [];

        foreach ($this->allConstructions as $construction) {
            if ($this->constructionCanStartWith($construction, $token)) {
                $matching[] = $construction;
            }
        }

        // Already sorted by priority during load
        return $matching;
    }

    /**
     * Get constructions that could extend an existing alternative
     *
     * This is used when an alternative is looking for its next component.
     * For now, this is a placeholder - actual implementation will use
     * the construction's compiled pattern to determine valid continuations.
     *
     * @param  mixed  $alternative  The alternative to extend
     * @param  object  $token  The next token
     * @return array<ConstructionDefinition> Possible constructions
     */
    public function getConstructionsForUpdate(mixed $alternative, object $token): array
    {
        // TODO: Implement pattern-based continuation matching
        // For now, return empty array
        return [];
    }

    /**
     * Get a construction by name
     */
    public function getConstruction(string $name): ?ConstructionDefinition
    {
        return $this->constructionsByName[$name] ?? null;
    }

    /**
     * Get all constructions of a specific type
     *
     * @param  string  $type  One of: mwe, phrasal, clausal, sentential
     * @return array<ConstructionDefinition>
     */
    public function getConstructionsByType(string $type): array
    {
        return $this->constructionsByType[$type] ?? [];
    }

    /**
     * Get all MWE constructions
     */
    public function getMWEConstructions(): array
    {
        return $this->getConstructionsByType('mwe');
    }

    /**
     * Get all phrasal constructions
     */
    public function getPhrasalConstructions(): array
    {
        return $this->getConstructionsByType('phrasal');
    }

    /**
     * Get all clausal constructions
     */
    public function getClausalConstructions(): array
    {
        return $this->getConstructionsByType('clausal');
    }

    /**
     * Get all sentential constructions
     */
    public function getSententialConstructions(): array
    {
        return $this->getConstructionsByType('sentential');
    }

    /**
     * Get all loaded constructions
     */
    public function getAllConstructions(): array
    {
        return $this->allConstructions;
    }

    /**
     * Check if a construction can start with a given token
     *
     * Simple heuristic for now:
     * - Check if pattern contains the token's POS tag
     * - Check if pattern contains literal words that match the token
     * - MWE constructions: check if first component matches
     *
     * TODO: Enhance with proper pattern matching using compiled pattern
     */
    private function constructionCanStartWith(ConstructionDefinition $construction, object $token): bool
    {
        $pattern = $construction->pattern;
        $pos = $token->upos ?? $token->pos ?? '';
        $word = strtolower($token->word ?? $token->form ?? '');
        $lemma = strtolower($token->lemma ?? '');

        // Check for POS match in pattern (e.g., {NOUN}, {VERB}, etc.)
        if (str_contains($pattern, "{{$pos}}")) {
            return true;
        }

        // Check for literal word match (e.g., "café", "apesar")
        if (str_contains($pattern, "\"{$word}\"") || str_contains($pattern, "\"{$lemma}\"")) {
            return true;
        }

        // For constructions that reference other constructions (e.g., "ARG_NP", "HEAD_NOUN")
        // This is a simplified check - actual implementation will use compiled pattern
        if (preg_match('/^[A-Z_]+/', $pattern)) {
            // This is likely a higher-level construction that references other constructions
            // We'll need to check recursively - for now, skip these
            return false;
        }

        return false;
    }

    /**
     * Clear the registry (for testing or reloading)
     */
    public function clear(): void
    {
        $this->constructionsByType = [];
        $this->constructionsByName = [];
        $this->allConstructions = [];
        $this->loaded = false;
    }

    /**
     * Get statistics about loaded constructions
     */
    public function getStatistics(): array
    {
        return [
            'total' => count($this->allConstructions),
            'mwe' => count($this->constructionsByType['mwe'] ?? []),
            'phrasal' => count($this->constructionsByType['phrasal'] ?? []),
            'clausal' => count($this->constructionsByType['clausal'] ?? []),
            'sentential' => count($this->constructionsByType['sentential'] ?? []),
        ];
    }
}

<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

/**
 * Construction Definition Data Transfer Object
 *
 * Represents a construction pattern in the V4 unified constructional parser.
 * Each construction is a form-meaning pairing that can match token sequences
 * and assign CE labels at multiple linguistic levels.
 *
 * Construction types:
 * - mwe: Multi-word expressions (highest priority)
 * - phrasal: Phrasal level constructions (Head, Mod, Adp, Lnk, etc.)
 * - clausal: Clausal level constructions (Pred, Arg, CPP, FPM, Gen, etc.)
 * - sentential: Sentential level constructions (Main, Adv, Rel, Comp, etc.)
 *
 * Priority bands:
 * - MWE: 100-199 (highest)
 * - Phrasal: 50-99
 * - Clausal: 20-49
 * - Sentential: 1-19 (lowest)
 */
class ConstructionDefinition extends Data
{
    public function __construct(
        public int $idConstruction,
        public int $idGrammarGraph,
        public string $name,
        public string $constructionType,
        public string $pattern,
        public ?array $compiledPattern,
        public int $priority,
        public bool $enabled,
        public ?string $phrasalCE,
        public ?string $clausalCE,
        public ?string $sententialCE,
        public array $constraints,
        public ?string $aggregateAs,
        public ?string $semanticType,
        public ?array $semantics,
        public bool $lookaheadEnabled,
        public int $lookaheadMaxDistance,
        public array $invalidationPatterns,
        public array $confirmationPatterns,
        public ?string $description,
        public ?array $examples,
    ) {}

    public static function rules(): array
    {
        return [
            'idConstruction' => ['sometimes', 'integer'],
            'idGrammarGraph' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'constructionType' => ['required', 'string', 'in:mwe,phrasal,clausal,sentential'],
            'pattern' => ['required', 'string', 'min:1'],
            'compiledPattern' => ['nullable', 'array'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:199'],
            'enabled' => ['sometimes', 'boolean'],
            'phrasalCE' => ['nullable', 'string', 'max:20'],
            'clausalCE' => ['nullable', 'string', 'max:20'],
            'sententialCE' => ['nullable', 'string', 'max:20'],
            'constraints' => ['sometimes', 'array'],
            'aggregateAs' => ['nullable', 'string', 'max:255'],
            'semanticType' => ['nullable', 'string', 'max:20'],
            'semantics' => ['nullable', 'array'],
            'lookaheadEnabled' => ['sometimes', 'boolean'],
            'lookaheadMaxDistance' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'invalidationPatterns' => ['sometimes', 'array'],
            'confirmationPatterns' => ['sometimes', 'array'],
            'description' => ['nullable', 'string'],
            'examples' => ['nullable', 'array'],
        ];
    }

    /**
     * Check if this construction can match at the given POS tag
     */
    public function canMatchPOS(string $pos): bool
    {
        // Simple check - can be enhanced with pattern analysis
        return str_contains($this->pattern, "{{$pos}}") ||
               str_contains($this->pattern, '"');
    }

    /**
     * Get the primary CE label for this construction
     */
    public function getPrimaryCE(): ?string
    {
        return $this->phrasalCE ?? $this->clausalCE ?? $this->sententialCE;
    }

    /**
     * Check if this is an MWE construction
     */
    public function isMWE(): bool
    {
        return $this->constructionType === 'mwe';
    }

    /**
     * Check if lookahead is enabled for this construction
     */
    public function hasLookahead(): bool
    {
        return $this->lookaheadEnabled && ! empty($this->invalidationPatterns);
    }
}

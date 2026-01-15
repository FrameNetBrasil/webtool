<?php

namespace App\Data\Parser;

use App\Rules\ValidMWEComponents;
use Spatie\LaravelData\Data;

/**
 * MWE (Multi-Word Expression) Data Transfer Object
 *
 * Supports both simple (fixed word) and extended (variable component) formats.
 *
 * Simple format components: ["word1", "word2", "word3"]
 * Extended format components: [{"type": "P", "value": "NOUN"}, {"type": "W", "value": "de"}, ...]
 *
 * Component types (extended format):
 * - W (Word): Exact word form match
 * - L (Lemma): Match against token lemma
 * - P (POS): Match against UDPOS tag
 * - C (CE): Match against phrasal CE label
 * - * (Wildcard): Match any token
 *
 * Semantic types:
 * - Legacy v1: E (Entity), V (Eventive), A (Attribute), F (Function), R (Relational)
 * - New v2 (Phrasal CEs): Head, Mod, Adm, Adp, Lnk, Clf, Idx, Conj
 */
class MWEData extends Data
{
    public function __construct(
        public int $idGrammarGraph,
        public string $phrase,
        public array $components,
        public string $semanticType,
        public string $componentFormat = 'simple',
        public ?int $anchorPosition = null,
        public ?string $anchorWord = null,
    ) {}

    public static function rules(): array
    {
        return [
            'idGrammarGraph' => ['required', 'integer', 'exists:parser_grammar_graph,idGrammarGraph'],
            'phrase' => ['required', 'string', 'min:3', 'max:255'],
            'components' => ['required', 'array', 'min:2', new ValidMWEComponents],
            'semanticType' => ['required', 'string', 'in:E,V,A,F,R,Head,Mod,Adm,Adp,Lnk,Clf,Idx,Conj'],
            'componentFormat' => ['sometimes', 'string', 'in:simple,extended'],
            'anchorPosition' => ['nullable', 'integer', 'min:0'],
            'anchorWord' => ['nullable', 'string', 'max:100'],
        ];
    }
}

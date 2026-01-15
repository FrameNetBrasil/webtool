<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class GrammarNodeData extends Data
{
    public function __construct(
        public int $idGrammarGraph,
        public string $label,
        public string $type,        // E, R, A, F, MWE
        public int $threshold = 1,
        public ?int $idLemma = null,
    ) {}

    public static function rules(): array
    {
        return [
            'idGrammarGraph' => ['required', 'integer', 'exists:parser_grammar_graph,idGrammarGraph'],
            'label' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:E,R,A,F,MWE'],
            'threshold' => ['integer', 'min:1'],
            'idLemma' => ['nullable', 'integer', 'exists:lemma,idLemma'],
        ];
    }
}

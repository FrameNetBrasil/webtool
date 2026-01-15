<?php

namespace App\Data\Parser;

use Spatie\LaravelData\Data;

class ParseInputData extends Data
{
    public function __construct(
        public string $sentence,
        public int $idGrammarGraph = 1,
        public string $queueStrategy = 'fifo',
    ) {}

    public static function rules(): array
    {
        return [
            'sentence' => ['required', 'string', 'min:1', 'max:1000'],
            'idGrammarGraph' => ['required', 'integer', 'exists:parser_grammar_graph,idGrammarGraph'],
            'queueStrategy' => ['required', 'string', 'in:fifo,lifo'],
        ];
    }
}

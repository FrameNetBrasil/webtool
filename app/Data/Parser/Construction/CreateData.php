<?php

namespace App\Data\Parser\Construction;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class CreateData extends Data
{
    public function __construct(
        #[Required]
        public int $idGrammarGraph = 0,

        #[Required, Min(3), Max(100)]
        public string $name = '',

        #[Required, In(['mwe', 'phrasal', 'clausal', 'sentential'])]
        public string $constructionType = 'phrasal',

        #[Required]
        public string $pattern = '',

        #[Min(1), Max(199)]
        public int $priority = 50,

        public bool $enabled = true,

        #[Max(100)]
        public ?string $phrasalCE = null,

        #[Max(100)]
        public ?string $clausalCE = null,

        #[Max(100)]
        public ?string $sententialCE = null,

        public ?string $constraints = null,

        #[Max(100)]
        public ?string $aggregateAs = null,

        #[Max(100)]
        public ?string $semanticType = null,

        public ?string $semantics = null,

        public bool $lookaheadEnabled = false,

        #[Min(0), Max(10)]
        public int $lookaheadMaxDistance = 3,

        public ?string $invalidationPatterns = null,

        public ?string $confirmationPatterns = null,

        #[Max(2000)]
        public ?string $description = null,

        public ?string $examples = null,
    ) {}
}

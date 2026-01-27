<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daisy Semantic Parser Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Daisy (Disambiguation Algorithm for Inferring
    | the Semantics of Y) semantic parser. Defines mappings, weights, and
    | parameters for the disambiguation pipeline.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Parameters
    |--------------------------------------------------------------------------
    */

    'defaultLanguage' => 1, // 1=Portuguese, 2=English
    'defaultLevel' => 5, // Frame relation depth (1-5)
    'defaultSearchType' => 4, // Network type (1-4)

    /*
    |--------------------------------------------------------------------------
    | Trankit Service URL
    |--------------------------------------------------------------------------
    |
    | URL for the Trankit UD parsing service
    |
    */

    'trankitUrl' => env('TRANKIT_URL', 'http://localhost:8405'),

    /*
    |--------------------------------------------------------------------------
    | UPOS to GRID Function Mapping
    |--------------------------------------------------------------------------
    |
    | Maps Universal Dependencies POS tags to GRID semantic functions:
    | - ENT: Entity
    | - EVT: Event
    | - QE: Quality Entity (adjective)
    | - QV: Quality Event (adverb)
    | - REL: Relation
    | - REF: Reference
    | - QUANT: Quantifier
    | - AUX: Auxiliary
    | - PUNCT: Punctuation
    |
    */

    'uposToGrid' => [
        'ADJ' => 'QE',      // Quality Entity
        'ADP' => 'REL',     // Relation
        'PUNCT' => 'PUNCT',   // Punctuation
        'ADV' => 'QV',      // Quality Event
        'AUX' => 'AUX',     // Auxiliary
        'SYM' => 'ENT',     // Entity
        'INTJ' => 'ENT',
        'CCONJ' => 'REL',     // Coordinating conjunction
        'X' => 'ENT',
        'NOUN' => 'ENT',     // Entity
        'DET' => 'REF',     // Reference
        'PROPN' => 'ENT',     // Proper noun
        'NUM' => 'QUANT',   // Quantifier
        'VERB' => 'EVT',     // Event
        'PART' => 'ENT',
        'PRON' => 'ENT',
        'SCONJ' => 'REL',     // Subordinating conjunction
    ],

    /*
    |--------------------------------------------------------------------------
    | GRID Function to POS Mapping
    |--------------------------------------------------------------------------
    |
    | Maps GRID functions to expected POS tags for lexical unit matching:
    | - ENT → N (Noun)
    | - EVT → V (Verb)
    | - QE → A (Adjective)
    | - QV → ADV (Adverb)
    |
    */

    'gridToPOS' => [
        'ENT' => 'N',
        'EVT' => 'V',
        'QE' => 'A',
        'QV' => 'ADV',
    ],

    /*
    |--------------------------------------------------------------------------
    | Combination Value Matrix
    |--------------------------------------------------------------------------
    |
    | Defines which GRID functions can combine and their compatibility weights.
    | Used during function disambiguation when a word has multiple possible
    | functions. Format: [can_combine, weight]
    |
    */

    'combinationValue' => [
        'ENT' => [
            'REL' => [1, 10],
            'QE' => [1, 9],
            'QUANT' => [1, 8],
            'EVT' => [1, 7],
            'REF' => [1, 6],
        ],
        'EVT' => [
            'ENT' => [1, 10],
            'REL' => [1, 9],
            'QV' => [1, 8],
            'AUX' => [1, 7],
        ],
        'QE' => [
            'ENT' => [1, 10],
            'REL' => [1, 9],
            'QV' => [1, 8],
        ],
        'QV' => [
            'EVT' => [1, 10],
            'QE' => [1, 9],
            'ENT' => [1, 8],
        ],
        'REL' => [
            'ENT' => [1, 10],
            'EVT' => [1, 9],
            'REL' => [1, 8],
        ],
        'REF' => [
            'ENT' => [1, 10],
            'QUANT' => [1, 9],
        ],
        'QUANT' => [
            'ENT' => [1, 10],
            'REF' => [1, 9],
        ],
        'AUX' => [
            'EVT' => [1, 10],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependency Relations for Clusters
    |--------------------------------------------------------------------------
    |
    | Defines cluster dependency relationships. Used to determine which
    | clusters can depend on which other clusters.
    |
    */

    'dependencyRelations' => [
        'ENT' => ['EVT' => 1],
        'EVT' => ['REL' => 1],
        'REL' => ['ENT' => 1],
        'QE' => ['ENT' => 1],
        'QV' => ['EVT' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cluster Compatibility
    |--------------------------------------------------------------------------
    |
    | Defines which GRID functions can be grouped into each cluster type.
    |
    */

    'clusterCanUse' => [
        'ENT' => ['ENT', 'REF', 'QUANT'],
        'EVT' => ['EVT', 'AUX'],
        'QUALE' => ['QE', 'QV', 'QUANT'],
        'REL' => ['REL'],
    ],

    /*
    |--------------------------------------------------------------------------
    | UD Relations Filter
    |--------------------------------------------------------------------------
    |
    | Universal Dependencies relations to keep for GRID processing.
    | Other relations are filtered out as they don't contribute to semantic
    | frame disambiguation.
    |
    */

    'udRelationsKeep' => [
        'root',
        'nsubj',
        'obj',
        'iobj',
        'csubj',
        'ccomp',
        'xcomp',
        'obl',
        'advcl',
        'acl',
        'amod',
        'nmod',
        'advmod',
    ],

    /*
    |--------------------------------------------------------------------------
    | Frame Relation Weights
    |--------------------------------------------------------------------------
    |
    | Weights applied to different types of frame-to-frame relations during
    | semantic network construction. Higher weights indicate stronger semantic
    | connections.
    |
    */

    'relationWeights' => [
        'rel_inheritance' => 0.95,
        'rel_perspective_on' => 0.9,
        'rel_subframe' => 0.85,
        'rel_using' => 0.0, // Not used in disambiguation
        'rel_see_also' => 0,
        'rel_causative_of' => 0.7,
        'rel_inchoative_of' => 0.7,
        'rel_metaphorical_projection' => 0,
        'rel_precedes' => 0.7,
        'rel_fe_f' => 0.9,
        'rel_qualia' => 0.9,
        'rel_evokes' => 1.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frame Element Weights
    |--------------------------------------------------------------------------
    |
    | Weights for Frame Element constraint relations.
    |
    */

    'feWeights' => [
        'cty_core' => 0.5,
        'cty_peripheral' => 0.0,
        'cty_extra_thematic' => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Energy Bonuses
    |--------------------------------------------------------------------------
    |
    | Bonus energy values applied during spreading activation:
    | - mwe: Multi-word expression bonus
    | - mknob: Domain-specific (MKNOB domain=5) bonus
    | - qualia_depth_1: Qualia relation at depth 1
    | - qualia_depth_2: Qualia relation at depth 2
    |
    */

    'energyBonus' => [
        'mwe' => 10,
        'mknob' => 5,
        'qualia_depth_1' => 0.9,
        'qualia_depth_2' => 0.45, // 0.9 / 2
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Expansion
    |--------------------------------------------------------------------------
    |
    | Parameters for recursive frame network expansion:
    | - valueDecrement: Amount to reduce value at each recursion level
    | - minValue: Minimum value to continue expansion
    | - maxQualiaDepth: Maximum depth for qualia relation search
    |
    */

    'networkExpansion' => [
        'valueDecrement' => 0.4,
        'minValue' => 0.0,
        'maxQualiaDepth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Types
    |--------------------------------------------------------------------------
    |
    | Different levels of semantic network construction:
    | 1: Direct frames only (fastest)
    | 2: Direct + frame family relations (recommended)
    | 3: Level 2 + Frame Element constraints
    | 4: Level 3 + Qualia relations (most comprehensive)
    |
    */

    'searchTypes' => [
        1 => 'Direct frames only',
        2 => 'Direct + frame family',
        3 => 'Frame family + FE constraints',
        4 => 'Full network + Qualia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Winner Selection
    |--------------------------------------------------------------------------
    |
    | Configuration for selecting winning frames:
    | - excludeVerbs: Whether to exclude verbs from winner selection
    | - gregnetMode: Allow multiple winners per word (for GregNet integration)
    |
    */

    'winnerSelection' => [
        'excludeVerbs' => false,
        'gregnetMode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache TTL (time to live) in seconds for different query types:
    | - lemmaToLU: Lemma to Lexical Unit mappings (24 hours)
    | - frameRelations: Frame-to-frame relations (24 hours)
    | - qualiaRelations: Qualia structure relations (24 hours)
    | - gridFunctions: GRID function mappings (permanent via 0)
    |
    */

    'cacheTTL' => [
        'lemmaToLU' => 86400, // 24 hours
        'frameRelations' => 86400,
        'qualiaRelations' => 86400,
        'gridFunctions' => 0, // No expiration
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain IDs
    |--------------------------------------------------------------------------
    |
    | Special domain identifiers:
    | - mknob: MKNOB domain ID for domain-specific frames
    |
    */

    'domains' => [
        'mknob' => 5,
    ],
];

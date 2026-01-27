<?php

use App\Models\CLN\BNode;
use App\Models\CLN\JNode;

return [
    /*
    |--------------------------------------------------------------------------
    | Node-Centric Architecture Feature Flags
    |--------------------------------------------------------------------------
    |
    | These flags control the phased rollout of the node-centric refactoring.
    | Each phase can be enabled independently for testing and validation.
    |
    | Phases:
    | - Phase 1: Event system foundation (non-breaking)
    | - Phase 2: Node self-management methods
    | - Phase 3: Pattern matching → node-centric
    | - Phase 4: Prediction checking → node-centric
    | - Phase 5: Column orchestration cleanup
    | - Phase 6: Performance optimization
    |
    */

    'node_centric_enabled' => env('CLN_NODE_CENTRIC', false),

    'node_centric_phases' => [
        // Phase 1: Construction activation - L23 nodes trigger construction matching
        'construction_activation' => env('CLN_NODE_CONSTRUCTION', false),

        // Phase 2: Prediction generation - Partials generate their own predictions
        'prediction_generation' => env('CLN_NODE_PREDICTION_GEN', false),

        // Phase 3: Construction evocation - Evocation happens on token activation
        'evocation' => env('CLN_NODE_EVOCATION', false),

        // Phase 4: Completion checking - Partials self-confirm and create L23 feedback
        'completion_checking' => env('CLN_NODE_COMPLETION', false),

        // Phase 5: Event-driven flow - Remove iteration loop, use event cascade
        'event_driven_flow' => env('CLN_NODE_EVENTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | CLN Node Type Mappings
    |--------------------------------------------------------------------------
    |
    | Maps information types to node classes (JNode or BNode).
    | - JNode: Junction nodes (accumulate evidence from multiple inputs)
    | - BNode: Branch nodes (broadcast activation to multiple outputs)
    |
    */

    'node_types' => [
        // L23 Layer (Input)
        'word' => BNode::class,      // Lexical forms (broadcast)
        'lemma' => BNode::class,     // Abstract concepts (broadcast)
        'feature' => JNode::class,   // Morphological features (accumulate)
        'pos' => BNode::class,       // POS tags (broadcast from input)
        'ce_label' => \App\Models\CLN\JNode::class,

        // L5 Layer (Output)
        'construction' => JNode::class,        // Constructions (accumulate evidence)
        'mwe' => JNode::class,                 // MWEs (accumulate evidence)
        'partial_construction' => JNode::class,  // Partial constructions (incomplete matches)
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Thresholds
    |--------------------------------------------------------------------------
    |
    | Default activation thresholds for each node type.
    | null = auto-threshold (all inputs must fire)
    | integer = specific threshold
    |
    */

    'thresholds' => [
        'construction' => null,        // auto (all inputs)
        'mwe' => null,                 // auto (all inputs)
        'partial_construction' => null,  // auto (all inputs)
        'feature' => 1,                // single input sufficient
        'pos' => 1,                    // single input sufficient
        'word' => 1,                   // single input sufficient
        'lemma' => 1,                  // single input sufficient
        'ce_label' => 1,                  // single input sufficient
    ],

    /*
    |--------------------------------------------------------------------------
    | Activation Parameters
    |--------------------------------------------------------------------------
    |
    | Parameters controlling activation dynamics in the CLN network.
    |
    */

    'activation' => [
        'partial_construction_threshold' => 0.25,  // Min activation for partial construction to generate predictions
        'boost_factor' => 0.3,                     // Lateral confirmation boost strength
        'decay_rate' => 0.9,                       // Temporal decay per time step
        'inhibition_strength' => 0.5,              // Lateral inhibition strength between competing constructions
    ],

    /*
    |--------------------------------------------------------------------------
    | Composition Parameters
    |--------------------------------------------------------------------------
    |
    | Parameters controlling recursive composition (L5 → L23 feedback).
    | When constructions complete at L5, they create nodes in L23 that can
    | participate in higher-level composition.
    |
    */

    'composition' => [
        'max_depth' => 3,  // Maximum composition depth (word → MWE → phrase → clause)
        'enable_recursive_composition' => true,  // Global toggle for L5 → L23 feedback
        'prevent_self_composition' => true,  // Prevent construction from composing with itself
    ],

    /*
    |--------------------------------------------------------------------------
    | Prediction Management
    |--------------------------------------------------------------------------
    |
    | Parameters controlling centralized prediction management.
    | When enabled, predictions from L5 partial constructions are stored
    | in the ColumnSequenceManager's registry instead of creating predicted
    | nodes directly in L23. This enables better control, visibility, and
    | memory efficiency.
    |
    */

    'predictions' => [
        // Enable centralized prediction manager
        'centralized_manager' => env('CLN_CENTRALIZED_PREDICTIONS', true),

        // Time-to-live for unmatched predictions (seconds)
        // Predictions older than this are cleaned up during periodic maintenance
        'ttl' => env('CLN_PREDICTION_TTL', 60.0),

        // Cleanup frequency (tokens)
        // Run cleanup every N tokens processed
        'cleanup_frequency' => env('CLN_PREDICTION_CLEANUP_FREQ', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pattern Matching Optimization
    |--------------------------------------------------------------------------
    |
    | Parameters controlling pattern matching performance optimizations.
    | The shared graph approach deduplicates common pattern nodes (e.g., all
    | {NOUN} slots) across all constructions, reducing pattern matching from
    | O(N×M) to near O(1) where N = number of constructions, M = number of tokens.
    |
    */

    'pattern_matching' => [
        // Enable shared pattern graph (GraphPatternMatcher)
        // When true: Uses deduplicated shared graph from parser_pattern_node/edge tables
        // When false: Uses individual construction patterns (backward compatible)
        'use_shared_graph' => env('CLN_USE_SHARED_GRAPH', false),

        // Enable graph caching (recommended for production)
        // When true: Graph is loaded once and cached in memory
        // When false: Graph is reloaded from database each time
        'cache_graph' => env('CLN_CACHE_PATTERN_GRAPH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | PC Parser Configuration (CLN v3)
    |--------------------------------------------------------------------------
    |
    | Configuration for the Pattern Completion (PC) parser, which implements
    | CLN v3 using a cortical column architecture with L1/L2/L5/L23 layers.
    |
    | These settings control the parser's behavior during sentence processing.
    | All values can be overridden at runtime when creating the parser:
    |
    |   $parser = new CLNParser(config: ['max_timesteps' => 100]);
    |
    | Environment variables (defined below) can be used to customize settings
    | without modifying this file.
    |
    */

    'pc_parser' => [

        /*
        |----------------------------------------------------------------------
        | Dynamics Settings
        |----------------------------------------------------------------------
        |
        | Core dynamics parameters that control the temporal evolution of the
        | network during parsing.
        |
        */

        'dynamics' => [
            // Timestep size in seconds (default: 10ms)
            'dt' => env('PC_DT', 0.01),

            // Maximum number of timesteps to run
            'max_timesteps' => env('PC_MAX_TIMESTEPS', 500),

            // Minimum timesteps before checking convergence
            'min_timesteps' => env('PC_MIN_TIMESTEPS', 10),
        ],

        /*
        |----------------------------------------------------------------------
        | Convergence Settings
        |----------------------------------------------------------------------
        |
        | Parameters that control when the parser considers the network to have
        | converged to a stable solution.
        |
        */

        'convergence' => [
            // Maximum change in activation for convergence
            'threshold' => env('PC_CONVERGENCE_THRESHOLD', 0.001),

            // Number of consecutive stable timesteps required
            'min_stable_steps' => env('PC_MIN_STABLE_STEPS', 5),

            // Window size for oscillation detection
            'oscillation_window' => env('PC_OSCILLATION_WINDOW', 10),

            // Check convergence every N timesteps
            'check_interval' => env('PC_CONVERGENCE_CHECK_INTERVAL', 5),
        ],

        /*
        |----------------------------------------------------------------------
        | Pruning Settings
        |----------------------------------------------------------------------
        |
        | Configuration for removing low-activation nodes during parsing to
        | improve efficiency and focus on promising constructions.
        |
        */

        'pruning' => [
            // Enable periodic pruning of low-activation nodes
            'enabled' => env('PC_PRUNING_ENABLED', true),

            // Prune every N timesteps
            'interval' => env('PC_PRUNING_INTERVAL', 10),

            // Pruning strategy: 'simple', 'smart', or 'aggressive'
            'strategy' => env('PC_PRUNING_STRATEGY', 'smart'),

            // Minimum activation threshold (nodes below this are pruned)
            'absolute_threshold' => env('PC_PRUNING_ABSOLUTE_THRESHOLD', 0.05),

            // Competitive gap from winner (prune if gap exceeds this)
            'competitive_gap' => env('PC_PRUNING_COMPETITIVE_GAP', 0.3),

            // Preserve completed constructions from pruning
            'preserve_completed' => env('PC_PRESERVE_COMPLETED', true),
        ],

        /*
        |----------------------------------------------------------------------
        | Input Scheduling
        |----------------------------------------------------------------------
        |
        | Controls how input words are presented to the network.
        |
        */

        'input' => [
            // Input mode: 'all_at_once' or 'incremental'
            'mode' => env('PC_INPUT_MODE', 'all_at_once'),
        ],

        /*
        |----------------------------------------------------------------------
        | Output Extraction
        |----------------------------------------------------------------------
        |
        | Settings for extracting final constructions from the parsed network.
        |
        */

        'output' => [
            // Only extract winning constructions (filter by competition)
            'extract_winners_only' => env('PC_EXTRACT_WINNERS_ONLY', true),

            // Minimum activation threshold for output
            'min_activation_threshold' => env('PC_MIN_ACTIVATION_THRESHOLD', 0.5),
        ],

        /*
        |----------------------------------------------------------------------
        | RNT Pattern Graph Integration
        |----------------------------------------------------------------------
        |
        | Configuration for the Relational Network Type (RNT) pattern graph.
        | The RNT graph uses a three-node-type architecture (DATA, OR, AND)
        | that enables incremental word-by-word parsing.
        |
        */

        'rnt' => [
            // Enable RNT pattern graph (default: false for backward compatibility)
            'enabled' => env('PC_RNT_ENABLED', false),

            // Cache RNT graph queries in memory for performance
            'cache_queries' => env('PC_RNT_CACHE_QUERIES', true),

            // Pre-warm cache with common POS tags and words on startup
            'warmup_cache' => env('PC_RNT_WARMUP_CACHE', true),
        ],

        /*
        |----------------------------------------------------------------------
        | Incremental Parsing
        |----------------------------------------------------------------------
        |
        | Settings for word-by-word incremental parsing (used with RNT graph).
        | When enabled, the parser processes each word as it arrives rather
        | than waiting for the complete sentence.
        |
        */

        'incremental' => [
            // Enable incremental word-by-word parsing
            'enabled' => env('PC_INCREMENTAL_ENABLED', false),

            // Number of dynamics timesteps to run after each word
            'timesteps_per_word' => env('PC_TIMESTEPS_PER_WORD', 20),

            // Prune low-activation nodes after each word (aggressive memory management)
            'prune_after_each_word' => env('PC_PRUNE_AFTER_EACH_WORD', false),

            // Extract partial completions after each word (for real-time feedback)
            'extract_after_each_word' => env('PC_EXTRACT_AFTER_EACH_WORD', false),
        ],

    ],
];

<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\BinaryErrorCalculator;
use App\Models\CLN_RNT\LearnGraph;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * CLN v3 Main Parser
 *
 * Orchestrates all components of the Construction Learning Network v3 parser:
 * - Input scheduling (L1 node creation)
 * - Pair-wise composition (L2 node creation)
 * - Competition dynamics (lateral inhibition)
 * - Activation dynamics (neural population updates)
 * - Completion detection (finished constructions)
 * - Pruning (remove low-activation nodes)
 * - Stability checking (convergence detection)
 * - Construction extraction (final parse result)
 */
class CLNParser
{
    private $querier;

    private InputParserService $inputParser;

    private PairwiseCompositor $compositor;

    private CompetitionManager $competition;

    private ActivationDynamicsParser $activationDynamicsParser;
    private ActivationDynamicsLearning $activationDynamicsLearning;

    private CompletionDetector $completionDetector;

    private StabilityChecker $stabilityChecker;

    private BinaryErrorCalculator $errorCalculator;

    private BuildStructureService $buildStructure;

    private ?GraphExportService $exporter;

    private RuntimeGraph|LearnGraph $graph;

    /**
     * Parser configuration
     */
    private array $config;

    public function __construct(
        string $process,
        $querier = null,
        ?InputParserService $inputParser = null,
        array $config = [],
        ?GraphExportService $exporter = null
    ) {
        $this->querier = $querier;
        $this->inputParser = $inputParser ?? new InputParserService;
        $this->config = array_merge($this->getDefaultConfig(), $config);

        // Initialize compositor with RNT querier if RNT is enabled
        $rntQuerier = null;
        //if ($this->config['rnt_enabled'] ?? false) {
            $rntQuerier = new RNTGraphQuerier;
        //}
        $this->buildStructure = new BuildStructureService();
        if($process == 'parsing') {
            // Initialize runtime graph
            $this->graph = new RuntimeGraph($this->buildStructure);
            $this->activationDynamicsParser = new ActivationDynamicsParser($this->graph);
        } else if ($process == 'learn') {
            // Initialize runtime graph
            $this->graph = new LearnGraph($this->buildStructure);
            $this->activationDynamicsLearning = new ActivationDynamicsLearning($this->graph);
        }
        $this->graph->setOutputDir($this->config['output_dir']);


        $this->compositor = new PairwiseCompositor($this->querier, $rntQuerier);
        $this->competition = new CompetitionManager;
        $this->errorCalculator = new BinaryErrorCalculator;
        $this->completionDetector = new CompletionDetector($this->querier);
        $this->exporter = $exporter;

        // Initialize stability checker with config
        $this->stabilityChecker = new StabilityChecker(
            convergenceThreshold: $this->config['convergence_threshold'],
            oscillationWindow: $this->config['oscillation_window'],
            minStableSteps: $this->config['min_stable_steps']
        );
    }
    /**
     * Parse a sentence into constructions
     *
     * Main entry point for the CLN v3 parser.
     *
     * @param  string|array  $input  Sentence string or array of words
     * @return array Parse result with constructions and metadata
     */
    public function parse(string|array $input): array
    {
        // Convert string to words if needed
        $sentence = is_array($input) ? implode(' ', $input) : $input;

        // Reset stability tracking
        $this->stabilityChecker->reset();

        // Parse sentence to get word data
        $wordData = $this->inputParser->parseForL1Nodes($sentence);

        if (empty($wordData)) {
            return $this->emptyResult($sentence, 'No words parsed');
        }

        // Reset activation dynamics for new parse
        $this->activationDynamicsParser->reset();

        $allL2Nodes = [];
        $timestepsPerWord = $this->config['timesteps_per_word'] ?? 20;
        $pruneAfterWord = $this->config['prune_after_each_word'] ?? false;
        $extractAfterWord = $this->config['extract_after_each_word'] ?? false;
        $wordCompletions = []; // Track completions per word
        $lastActivationStats = []; // Track last activation stats

        $columns = [];

        // First level built from data
        foreach ($wordData as $idx => $data) {
            if ($data['pos'] == 'PUNCT') {
                continue;
            }
            $position = $data['position'];

            // 1. Add L1 node for this word
            //$columns[] = $this->graph->createPOSColumn($data);
            $this->graph->addData($data);

//            $constructions[] = $this->graph->addL1Node(
//                name: $data['word'],
//                position: $data['position'],
//                constructionType: 'literal',
//                features: $data['features']
//            );
        }
        // add eos
        $this->graph->addEOS(count($wordData));

        $this->activationDynamicsParser->propagateActivation();

        $loopResult = [];
        // Build result
        return $this->buildResult($sentence, $wordData, [], $loopResult);
    }

    public function parseStage1(string|array $input): array
    {
        // Convert string to words if needed
        $sentence = is_array($input) ? implode(' ', $input) : $input;

        // Reset stability tracking
        $this->stabilityChecker->reset();

        // Parse sentence to get word data
        $wordData = $this->inputParser->parseForL1Nodes($sentence);

        if (empty($wordData)) {
            return $this->emptyResult($sentence, 'No words parsed');
        }

        // Reset activation dynamics for new parse
        $this->activationDynamicsParser->reset();

        // Parsing in two stages:
        // 1. Building the whole estructure for the input
        // 2. Activation and propagation from each word

        $allL2Nodes = [];
        $timestepsPerWord = $this->config['timesteps_per_word'] ?? 20;
        $pruneAfterWord = $this->config['prune_after_each_word'] ?? false;
        $extractAfterWord = $this->config['extract_after_each_word'] ?? false;
        $wordCompletions = []; // Track completions per word
        $lastActivationStats = []; // Track last activation stats

        // === INCREMENTAL WORD-BY-WORD PROCESSING ===
        $columns = [];

        // First level built from data
        foreach ($wordData as $idx => $data) {
            if ($data['pos'] == 'PUNCT') {
                continue;
            }
            $position = $data['position'];

            // 1. Add L1 node for this word
            $columns[] = $this->graph->createPOSColumn($data);

//            $constructions[] = $this->graph->addL1Node(
//                name: $data['word'],
//                position: $data['position'],
//                constructionType: 'literal',
//                features: $data['features']
//            );
        }

        $this->parseStage1Level2($columns);

        $loopResult = [];
        // Build result
        return $this->buildResult($sentence, $wordData, [], $loopResult);
    }

    public function parseStage1Level2(array $currentColumns): array
    {
        if (empty($currentColumns)) {
            return [];
        }

        $nextColumns = $this->graph->createNextLevelColumns($currentColumns);
        return $this->parseStage1Level2($nextColumns);
    }

    /**
     * Parse incrementally word by word (RNT mode)
     *
     * Processes each word as it arrives, running dynamics and composition
     * after each word. This enables incremental parsing with the RNT pattern graph.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  string  $sentence  Input sentence
     * @return array Parse result with constructions and metadata
     */
//    private function parseIncremental(RuntimeGraph $graph, string $sentence): array
//    {
//        // Parse sentence to get word data
//        $wordData = $this->inputParser->parseForL1Nodes($sentence);
//
//        if (empty($wordData)) {
//            return $this->emptyResult($sentence, 'No words parsed');
//        }
//
//        // Reset activation dynamics for new parse
//        $this->activationDynamicsParser->reset();
//
//        // Parsing in two stages:
//        // 1. Building the whole estructure for the input
//        // 2. Activation and propagation from each word
//
//        $allL2Nodes = [];
//        $timestepsPerWord = $this->config['timesteps_per_word'] ?? 20;
//        $pruneAfterWord = $this->config['prune_after_each_word'] ?? false;
//        $extractAfterWord = $this->config['extract_after_each_word'] ?? false;
//        $wordCompletions = []; // Track completions per word
//        $lastActivationStats = []; // Track last activation stats
//
//        // === INCREMENTAL WORD-BY-WORD PROCESSING ===
//
//        // First level built from data
//        foreach ($wordData as $idx => $data) {
//            // 1. Add L1 node for this word
//            $l1Node = $graph->addL1Node(
//                position: $data['position'],
//                constructionType: 'literal',
//                features: $data['features']
//            );
//            //$l1Node->activation = 0.95; // High initial activation for input
//
//            // 2. Immediate composition attempt
////            $newL2Nodes = $this->compositor->composePairs($graph);
////            $allL2Nodes = array_merge($allL2Nodes, $newL2Nodes);
//
//            // 3. Establish competition for new nodes
////            if (! empty($newL2Nodes)) {
////                $this->competition->establishCompetition($graph);
////            }
//
//            // 4. Propagate activation through pattern graph
////            $lastActivationStats = $this->activationDynamicsParser->propagateActivation($graph);
//
//            // 4.5. Prune invalid SEQUENCER links after dynamics
//            // Some SEQUENCER inputs may have linked before HEAD arrived, making
//            // position validation impossible at link time. Now that dynamics are
//            // complete and all inputs have settled, we can validate and remove
//            // invalid links based on final HEAD positions.
////            if ($this->config['rnt_enabled'] ?? false) {
////                $this->compositor->pruneSequencerLinks($graph);
////            }
//
//            // 5. Detect completions after this word
////            $completions = $this->completionDetector->detectCompletions($graph);
////            if (! empty($completions)) {
////                $wordCompletions[$idx] = [
////                    'word' => $data['features']['value'] ?? "word_{$idx}",
////                    'position' => $data['position'],
////                    'completions' => count($completions),
////                ];
////            }
//
//            // 6. Optional extraction after each word (for real-time feedback)
////            if ($extractAfterWord) {
////                // Could log or emit partial results here
////            }
//
//            // 7. Optional pruning after each word (aggressive memory management)
////            if ($pruneAfterWord) {
////                $this->performPruning($graph);
////                $allL2Nodes = array_values($graph->getNodesByLevel('L2'));
////            }
//        }
//
//        // === FINAL PROCESSING LOOP ===
//
//        // from first L2 columns
//
////        foreach ($wordData as $idx => $data) {
////            // 1. Add L1 node for this word
////            $l1Node = $graph->addL1Node(
////                position: $data['position'],
////                constructionType: 'literal',
////                features: $data['features']
////            );
////            //$l1Node->activation = 0.95; // High initial activation for input
////
////            // 2. Immediate composition attempt
//////            $newL2Nodes = $this->compositor->composePairs($graph);
//////            $allL2Nodes = array_merge($allL2Nodes, $newL2Nodes);
////
////            // 3. Establish competition for new nodes
//////            if (! empty($newL2Nodes)) {
//////                $this->competition->establishCompetition($graph);
//////            }
////
////            // 4. Propagate activation through pattern graph
////            $lastActivationStats = $this->activationDynamicsParser->propagateActivation($graph);
////
////            // 4.5. Prune invalid SEQUENCER links after dynamics
////            // Some SEQUENCER inputs may have linked before HEAD arrived, making
////            // position validation impossible at link time. Now that dynamics are
////            // complete and all inputs have settled, we can validate and remove
////            // invalid links based on final HEAD positions.
////            if ($this->config['rnt_enabled'] ?? false) {
////                $this->compositor->pruneSequencerLinks($graph);
////            }
////
////            // 5. Detect completions after this word
////            $completions = $this->completionDetector->detectCompletions($graph);
////            if (! empty($completions)) {
////                $wordCompletions[$idx] = [
////                    'word' => $data['features']['value'] ?? "word_{$idx}",
////                    'position' => $data['position'],
////                    'completions' => count($completions),
////                ];
////            }
////
////            // 6. Optional extraction after each word (for real-time feedback)
////            if ($extractAfterWord) {
////                // Could log or emit partial results here
////            }
////
////            // 7. Optional pruning after each word (aggressive memory management)
////            if ($pruneAfterWord) {
////                $this->performPruning($graph);
////                $allL2Nodes = array_values($graph->getNodesByLevel('L2'));
////            }
////        }
//
//
//        $loopResult['timesteps'] = 0;
//        $loopResult['converged'] = true;
//        $loopResult['converged_at'] = 0;
//        $loopResult['simulated_time'] = 0;
//        $loopResult['total_pruned'] = 0;
//
//        // Continue dynamics until convergence
////        $loopResult = $this->processingLoop($graph, $allL2Nodes);
//
//        // Add incremental-specific metadata
//        $loopResult['incremental_mode'] = true;
//        $loopResult['words_processed'] = count($wordData);
//        $loopResult['timesteps_per_word'] = $timestepsPerWord;
//        $loopResult['word_completions'] = $wordCompletions;
//        $loopResult['activation_stats'] = $lastActivationStats;
//
//        // Extract final constructions
//  //      $constructions = $this->extractConstructions($graph);
//        $constructions = [];
//        // Build result
//        return $this->buildResult($sentence, $wordData, $constructions, $loopResult);
//    }

    public function getRuntimeGraph() : RuntimeGraph|LearnGraph
    {
        return $this->graph;
    }

    /**
     * Run dynamics for a specific number of timesteps
     *
     * Helper method for incremental parsing that runs neural dynamics
     * for a fixed number of timesteps without checking convergence.
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $l2Nodes  L2 nodes to update
     * @param  int  $steps  Number of timesteps to run
     */
    private function runDynamicsSteps(RuntimeGraph $graph, array &$l2Nodes, int $steps): void
    {
        $dt = $this->config['dt'];

        for ($t = 0; $t < $steps; $t++) {
            // Update all L2 nodes
            foreach ($l2Nodes as $l2Node) {
                $this->dynamics->updateNode($l2Node, $graph, $dt);
            }

            // Record state for stability tracking
            $this->stabilityChecker->recordState($graph);
        }
    }

    /**
     * Schedule input by creating L1 nodes
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  string  $sentence  Input sentence
     * @return array Word data
     */
    private function scheduleInput(RuntimeGraph $graph, string $sentence): array
    {
        $wordData = $this->inputParser->parseForL1Nodes($sentence);

        foreach ($wordData as $data) {
            $node = $graph->addL1Node(
                position: $data['position'],
                constructionType: 'literal',
                features: $data['features']
            );
            $node->activation = 0.95; // High initial activation for input
        }

        return $wordData;
    }

    /**
     * Main processing loop with dynamics, stability, and pruning
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  array  $l2Nodes  Initial L2 nodes
     * @return array Loop statistics
     */
    private function processingLoop(RuntimeGraph $graph, array &$l2Nodes): array
    {
        $dt = $this->config['dt'];
        $maxTimesteps = $this->config['max_timesteps'];
        $minTimesteps = $this->config['min_timesteps'];
        $convergenceCheckInterval = $this->config['convergence_check_interval'];
        $pruningInterval = $this->config['pruning_interval'];
        $enablePruning = $this->config['enable_pruning'];

        $convergedAt = null;
        $totalPruned = 0;
        $completionHistory = [];

        for ($t = 0; $t < $maxTimesteps; $t++) {
            // Propagate activation through pattern graph
            $this->activationDynamicsParser->propagateActivation($graph);

            // Record state for stability tracking
            $this->stabilityChecker->recordState($graph);

            // Detect completions (track over time)
            $completions = $this->completionDetector->detectCompletions($graph);
            if (! empty($completions)) {
                $completionHistory[$t] = count($completions);
            }

            // Periodic pruning
            if ($enablePruning && $t > 0 && $t % $pruningInterval === 0) {
                $pruneStats = $this->performPruning($graph);
                $totalPruned += $pruneStats['total_pruned'];

                // Update L2 nodes array after pruning
                $l2Nodes = array_values($graph->getNodesByLevel('L2'));
            }

            // Check convergence (after minimum timesteps)
            if ($t >= $minTimesteps &&
                $t % $convergenceCheckInterval === 0 &&
                $this->stabilityChecker->hasSufficientData()) {

                if ($this->stabilityChecker->hasConverged()) {
                    $convergedAt = $t;
                    break;
                }
            }
        }

        return [
            'timesteps' => $convergedAt ?? $maxTimesteps,
            'converged' => $convergedAt !== null,
            'converged_at' => $convergedAt,
            'simulated_time' => ($convergedAt ?? $maxTimesteps) * $dt,
            'total_pruned' => $totalPruned,
            'stability_metrics' => $this->stabilityChecker->getMetrics(),
            'completion_history' => $completionHistory,
        ];
    }

    /**
     * Perform pruning based on configured strategy
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Pruning statistics
     */
    private function performPruning(RuntimeGraph $graph): array
    {
        $strategy = $this->config['pruning_strategy'];

        return match ($strategy) {
            'simple' => [
                'total_pruned' => $this->competition->pruneLosers(
                    $graph,
                    $this->config['pruning_absolute_threshold']
                ),
            ],
            'smart' => $this->competition->pruneCompetitionLosers(
                $graph,
                absoluteThreshold: $this->config['pruning_absolute_threshold'],
                competitiveGap: $this->config['pruning_competitive_gap'],
                preserveCompleted: $this->config['preserve_completed']
            ),
            'aggressive' => $this->competition->pruneAllLosers(
                $graph,
                preserveCompleted: $this->config['preserve_completed']
            ),
            default => ['total_pruned' => 0],
        };
    }

    /**
     * Extract final constructions from graph
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @return array Array of construction information
     */
    private function extractConstructions(RuntimeGraph $graph): array
    {
        $l2Nodes = $graph->getNodesByLevel('L2');
        $constructions = [];
        $extractWinnersOnly = $this->config['extract_winners_only'];
        $minActivation = $this->config['min_activation_threshold'];

        // Get all completed constructions
        $completedNodes = $this->completionDetector->getCompletedConstructions($graph);

        foreach ($completedNodes as $node) {
            // Filter by activation threshold
            if ($node->activation < $minActivation) {
                continue;
            }

            // Filter by winner status if configured
            if ($extractWinnersOnly) {
                $competitors = $this->competition->getCompetitors($graph, $node->span);
                if (count($competitors) > 1 && $competitors[0]->id !== $node->id) {
                    continue; // Not the winner, skip
                }
            }

            $constructions[] = [
                'node_id' => $node->id,
                'construction_type' => $node->construction_type,
                'span' => $node->span,
                'activation' => $node->activation,
                'pattern_id' => $node->bindings['pattern_id'] ?? null,
                'is_winner' => $this->isWinner($graph, $node),
                'features' => $node->features,
            ];
        }

        // Sort by span position, then by activation
        usort($constructions, function ($a, $b) {
            if ($a['span'][0] !== $b['span'][0]) {
                return $a['span'][0] <=> $b['span'][0];
            }

            return $b['activation'] <=> $a['activation'];
        });

        return $constructions;
    }

    /**
     * Check if a node is a winner in its competitive group
     *
     * @param  RuntimeGraph  $graph  Runtime graph
     * @param  mixed  $node  Node to check
     * @return bool True if winner
     */
    private function isWinner(RuntimeGraph $graph, $node): bool
    {
        $competitors = $this->competition->getCompetitors($graph, $node->span);

        return count($competitors) === 0 || $competitors[0]->id === $node->id;
    }

    /**
     * Build empty result when parsing fails
     *
     * @param  string  $sentence  Input sentence
     * @param  string  $reason  Failure reason
     * @return array Empty result
     */
    private function emptyResult(string $sentence, string $reason): array
    {
        return [
            'sentence' => $sentence,
            'constructions' => [],
            'metadata' => [
                'success' => false,
                'reason' => $reason,
                'words' => 0,
                'timesteps' => 0,
                'converged' => false,
            ],
        ];
    }

    /**
     * Build final parse result
     *
     * @param  string  $sentence  Input sentence
     * @param  array  $wordData  Word data
     * @param  array  $constructions  Extracted constructions
     * @param  array  $loopResult  Processing loop result
     * @return array Complete parse result
     */
    private function buildResult(
        string $sentence,
        array $wordData,
        array $constructions,
        array $loopResult
    ): array {
        return [
            'sentence' => $sentence,
            'constructions' => $constructions,
            'words' => $wordData,
            'activation_stats' => $loopResult['activation_stats'] ?? [],
            'metadata' => [
                'success' => true,
                'words' => count($wordData),
                'constructions_found' => count($constructions),
//                'timesteps' => $loopResult['timesteps'],
//                'converged' => $loopResult['converged'],
//                'converged_at' => $loopResult['converged_at'],
//                'simulated_time' => $loopResult['simulated_time'],
//                'total_pruned' => $loopResult['total_pruned'],
                'stability' => [
//                    'max_change' => $loopResult['stability_metrics']['max_change'],
//                    'avg_change' => $loopResult['stability_metrics']['avg_change'],
//                    'oscillating_nodes' => count($loopResult['stability_metrics']['oscillating_nodes']),
                ],
                'config' => [
                    'dt' => $this->config['dt'],
                    'max_timesteps' => $this->config['max_timesteps'],
                    'pruning_strategy' => $this->config['pruning_strategy'],
                ],
            ],
        ];
    }

    /**
     * Get parser configuration
     *
     * @return array Current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update parser configuration
     *
     * @param  array  $config  Configuration updates
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        // Update stability checker if relevant config changed
        if (isset($config['convergence_threshold']) ||
            isset($config['oscillation_window']) ||
            isset($config['min_stable_steps'])) {

            $this->stabilityChecker = new StabilityChecker(
                convergenceThreshold: $this->config['convergence_threshold'],
                oscillationWindow: $this->config['oscillation_window'],
                minStableSteps: $this->config['min_stable_steps']
            );
        }
    }

    /**
     * Get default configuration from config file
     *
     * Loads configuration from config/cln.php and flattens the nested
     * structure into the format expected by the parser.
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        $pcConfig = config('cln.pc_parser', []);

        return [
            // Dynamics
            'dt' => $pcConfig['dynamics']['dt'] ?? 0.01,
            'max_timesteps' => $pcConfig['dynamics']['max_timesteps'] ?? 500,
            'min_timesteps' => $pcConfig['dynamics']['min_timesteps'] ?? 10,

            // Convergence
            'convergence_threshold' => $pcConfig['convergence']['threshold'] ?? 0.001,
            'min_stable_steps' => $pcConfig['convergence']['min_stable_steps'] ?? 5,
            'oscillation_window' => $pcConfig['convergence']['oscillation_window'] ?? 10,
            'convergence_check_interval' => $pcConfig['convergence']['check_interval'] ?? 5,

            // Pruning
            'enable_pruning' => $pcConfig['pruning']['enabled'] ?? true,
            'pruning_interval' => $pcConfig['pruning']['interval'] ?? 10,
            'pruning_strategy' => $pcConfig['pruning']['strategy'] ?? 'smart',
            'pruning_absolute_threshold' => $pcConfig['pruning']['absolute_threshold'] ?? 0.05,
            'pruning_competitive_gap' => $pcConfig['pruning']['competitive_gap'] ?? 0.3,
            'preserve_completed' => $pcConfig['pruning']['preserve_completed'] ?? true,

            // Input scheduling
            'input_mode' => $pcConfig['input']['mode'] ?? 'all_at_once',

            // Output
            'extract_winners_only' => $pcConfig['output']['extract_winners_only'] ?? true,
            'min_activation_threshold' => $pcConfig['output']['min_activation_threshold'] ?? 0.5,

            // RNT Pattern Graph
            'rnt_enabled' => $pcConfig['rnt']['enabled'] ?? false,
            'rnt_cache_queries' => $pcConfig['rnt']['cache_queries'] ?? true,
            'rnt_warmup_cache' => $pcConfig['rnt']['warmup_cache'] ?? true,

            // Incremental Parsing
            'incremental_enabled' => $pcConfig['incremental']['enabled'] ?? false,
            'timesteps_per_word' => $pcConfig['incremental']['timesteps_per_word'] ?? 20,
            'prune_after_each_word' => $pcConfig['incremental']['prune_after_each_word'] ?? false,
            'extract_after_each_word' => $pcConfig['incremental']['extract_after_each_word'] ?? false,
        ];
    }

    /**
     * Export graph snapshot to DOT file
     *
     * @param  RuntimeGraph  $graph  Runtime graph to export
     * @param  string  $filepath  Output file path
     * @param  string  $title  Graph title
     * @param  array  $metadata  Optional metadata
     */
    private function exportGraphSnapshot(
        RuntimeGraph $graph,
        string $filepath,
        string $title,
        array $metadata = []
    ): void {
        if ($this->exporter === null) {
            return;
        }

        $dot = $this->exporter->exportToDot($graph, $title, $metadata);
        $this->exporter->saveDotToFile($dot, $filepath);
    }

    public function learn(string|array $input): array
    {
        // Convert string to words if needed
        $sentence = is_array($input) ? implode(' ', $input) : $input;

        // Reset stability tracking
        $this->stabilityChecker->reset();

        // Parse sentence to get word data
        $wordData = $this->inputParser->parseForL1Nodes($sentence);

        if (empty($wordData)) {
            return $this->emptyResult($sentence, 'No words parsed');
        }

        // Reset activation dynamics for new parse
//        $this->activationDynamicsParser->reset();

        // Parsing in two stages:
        // 1. Building the whole structure for the input
        // 2. Activation and propagation from each word

        $allL2Nodes = [];
        $timestepsPerWord = $this->config['timesteps_per_word'] ?? 20;
        $pruneAfterWord = $this->config['prune_after_each_word'] ?? false;
        $extractAfterWord = $this->config['extract_after_each_word'] ?? false;
        $wordCompletions = []; // Track completions per word
        $lastActivationStats = []; // Track last activation stats

        // === INCREMENTAL WORD-BY-WORD PROCESSING ===
        $columns = [];

        // First level built from data
        foreach ($wordData as $idx => $data) {
            if ($data['pos'] == 'PUNCT') {
                continue;
            }
            $position = $data['position'];

            // 1. Add L1 node for this word
            $columns[] = $this->graph->createPOSColumn($data);


//            $constructions[] = $this->graph->addL1Node(
//                name: $data['word'],
//                position: $data['position'],
//                constructionType: 'literal',
//                features: $data['features']
//            );
        }

        $this->learnLevel2($columns);

        $this->activationDynamicsLearning->processSentence($wordData);

        $loopResult = [];
        // Build result
        return $this->buildResult($sentence, $wordData, [], $loopResult);
    }

    public function learnLevel2(array $currentColumns): array
    {
        if (empty($currentColumns)) {
            return [];
        }

        $nextColumns = $this->graph->createNextLevelColumns($currentColumns);
        //return $this->learnLevel2($nextColumns);
        return [];
    }




}

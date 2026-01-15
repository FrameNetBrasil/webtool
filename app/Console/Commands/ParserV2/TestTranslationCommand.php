<?php

namespace App\Console\Commands\ParserV2;

use App\Enums\Parser\ClausalCE;
use App\Models\Parser\PhrasalCENode;
use App\Repositories\Parser\MWE;
use App\Services\Parser\PhraseAssemblyService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;

/**
 * Test Stage 2 (Translation) of the three-stage parser
 *
 * Takes Stage 1 output (PhrasalCENodes) and applies Stage 2 transformations:
 * - Head disambiguation
 * - Feature-based grouping
 * - Clausal CE classification
 */
class TestTranslationCommand extends Command
{
    protected $signature = 'parser:test-translation
                            {file : Path to input file with sentences (one per line)}
                            {--language=pt : Language code (pt, en)}
                            {--grammar= : Grammar graph ID for MWE detection}
                            {--output= : Output file for results (optional)}
                            {--format=table : Output format (table, json, csv)}
                            {--show-scores : Show compatibility scores}
                            {--verbose-features : Show all features for each token}
                            {--limit= : Limit number of sentences to process}
                            {--skip= : Skip first N sentences}';

    protected $description = 'Test Stage 2 (Translation): Transform PhrasalCE to ClausalCE classifications';

    private TrankitService $trankit;

    private PhraseAssemblyService $assemblyService;

    private ?int $idGrammarGraph = null;

    private array $stats = [
        'sentences_processed' => 0,
        'phrasal_nodes' => 0,
        'clausal_nodes' => 0,
        'parse_errors' => 0,
        'clausal_ce_distribution' => [],
        'head_disambiguations' => 0,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $language = $this->option('language');
        $outputFile = $this->option('output');
        $format = $this->option('format');
        $showScores = $this->option('show-scores');
        $verboseFeatures = $this->option('verbose-features');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $skip = $this->option('skip') ? (int) $this->option('skip') : 0;

        // Grammar graph for MWE detection in Stage 1
        $this->idGrammarGraph = $this->option('grammar') ? (int) $this->option('grammar') : null;

        // Validate input file
        if (! file_exists($filePath)) {
            $this->error("Input file not found: {$filePath}");

            return Command::FAILURE;
        }

        $this->displayConfiguration($filePath, $language, $format, $limit, $skip);

        // Initialize services
        $this->initServices();

        // Read sentences from file
        $sentences = $this->readSentences($filePath, $limit, $skip);

        if (empty($sentences)) {
            $this->warn('No sentences found in input file.');

            return Command::SUCCESS;
        }

        $this->info("Processing {$this->stats['sentences_processed']} sentences...");
        $this->newLine();

        // Process each sentence
        $results = [];
        $progressBar = $this->output->createProgressBar(count($sentences));
        $progressBar->start();

        foreach ($sentences as $index => $sentence) {
            $result = $this->processSentence($sentence, $index + 1, $language);
            if ($result) {
                $results[] = $result;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($results, $format, $verboseFeatures, $showScores);

        // Display statistics
        $this->displayStatistics();

        // Save output if requested
        if ($outputFile) {
            $this->saveOutput($results, $outputFile, $format);
        }

        return Command::SUCCESS;
    }

    private function displayConfiguration(string $filePath, string $language, string $format, ?int $limit, int $skip): void
    {
        $this->info('Stage 2 (Translation) Test');
        $this->line(str_repeat('─', 60));
        $this->line('Configuration:');
        $this->line("  • Input file: {$filePath}");
        $this->line("  • Language: {$language}");
        $this->line("  • Output format: {$format}");
        $this->line('  • Limit: '.($limit ?: 'No limit'));
        $this->line("  • Skip: {$skip}");

        if ($this->idGrammarGraph) {
            $this->line("  • Grammar Graph: ID {$this->idGrammarGraph}");
        } else {
            $this->line('  • Grammar Graph: <fg=yellow>None</> (use --grammar=ID for MWE detection)');
        }

        $this->newLine();
    }

    private function initServices(): void
    {
        // Initialize Trankit for Stage 1
        $this->trankit = new TrankitService;
        $trankitUrl = config('parser.trankit.url');
        $this->trankit->init($trankitUrl);
        $this->info("Trankit service initialized at: {$trankitUrl}");

        // Initialize PhraseAssemblyService for Stage 2
        $this->assemblyService = app(PhraseAssemblyService::class);
        $this->info('PhraseAssemblyService initialized');

        if ($this->idGrammarGraph) {
            $mwes = MWE::listByGrammar($this->idGrammarGraph);
            $this->info('Grammar Graph loaded with '.count($mwes).' MWEs');
        }
    }

    private function readSentences(string $filePath, ?int $limit, int $skip): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $sentences = [];

        foreach ($lines as $index => $line) {
            // Skip comment lines
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Skip empty lines
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Apply skip
            if ($skip > 0) {
                $skip--;

                continue;
            }

            $sentences[] = $line;

            // Apply limit
            if ($limit && count($sentences) >= $limit) {
                break;
            }
        }

        $this->stats['sentences_processed'] = count($sentences);

        return $sentences;
    }

    private function processSentence(string $sentence, int $index, string $language): ?array
    {
        try {
            // Get language ID
            $idLanguage = config('parser.languageMap')[$language] ?? 1;

            // STAGE 1: Run transcription with MWE handling (same as TestTranscriptionCommand)

            // STEP 1: Parse with preserved contractions for MWE detection
            // This is crucial - we need "pelo" to stay as "pelo" (not "por" + "o")
            // so we can match MWEs like "pelo menos" in the database
            $textResult = $this->trankit->getUDTrankitText($sentence, $idLanguage);
            $textTokens = $textResult->udpipe ?? [];

            if (empty($textTokens)) {
                $this->stats['parse_errors']++;

                return null;
            }

            // Build preliminary nodes from text tokens (with preserved contractions)
            $textNodes = [];
            foreach ($textTokens as $token) {
                $textNodes[] = PhrasalCENode::fromUDToken($token);
            }

            // Detect MWEs if grammar is available
            $mweCandidates = [];
            $detectedMWEs = [];

            if ($this->idGrammarGraph) {
                [$mweCandidates, $detectedMWEs] = $this->detectMWEs($textNodes);
            }

            // STEP 2: Parse with expanded contractions for full syntactic analysis
            // Now we get the complete dependency tree with "pelo" → "por" + "o"
            $udResult = $this->trankit->getUDTrankit($sentence, $idLanguage);
            $tokens = $udResult->udpipe ?? [];

            if (empty($tokens)) {
                $this->stats['parse_errors']++;

                return null;
            }

            // Build PhrasalCENodes from expanded parse
            $phrasalNodes = [];
            foreach ($tokens as $token) {
                $node = PhrasalCENode::fromUDToken($token);
                $phrasalNodes[] = $node;
                $this->stats['phrasal_nodes']++;
            }

            // STEP 3: Merge MWE information with expanded parse
            // Apply MWE assembly - this will replace expanded tokens with MWE nodes
            if (! empty($detectedMWEs)) {
                $phrasalNodes = $this->assembleMWEsWithExpanded($phrasalNodes, $detectedMWEs, $textNodes, $language);
            }

            // STAGE 2: Apply translation (Head disambiguation + grouping)
            $clausalNodes = $this->assemblyService->assemble($phrasalNodes, $language);

            // Update statistics
            $this->stats['clausal_nodes'] += count($clausalNodes);
            foreach ($clausalNodes as $clausalNode) {
                $ceValue = $clausalNode->clausalCE->value;
                $this->stats['clausal_ce_distribution'][$ceValue] = ($this->stats['clausal_ce_distribution'][$ceValue] ?? 0) + 1;
            }

            return [
                'index' => $index,
                'sentence' => $sentence,
                'phrasalNodes' => $phrasalNodes,
                'clausalNodes' => $clausalNodes,
                'mweCandidates' => $mweCandidates,
                'detectedMWEs' => $detectedMWEs,
            ];
        } catch (\Exception $e) {
            $this->stats['parse_errors']++;
            $this->warn("Error processing sentence {$index}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Detect MWEs in a sequence of nodes using two-phase detection
     *
     * Phase 1: Anchored patterns (simple format + extended format with anchor words)
     * Phase 2: Fully variable patterns (no fixed word anchors)
     *
     * @return array [candidates, detected]
     */
    private function detectMWEs(array $nodes): array
    {
        $candidates = [];
        $detected = [];

        // Reindex nodes by array position for sequential access
        $nodesByPosition = array_values($nodes);

        // Phase 1: Anchored patterns (simple format uses firstWord, extended uses anchorWord)
        foreach ($nodesByPosition as $nodePosition => $node) {
            // Get simple-format MWEs starting with this word
            $simpleMWEs = MWE::getStartingWith($this->idGrammarGraph, strtolower($node->word));

            // Get extended-format MWEs anchored by this word
            $extendedMWEs = MWE::getByAnchorWord($this->idGrammarGraph, strtolower($node->word));

            // Process all anchored MWEs
            foreach (array_merge($simpleMWEs, $extendedMWEs) as $mwe) {
                $result = $this->tryMatchMWE($mwe, $nodesByPosition, $nodePosition);
                if ($result !== null) {
                    if ($result['complete']) {
                        $detected[] = $result;
                    } else {
                        $candidates[] = $result;
                    }
                }
            }
        }

        // Phase 2: Fully variable patterns (no fixed word anchor)
        $variableMWEs = MWE::getFullyVariable($this->idGrammarGraph);
        foreach ($variableMWEs as $mwe) {
            foreach ($nodesByPosition as $nodePosition => $node) {
                $result = $this->tryMatchMWE($mwe, $nodesByPosition, $nodePosition);
                if ($result !== null && $result['complete']) {
                    $detected[] = $result;
                }
            }
        }

        return [$candidates, $detected];
    }

    /**
     * Try to match an MWE pattern starting at a given position.
     *
     * Handles both simple (string array) and extended (type/value array) component formats.
     *
     * @param  object  $mwe  The MWE definition from database
     * @param  array  $nodesByPosition  Nodes indexed by position
     * @param  int  $anchorPosition  Position where anchor word was found
     * @return array|null Candidate array or null if no match possible
     */
    private function tryMatchMWE(object $mwe, array $nodesByPosition, int $anchorPosition): ?array
    {
        $components = MWE::getParsedComponents($mwe);
        $threshold = count($components);

        // Calculate pattern start position based on anchor offset
        $anchorOffset = $mwe->anchorPosition ?? 0;
        $patternStartPosition = $anchorPosition - $anchorOffset;

        if ($patternStartPosition < 0) {
            return null; // Pattern would start before sentence
        }

        // Check if we have enough nodes for this pattern
        if ($patternStartPosition + $threshold > count($nodesByPosition)) {
            return null;
        }

        $startNode = $nodesByPosition[$patternStartPosition] ?? null;
        if ($startNode === null) {
            return null;
        }

        $candidate = [
            'idMWE' => $mwe->idMWE,
            'phrase' => $mwe->phrase,
            'components' => $components,
            'threshold' => $threshold,
            'startIndex' => $startNode->index,
            'activation' => 0,
            'matchedWords' => [],
        ];

        // Match each component
        $currentPosition = $patternStartPosition;
        foreach ($components as $i => $component) {
            if (! isset($nodesByPosition[$currentPosition])) {
                break;
            }

            $node = $nodesByPosition[$currentPosition];

            if (MWE::componentMatchesToken($component, $node)) {
                $candidate['activation']++;
                $candidate['matchedWords'][] = $node->word;
                $candidate['endIndex'] = $node->index;
                $currentPosition++;
            } else {
                break;
            }
        }

        if (! isset($candidate['endIndex'])) {
            $candidate['endIndex'] = $candidate['startIndex'];
        }

        // Check if MWE is complete
        if ($candidate['activation'] >= $threshold) {
            $candidate['complete'] = true;
        } else {
            $candidate['complete'] = false;
        }

        return $candidate;
    }

    /**
     * Assemble MWEs by merging information from text parse with expanded parse
     *
     * @param  array  $expandedNodes  Nodes from expanded parse
     * @param  array  $detectedMWEs  MWEs detected from text parse
     * @param  array  $textNodes  Original nodes with preserved contractions
     * @param  string  $language  Language code
     * @return array Modified nodes with MWEs assembled
     */
    private function assembleMWEsWithExpanded(array $expandedNodes, array $detectedMWEs, array $textNodes, string $language): array
    {
        // Get language ID for POS lookup
        $idLanguage = config('parser.languageMap')[$language] ?? 1;

        // Sort MWEs by start index (descending) to process from end to avoid index shifting
        usort($detectedMWEs, fn ($a, $b) => $b['startIndex'] <=> $a['startIndex']);

        foreach ($detectedMWEs as $mwe) {
            // Find the corresponding nodes in textNodes
            $textComponentNodes = [];
            foreach ($textNodes as $node) {
                if ($node->index >= $mwe['startIndex'] && $node->index <= $mwe['endIndex']) {
                    $textComponentNodes[] = $node;
                }
            }

            if (empty($textComponentNodes)) {
                continue;
            }

            // Find all expanded nodes that fall within this range
            $expandedComponentNodes = [];
            $startArrayIdx = null;
            $endArrayIdx = null;

            foreach ($expandedNodes as $arrayIdx => $node) {
                if ($node->index >= $mwe['startIndex'] && $node->index <= $mwe['endIndex']) {
                    if ($startArrayIdx === null) {
                        $startArrayIdx = $arrayIdx;
                    }
                    $endArrayIdx = $arrayIdx;
                    $expandedComponentNodes[] = $node;
                }
            }

            if ($startArrayIdx !== null && ! empty($expandedComponentNodes)) {
                // Get POS from view_lemma_pos for this MWE
                $mwePos = MWE::getPOS($mwe['phrase'], $idLanguage);

                // Create MWE node from the expanded components
                $mweNode = PhrasalCENode::fromMWEComponents(
                    $expandedComponentNodes,
                    count($textComponentNodes),
                    $mwePos
                );

                // Set the original MWE phrase as the word
                $mweNode->word = $mwe['phrase'];

                // Replace the expanded nodes with the MWE node
                array_splice($expandedNodes, $startArrayIdx, count($expandedComponentNodes), [$mweNode]);
            }
        }

        return $expandedNodes;
    }

    private function displayResults(array $results, string $format, bool $verboseFeatures, bool $showScores): void
    {
        if ($format === 'table') {
            $this->displayTableFormat($results, $verboseFeatures, $showScores);
        } elseif ($format === 'json') {
            $this->displayJsonFormat($results);
        } elseif ($format === 'csv') {
            $this->displayCsvFormat($results);
        }
    }

    private function displayTableFormat(array $results, bool $verboseFeatures, bool $showScores): void
    {
        foreach ($results as $result) {
            $this->info("Sentence {$result['index']}: {$result['sentence']}");
            $this->line(str_repeat('─', 60));

            // Display Stage 1 Output (PhrasalCE)
            $this->line('<fg=cyan>Stage 1 Output (Phrasal CEs):</>');
            $phrasalData = [];
            foreach ($result['phrasalNodes'] as $node) {
                $features = $node->getLexicalFeatures();
                $featureStr = $verboseFeatures
                    ? json_encode($features, JSON_UNESCAPED_UNICODE)
                    : $this->formatKeyFeatures($features);

                $phrasalData[] = [
                    'Index' => $node->index,
                    'Word' => $node->word,
                    'POS' => $node->pos,
                    'PhrasalCE' => $node->phrasalCE->value,
                    'Features' => $featureStr,
                ];
            }
            $this->table(
                ['Index', 'Word', 'POS', 'PhrasalCE', 'Features'],
                $phrasalData
            );

            $this->newLine();

            // Display Stage 2 Output (ClausalCE)
            $this->line('<fg=green>Stage 2 Output (Clausal CEs):</>');
            $clausalData = [];
            foreach ($result['clausalNodes'] as $clausalNode) {
                $features = $clausalNode->phrasalNode->getLexicalFeatures();
                $featureStr = $this->formatKeyFeatures($features);

                $clausalData[] = [
                    'Word' => $clausalNode->getWord(),
                    'PhrasalCE' => $clausalNode->phrasalNode->phrasalCE->value,
                    'ClausalCE' => $clausalNode->clausalCE->value,
                    'Features' => $featureStr,
                ];
            }
            $this->table(
                ['Word', 'PhrasalCE', 'ClausalCE', 'Features'],
                $clausalData
            );

            $this->newLine(2);
        }
    }

    private function formatKeyFeatures(array $features): string
    {
        $key = ['Gender', 'Number', 'Person', 'Case', 'VerbForm', 'PronType', 'Poss'];
        $parts = [];

        foreach ($key as $feature) {
            if (isset($features[$feature])) {
                $parts[] = "{$feature}={$features[$feature]}";
            }
        }

        return implode(', ', $parts);
    }

    private function displayJsonFormat(array $results): void
    {
        $output = [];
        foreach ($results as $result) {
            $output[] = [
                'sentence' => $result['sentence'],
                'phrasalNodes' => array_map(fn ($n) => $n->toArray(), $result['phrasalNodes']),
                'clausalNodes' => array_map(fn ($n) => $n->toArray(), $result['clausalNodes']),
            ];
        }

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function displayCsvFormat(array $results): void
    {
        // CSV header
        $this->line('Sentence,Index,Word,POS,PhrasalCE,ClausalCE,Gender,Number,VerbForm,PronType');

        foreach ($results as $result) {
            foreach ($result['clausalNodes'] as $clausalNode) {
                $features = $clausalNode->phrasalNode->getLexicalFeatures();

                $row = [
                    $result['sentence'],
                    $clausalNode->getIndex(),
                    $clausalNode->getWord(),
                    $clausalNode->phrasalNode->pos,
                    $clausalNode->phrasalNode->phrasalCE->value,
                    $clausalNode->clausalCE->value,
                    $features['Gender'] ?? '',
                    $features['Number'] ?? '',
                    $features['VerbForm'] ?? '',
                    $features['PronType'] ?? '',
                ];

                $this->line(implode(',', array_map(fn ($v) => '"'.$v.'"', $row)));
            }
        }
    }

    private function displayStatistics(): void
    {
        $this->info('Statistics:');
        $this->line(str_repeat('─', 60));
        $this->line("Sentences processed: {$this->stats['sentences_processed']}");
        $this->line("Parse errors: {$this->stats['parse_errors']}");
        $this->line("Phrasal nodes (Stage 1): {$this->stats['phrasal_nodes']}");
        $this->line("Clausal nodes (Stage 2): {$this->stats['clausal_nodes']}");
        $this->newLine();

        if (! empty($this->stats['clausal_ce_distribution'])) {
            $this->line('Clausal CE Distribution:');
            arsort($this->stats['clausal_ce_distribution']);
            foreach ($this->stats['clausal_ce_distribution'] as $ce => $count) {
                $pct = round($count / $this->stats['clausal_nodes'] * 100, 1);
                $this->line("  • {$ce}: {$count} ({$pct}%)");
            }
        }
    }

    private function saveOutput(array $results, string $outputFile, string $format): void
    {
        $content = '';

        if ($format === 'json') {
            $output = [];
            foreach ($results as $result) {
                $output[] = [
                    'sentence' => $result['sentence'],
                    'phrasalNodes' => array_map(fn ($n) => $n->toArray(), $result['phrasalNodes']),
                    'clausalNodes' => array_map(fn ($n) => $n->toArray(), $result['clausalNodes']),
                ];
            }
            $content = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'csv') {
            $lines = ['Sentence,Index,Word,POS,PhrasalCE,ClausalCE,Gender,Number,VerbForm,PronType'];

            foreach ($results as $result) {
                foreach ($result['clausalNodes'] as $clausalNode) {
                    $features = $clausalNode->phrasalNode->getLexicalFeatures();

                    $row = [
                        $result['sentence'],
                        $clausalNode->getIndex(),
                        $clausalNode->getWord(),
                        $clausalNode->phrasalNode->pos,
                        $clausalNode->phrasalNode->phrasalCE->value,
                        $clausalNode->clausalCE->value,
                        $features['Gender'] ?? '',
                        $features['Number'] ?? '',
                        $features['VerbForm'] ?? '',
                        $features['PronType'] ?? '',
                    ];

                    $lines[] = implode(',', array_map(fn ($v) => '"'.$v.'"', $row));
                }
            }

            $content = implode("\n", $lines);
        }

        file_put_contents($outputFile, $content);
        $this->info("Output saved to: {$outputFile}");
    }
}

<?php

namespace App\Console\Commands\ParserV2;

use App\Enums\Parser\PhrasalCE;
use App\Models\Parser\PhrasalCENode;
use App\Repositories\Parser\MWE;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;

/**
 * Test Stage 1 (Transcription) of the three-stage parser
 *
 * Reads sentences from a text file and applies the Transcription stage
 * to generate PhrasalCENodes for debugging and improvement.
 *
 * With --grammar option, integrates with grammar graph for:
 * - MWE (Multi-Word Expression) detection and assembly
 */
class TestTranscriptionCommand extends Command
{
    protected $signature = 'parser:test-transcription
                            {file : Path to input file with sentences (one per line)}
                            {--language=pt : Language code (pt, en)}
                            {--grammar= : Grammar graph ID for MWE detection}
                            {--output= : Output file for results (optional)}
                            {--format=table : Output format (table, json, csv)}
                            {--verbose-features : Show all features for each token}
                            {--show-mwe-candidates : Show MWE candidates even if not completed}
                            {--limit= : Limit number of sentences to process}
                            {--skip= : Skip first N sentences}';

    protected $description = 'Test Stage 1 (Transcription): Parse sentences and generate PhrasalCE classifications';

    private TrankitService $trankit;

    private ?int $idGrammarGraph = null;

    private array $stats = [
        'sentences_processed' => 0,
        'tokens_processed' => 0,
        'parse_errors' => 0,
        'ce_distribution' => [],
        'pos_distribution' => [],
        'mwes_detected' => 0,
        'mwe_candidates' => 0,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $language = $this->option('language');
        $outputFile = $this->option('output');
        $format = $this->option('format');
        $verboseFeatures = $this->option('verbose-features');
        $showMweCandidates = $this->option('show-mwe-candidates');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $skip = $this->option('skip') ? (int) $this->option('skip') : 0;

        // Grammar graph for MWE detection
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
        $this->displayResults($results, $format, $verboseFeatures);

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
        $this->info('Stage 1 (Transcription) Test');
        $this->line(str_repeat('─', 60));
        $this->line('Configuration:');
        $this->line("  • Input file: {$filePath}");
        $this->line("  • Language: {$language}");
        $this->line("  • Output format: {$format}");
        $this->line('  • Limit: '.($limit ?: 'No limit'));
        $this->line("  • Skip: {$skip}");

        if ($this->idGrammarGraph) {
            $this->line("  • Grammar Graph: ID {$this->idGrammarGraph}");
            $this->line('  • Features: MWE Detection');
        } else {
            $this->line('  • Grammar Graph: <fg=yellow>None</> (use --grammar=ID for MWE detection)');
        }

        $this->newLine();
    }

    private function initServices(): void
    {
        // Initialize Trankit
        $this->trankit = new TrankitService;
        $trankitUrl = config('parser.trankit.url');
        $this->trankit->init($trankitUrl);
        $this->info("Trankit service initialized at: {$trankitUrl}");

        // Count MWEs in the grammar if provided
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
                $this->stats['mwe_candidates'] += count($mweCandidates);
                $this->stats['mwes_detected'] += count($detectedMWEs);
            }

            // STEP 2: Parse with expanded contractions for full syntactic analysis
            // Now we get the complete dependency tree with "pelo" → "por" + "o"
            $udResult = $this->trankit->getUDTrankit($sentence, $idLanguage);
            $tokens = $udResult->udpipe ?? [];

            if (empty($tokens)) {
                $this->stats['parse_errors']++;

                return null;
            }

            // Build final PhrasalCENodes from expanded parse
            $nodes = [];

            foreach ($tokens as $token) {
                $node = PhrasalCENode::fromUDToken($token);
                $nodes[] = $node;

                // Update statistics
                $this->stats['tokens_processed']++;
                $ceValue = $node->phrasalCE->value;
                $this->stats['ce_distribution'][$ceValue] = ($this->stats['ce_distribution'][$ceValue] ?? 0) + 1;
                $this->stats['pos_distribution'][$node->pos] = ($this->stats['pos_distribution'][$node->pos] ?? 0) + 1;
            }

            // STEP 3: Merge MWE information with expanded parse
            // Apply MWE assembly - this will replace expanded tokens with MWE nodes
            if (! empty($detectedMWEs)) {
                $nodes = $this->assembleMWEsWithExpanded($nodes, $detectedMWEs, $textNodes, $language);
            }

            return [
                'index' => $index,
                'sentence' => $sentence,
                'tokens' => $tokens,
                'nodes' => $nodes,
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
     * NOTE: When using preserved contractions (getUDTrankitText), node indices
     * may not be sequential. For example: "pelo menos" has indices 4, 6 (not 4, 5)
     * because the contraction "pelo" uses ID 4, skipping the expanded IDs.
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
     * Find a node by its index
     */
    private function findNodeByIndex(array $nodes, int $index): ?PhrasalCENode
    {
        foreach ($nodes as $node) {
            if ($node->index === $index) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Assemble MWEs by replacing individual nodes with combined MWE nodes
     */
    private function assembleMWEs(array $nodes, array $detectedMWEs, string $language): array
    {
        // Get language ID for POS lookup
        $idLanguage = config('parser.languageMap')[$language] ?? 1;

        // Sort MWEs by start index (descending) to process from end to avoid index shifting
        usort($detectedMWEs, fn ($a, $b) => $b['startIndex'] <=> $a['startIndex']);

        foreach ($detectedMWEs as $mwe) {
            $startIdx = null;
            $endIdx = null;
            $componentNodes = [];

            // Find the array indices (not node indices) for the MWE components
            foreach ($nodes as $arrayIdx => $node) {
                if ($node->index >= $mwe['startIndex'] && $node->index <= $mwe['endIndex']) {
                    if ($startIdx === null) {
                        $startIdx = $arrayIdx;
                    }
                    $endIdx = $arrayIdx;
                    $componentNodes[] = $node;
                }
            }

            if ($startIdx !== null && count($componentNodes) > 0) {
                // Get POS from view_lemma_pos for this MWE
                $mwePos = MWE::getPOS($mwe['phrase'], $idLanguage);

                // Create MWE node from components with proper POS
                $mweNode = PhrasalCENode::fromMWEComponents($componentNodes, count($componentNodes), $mwePos);

                // Replace the nodes with the MWE node
                array_splice($nodes, $startIdx, count($componentNodes), [$mweNode]);
            }
        }

        return $nodes;
    }

    /**
     * Assemble MWEs by merging information from text parse (with preserved contractions)
     * with expanded parse (with full dependency tree).
     *
     * This is the key method that solves the MWE detection problem:
     * - MWEs are detected using textNodes (e.g., "pelo menos")
     * - But we need to apply them to expandedNodes (e.g., "por o menos")
     *
     * @param  array  $expandedNodes  Nodes from expanded parse (with "por o" instead of "pelo")
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
            // Find the corresponding nodes in textNodes (these have the indices from text parse)
            $textComponentNodes = [];
            foreach ($textNodes as $node) {
                if ($node->index >= $mwe['startIndex'] && $node->index <= $mwe['endIndex']) {
                    $textComponentNodes[] = $node;
                }
            }

            if (empty($textComponentNodes)) {
                continue;
            }

            // Map text node indices to expanded node positions
            // This is complex because:
            // - Text parse: "pelo" is index 4
            // - Expanded parse: "por" is index 4, "o" is index 5
            // We need to find ALL expanded nodes that correspond to the MWE range

            $startTextIndex = $mwe['startIndex'];
            $endTextIndex = $mwe['endIndex'];

            // Find all expanded nodes that fall within or overlap this range
            $expandedComponentNodes = [];
            $startArrayIdx = null;
            $endArrayIdx = null;

            foreach ($expandedNodes as $arrayIdx => $node) {
                // Check if this expanded node's index is within the text range
                // Since contractions create multiple indices, we need to check if the node
                // falls within the MWE span in the original text
                if ($node->index >= $startTextIndex && $node->index <= $endTextIndex) {
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
                // Use the text components count for proper MWE assembly
                $mweNode = PhrasalCENode::fromMWEComponents(
                    $expandedComponentNodes,
                    count($textComponentNodes),
                    $mwePos
                );

                // Set the original MWE phrase as the word (using preserved contractions)
                $mweNode->word = $mwe['phrase'];

                // Replace the expanded nodes with the MWE node
                array_splice($expandedNodes, $startArrayIdx, count($expandedComponentNodes), [$mweNode]);
            }
        }

        return $expandedNodes;
    }

    private function displayResults(array $results, string $format, bool $verboseFeatures): void
    {
        $showMweCandidates = $this->option('show-mwe-candidates');

        foreach ($results as $result) {
            $this->displaySentenceResult($result, $format, $verboseFeatures, $showMweCandidates);
        }
    }

    private function displaySentenceResult(array $result, string $format, bool $verboseFeatures, bool $showMweCandidates = false): void
    {
        $this->info("Sentence {$result['index']}: {$result['sentence']}");
        $this->line(str_repeat('─', 60));

        if ($format === 'table') {
            $this->displayAsTable($result, $verboseFeatures);
        } elseif ($format === 'json') {
            $this->displayAsJson($result, $verboseFeatures);
        } elseif ($format === 'csv') {
            $this->displayAsCsv($result, $verboseFeatures);
        }

        // Show MWE information if grammar is used
        if (! empty($result['detectedMWEs'])) {
            $this->newLine();
            $this->line('<fg=green>Detected MWEs:</>');
            foreach ($result['detectedMWEs'] as $mwe) {
                $this->line("  • <fg=cyan>{$mwe['phrase']}</> (words {$mwe['startIndex']}-{$mwe['endIndex']})");
            }
        }

        if ($showMweCandidates && ! empty($result['mweCandidates'])) {
            $this->newLine();
            $this->line('<fg=yellow>MWE Candidates (incomplete):</>');
            foreach ($result['mweCandidates'] as $candidate) {
                $matched = implode(' ', $candidate['matchedWords']);
                $this->line("  • {$candidate['phrase']} - matched: \"{$matched}\" ({$candidate['activation']}/{$candidate['threshold']})");
            }
        }

        $this->newLine();
    }

    private function displayAsTable(array $result, bool $verboseFeatures): void
    {
        $hasGrammar = $this->idGrammarGraph !== null;

        $headers = ['#', 'Word', 'Lemma', 'POS', 'PhrasalCE', 'DepRel', 'Head'];

        if ($hasGrammar) {
            $headers[] = 'MWE';
        }

        if ($verboseFeatures) {
            $headers[] = 'Features';
        }

        $rows = [];
        foreach ($result['nodes'] as $node) {
            $row = [
                $node->index,
                $node->word,
                $node->lemma,
                $node->pos,
                $this->formatCE($node->phrasalCE),
                $node->deprel ?? '-',
                $node->head ?? '-',
            ];

            if ($hasGrammar) {
                // MWE flag
                $row[] = $node->isMWE ? '<fg=cyan>✓</>' : '-';
            }

            if ($verboseFeatures) {
                $row[] = $this->formatFeatures($node->getLexicalFeatures());
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        // Show analysis row
        $this->line('');
        $analysisFormat = $hasGrammar
            ? fn ($n) => $n->word.'['.$n->phrasalCE->value.($n->isMWE ? '/MWE' : '').']'
            : fn ($n) => $n->word.'['.$n->phrasalCE->value.']';

        $this->line('Analysis: '.implode(' + ', array_map($analysisFormat, $result['nodes'])));
    }

    private function displayAsJson(array $result, bool $verboseFeatures): void
    {
        $data = [
            'sentence' => $result['sentence'],
            'tokens' => array_map(function ($node) use ($verboseFeatures) {
                $tokenData = [
                    'index' => $node->index,
                    'word' => $node->word,
                    'lemma' => $node->lemma,
                    'pos' => $node->pos,
                    'phrasal_ce' => $node->phrasalCE->value,
                    'deprel' => $node->deprel,
                    'head' => $node->head,
                    'isMWE' => $node->isMWE,
                ];

                if ($verboseFeatures) {
                    $tokenData['features'] = $node->getLexicalFeatures();
                }

                return $tokenData;
            }, $result['nodes']),
        ];

        // Add MWE information
        if (! empty($result['detectedMWEs'])) {
            $data['detectedMWEs'] = $result['detectedMWEs'];
        }

        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function displayAsCsv(array $result, bool $verboseFeatures): void
    {
        $headers = ['index', 'word', 'lemma', 'pos', 'phrasal_ce', 'deprel', 'head'];
        if ($verboseFeatures) {
            $headers[] = 'features';
        }

        $this->line(implode(',', $headers));

        foreach ($result['nodes'] as $node) {
            $row = [
                $node->index,
                '"'.str_replace('"', '""', $node->word).'"',
                '"'.str_replace('"', '""', $node->lemma).'"',
                $node->pos,
                $node->phrasalCE->value,
                $node->deprel ?? '',
                $node->head ?? '',
            ];

            if ($verboseFeatures) {
                $row[] = '"'.json_encode($node->getLexicalFeatures()).'"';
            }

            $this->line(implode(',', $row));
        }
    }

    private function formatCE(PhrasalCE $ce): string
    {
        $colors = [
            'Head' => 'green',
            'Mod' => 'blue',
            'Adm' => 'yellow',
            'Adp' => 'cyan',
            'Lnk' => 'magenta',
            'Clf' => 'white',
            'Idx' => 'white',
            'Conj' => 'red',
        ];

        $color = $colors[$ce->value] ?? 'white';

        return "<fg={$color}>{$ce->value}</>";
    }

    private function formatFeatures(array $features): string
    {
        if (empty($features)) {
            return '-';
        }

        $parts = [];
        foreach ($features as $name => $value) {
            $parts[] = "{$name}={$value}";
        }

        return implode('|', $parts);
    }

    private function displayStatistics(): void
    {
        $this->info('Statistics');
        $this->line(str_repeat('─', 60));

        // General stats
        $generalStats = [
            ['Sentences Processed', $this->stats['sentences_processed']],
            ['Tokens Processed', $this->stats['tokens_processed']],
            ['Parse Errors', $this->stats['parse_errors']],
        ];

        // Add MWE stats if grammar was used
        if ($this->idGrammarGraph) {
            $generalStats[] = ['MWEs Detected', $this->stats['mwes_detected']];
            $generalStats[] = ['MWE Candidates (incomplete)', $this->stats['mwe_candidates']];
        }

        $this->table(['Metric', 'Value'], $generalStats);

        // CE Distribution
        if (! empty($this->stats['ce_distribution'])) {
            $this->newLine();
            $this->info('PhrasalCE Distribution:');

            arsort($this->stats['ce_distribution']);
            $ceRows = [];
            foreach ($this->stats['ce_distribution'] as $ce => $count) {
                $percentage = round(($count / $this->stats['tokens_processed']) * 100, 1);
                $ceRows[] = [$ce, $count, "{$percentage}%"];
            }

            $this->table(['PhrasalCE', 'Count', 'Percentage'], $ceRows);
        }

        // POS Distribution
        if (! empty($this->stats['pos_distribution'])) {
            $this->newLine();
            $this->info('POS Distribution:');

            arsort($this->stats['pos_distribution']);
            $posRows = [];
            foreach ($this->stats['pos_distribution'] as $pos => $count) {
                $percentage = round(($count / $this->stats['tokens_processed']) * 100, 1);
                $posRows[] = [$pos, $count, "{$percentage}%"];
            }

            $this->table(['POS', 'Count', 'Percentage'], $posRows);
        }
    }

    private function saveOutput(array $results, string $outputFile, string $format): void
    {
        $output = '';

        if ($format === 'json') {
            $data = array_map(function ($result) {
                return [
                    'index' => $result['index'],
                    'sentence' => $result['sentence'],
                    'tokens' => array_map(fn ($node) => $node->toArray(), $result['nodes']),
                ];
            }, $results);
            $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'csv') {
            $lines = ['index,sentence_index,word,lemma,pos,phrasal_ce,deprel,head'];
            foreach ($results as $result) {
                foreach ($result['nodes'] as $node) {
                    $lines[] = implode(',', [
                        $node->index,
                        $result['index'],
                        '"'.str_replace('"', '""', $node->word).'"',
                        '"'.str_replace('"', '""', $node->lemma).'"',
                        $node->pos,
                        $node->phrasalCE->value,
                        $node->deprel ?? '',
                        $node->head ?? '',
                    ]);
                }
            }
            $output = implode("\n", $lines);
        } else {
            // Table format - save as readable text
            $lines = [];
            foreach ($results as $result) {
                $lines[] = "Sentence {$result['index']}: {$result['sentence']}";
                $lines[] = str_repeat('─', 60);
                $lines[] = sprintf('%-4s %-20s %-20s %-8s %-8s %-10s %-5s', '#', 'Word', 'Lemma', 'POS', 'CE', 'DepRel', 'Head');
                foreach ($result['nodes'] as $node) {
                    $lines[] = sprintf(
                        '%-4d %-20s %-20s %-8s %-8s %-10s %-5s',
                        $node->index,
                        mb_substr($node->word, 0, 20),
                        mb_substr($node->lemma, 0, 20),
                        $node->pos,
                        $node->phrasalCE->value,
                        $node->deprel ?? '-',
                        $node->head ?? '-'
                    );
                }
                $lines[] = '';
                $lines[] = 'Analysis: '.implode(' + ', array_map(
                    fn ($n) => $n->word.'['.$n->phrasalCE->value.']',
                    $result['nodes']
                ));
                $lines[] = '';
                $lines[] = '';
            }
            $output = implode("\n", $lines);
        }

        file_put_contents($outputFile, $output);
        $this->info("Results saved to: {$outputFile}");
    }
}

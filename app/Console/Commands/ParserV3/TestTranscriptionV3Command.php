<?php

namespace App\Console\Commands\ParserV3;

use App\Enums\Parser\PhrasalCE;
use App\Services\Parser\TranscriptionService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;

/**
 * Test Stage 1 (Transcription V3) with Construction and MWE Detection
 *
 * Reads sentences from a text file and applies the V3 Transcription stage
 * to generate PhrasalCENodes with BNF construction detection and MWE assembly.
 *
 * V3 improvements over V2:
 * - BNF-based construction pattern matching
 * - Semantic value calculation
 * - Enhanced MWE detection
 */
class TestTranscriptionV3Command extends Command
{
    protected $signature = 'parser:test-transcription-v3
                            {file : Path to input file with sentences (one per line)}
                            {--language=pt : Language code (pt, en)}
                            {--grammar= : Grammar graph ID for MWE/construction detection}
                            {--output= : Output file for results (optional)}
                            {--format=table : Output format (table, json, csv)}
                            {--verbose-features : Show all features for each token}
                            {--show-constructions : Show detected constructions in detail}
                            {--show-mwe-candidates : Show MWE candidates even if not completed}
                            {--limit= : Limit number of sentences to process}
                            {--skip= : Skip first N sentences}';

    protected $description = 'Test Stage 1 (Transcription V3): Parse sentences with construction and MWE detection';

    private TrankitService $trankit;

    private TranscriptionService $transcriptionService;

    private ?int $idGrammarGraph = null;

    private array $stats = [
        'sentences_processed' => 0,
        'tokens_processed' => 0,
        'parse_errors' => 0,
        'ce_distribution' => [],
        'pos_distribution' => [],
        'mwes_detected' => 0,
        'mwe_candidates' => 0,
        'constructions_detected' => 0,
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');
        $language = $this->option('language');
        $outputFile = $this->option('output');
        $format = $this->option('format');
        $verboseFeatures = $this->option('verbose-features');
        $showConstructions = $this->option('show-constructions');
        $showMweCandidates = $this->option('show-mwe-candidates');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $skip = $this->option('skip') ? (int) $this->option('skip') : 0;

        // Grammar graph for MWE/construction detection
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
        $this->displayResults($results, $format, $verboseFeatures, $showConstructions);

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
        $this->info('Stage 1 (Transcription V3) Test');
        $this->line(str_repeat('─', 60));
        $this->line('Configuration:');
        $this->line("  • Input file: {$filePath}");
        $this->line("  • Language: {$language}");
        $this->line("  • Output format: {$format}");
        $this->line('  • Limit: '.($limit ?: 'No limit'));
        $this->line("  • Skip: {$skip}");

        if ($this->idGrammarGraph) {
            $this->line("  • Grammar Graph: ID {$this->idGrammarGraph}");
            $this->line('  • Features: MWE Detection + Construction Detection (V3)');
        } else {
            $this->line('  • Grammar Graph: <fg=yellow>None</> (use --grammar=ID for MWE/construction detection)');
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

        // Initialize V3 Transcription Service
        $this->transcriptionService = app(TranscriptionService::class);
        $this->info('Transcription V3 service initialized');
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

            // Parse with Trankit to get UD tokens
            $udResult = $this->trankit->getUDTrankit($sentence, $idLanguage);
            $tokens = $udResult->udpipe ?? [];

            if (empty($tokens)) {
                $this->stats['parse_errors']++;

                return null;
            }

            // Use V3 transcription service (includes construction detection + MWE)
            $nodes = $this->transcriptionService->transcribeV3(
                $tokens,
                $this->idGrammarGraph ?? 1,
                $idLanguage
            );

            // Update statistics
            foreach ($nodes as $node) {
                $this->stats['tokens_processed']++;
                $ceValue = $node->phrasalCE->value;
                $this->stats['ce_distribution'][$ceValue] = ($this->stats['ce_distribution'][$ceValue] ?? 0) + 1;
                $this->stats['pos_distribution'][$node->pos] = ($this->stats['pos_distribution'][$node->pos] ?? 0) + 1;

                if ($node->isMWE) {
                    $this->stats['mwes_detected']++;
                }

                if (isset($node->features['derived']['construction'])) {
                    $this->stats['constructions_detected']++;
                }
            }

            return [
                'index' => $index,
                'sentence' => $sentence,
                'tokens' => $tokens,
                'nodes' => $nodes,
            ];
        } catch (\Exception $e) {
            $this->stats['parse_errors']++;
            $this->warn("Error processing sentence {$index}: {$e->getMessage()}");

            return null;
        }
    }

    private function displayResults(array $results, string $format, bool $verboseFeatures, bool $showConstructions): void
    {
        $showMweCandidates = $this->option('show-mwe-candidates');

        foreach ($results as $result) {
            $this->displaySentenceResult($result, $format, $verboseFeatures, $showConstructions);
        }
    }

    private function displaySentenceResult(array $result, string $format, bool $verboseFeatures, bool $showConstructions): void
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

        // Show construction information if requested
        if ($showConstructions) {
            $this->displayConstructionInfo($result['nodes']);
        }

        $this->newLine();
    }

    private function displayAsTable(array $result, bool $verboseFeatures): void
    {
        $hasGrammar = $this->idGrammarGraph !== null;

        $headers = ['#', 'Word', 'Lemma', 'POS', 'PhrasalCE', 'DepRel', 'Head'];

        if ($hasGrammar) {
            $headers[] = 'MWE';
            $headers[] = 'Constr';
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
                // Construction flag
                $row[] = isset($node->features['derived']['construction']) ? '<fg=green>✓</>' : '-';
            }

            if ($verboseFeatures) {
                $row[] = $this->formatFeatures($node->features);
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        // Show analysis row
        $this->line('');
        $analysisFormat = $hasGrammar
            ? fn ($n) => $n->word.'['.$n->phrasalCE->value.
                ($n->isMWE ? '/MWE' : '').
                (isset($n->features['derived']['construction']) ? '/C' : '').']'
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
                    'construction' => $node->features['derived']['construction'] ?? null,
                ];

                if ($verboseFeatures) {
                    $tokenData['features'] = $node->features;
                }

                return $tokenData;
            }, $result['nodes']),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function displayAsCsv(array $result, bool $verboseFeatures): void
    {
        $headers = ['index', 'word', 'lemma', 'pos', 'phrasal_ce', 'deprel', 'head', 'is_mwe', 'construction'];
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
                $node->isMWE ? '1' : '0',
                isset($node->features['derived']['construction']) ? '"'.$node->features['derived']['construction'].'"' : '',
            ];

            if ($verboseFeatures) {
                $row[] = '"'.json_encode($node->features).'"';
            }

            $this->line(implode(',', $row));
        }
    }

    private function displayConstructionInfo(array $nodes): void
    {
        $constructions = array_filter($nodes, fn ($n) => isset($n->features['derived']['construction']));

        if (empty($constructions)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=green>Detected Constructions:</>');

        foreach ($constructions as $node) {
            $construction = $node->features['derived']['construction'];
            $semanticValue = $node->features['derived']['semanticValue'] ?? 'N/A';
            $slots = $node->features['derived']['slots'] ?? [];

            $this->line("  • <fg=cyan>{$node->word}</> → Construction: {$construction}");
            $this->line("    Semantic Value: {$semanticValue}");

            if (! empty($slots)) {
                $this->line('    Slots: '.json_encode($slots));
            }
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
        $parts = [];

        // Lexical features
        if (! empty($features['lexical'])) {
            foreach ($features['lexical'] as $key => $value) {
                $parts[] = "{$key}={$value}";
            }
        }

        // Derived features
        if (! empty($features['derived'])) {
            if (isset($features['derived']['construction'])) {
                $parts[] = 'Constr='.$features['derived']['construction'];
            }
            if (isset($features['derived']['semanticValue'])) {
                $parts[] = 'SemVal='.$features['derived']['semanticValue'];
            }
        }

        return empty($parts) ? '-' : implode('|', $parts);
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

        // Add V3-specific stats if grammar was used
        if ($this->idGrammarGraph) {
            $generalStats[] = ['MWEs Detected', $this->stats['mwes_detected']];
            $generalStats[] = ['Constructions Detected (V3)', $this->stats['constructions_detected']];
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
                    'tokens' => array_map(fn ($node) => [
                        'index' => $node->index,
                        'word' => $node->word,
                        'lemma' => $node->lemma,
                        'pos' => $node->pos,
                        'phrasal_ce' => $node->phrasalCE->value,
                        'deprel' => $node->deprel,
                        'head' => $node->head,
                        'isMWE' => $node->isMWE,
                        'construction' => $node->features['derived']['construction'] ?? null,
                        'features' => $node->features,
                    ], $result['nodes']),
                ];
            }, $results);
            $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } elseif ($format === 'csv') {
            $lines = ['index,sentence_index,word,lemma,pos,phrasal_ce,deprel,head,is_mwe,construction'];
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
                        $node->isMWE ? '1' : '0',
                        isset($node->features['derived']['construction']) ? '"'.$node->features['derived']['construction'].'"' : '',
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
                $lines[] = sprintf('%-4s %-20s %-20s %-8s %-8s %-10s %-5s %-5s %-10s', '#', 'Word', 'Lemma', 'POS', 'CE', 'DepRel', 'Head', 'MWE', 'Constr');
                foreach ($result['nodes'] as $node) {
                    $lines[] = sprintf(
                        '%-4d %-20s %-20s %-8s %-8s %-10s %-5s %-5s %-10s',
                        $node->index,
                        mb_substr($node->word, 0, 20),
                        mb_substr($node->lemma, 0, 20),
                        $node->pos,
                        $node->phrasalCE->value,
                        $node->deprel ?? '-',
                        $node->head ?? '-',
                        $node->isMWE ? 'Y' : '-',
                        isset($node->features['derived']['construction']) ? 'Y' : '-'
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

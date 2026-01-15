<?php

namespace App\Console\Commands\ParserV4;

use App\Data\Parser\ParseInputData;
use App\Services\Parser\ParserService;
use Illuminate\Console\Command;

/**
 * Test V4 Incremental Constructional Parser
 *
 * This command allows testing the V4 parser with sample sentences
 * to verify correct operation and compare with V3 results.
 *
 * Usage:
 *   php artisan parser:test-v4 "O menino chegou"
 *   php artisan parser:test-v4 --file=test_sentences.txt
 *   php artisan parser:test-v4 --interactive
 *   php artisan parser:test-v4 --compare  # Compare V3 vs V4
 */
class TestV4Command extends Command
{
    protected $signature = 'parser:test-v4
                            {sentence? : Sentence to parse}
                            {--grammar=1 : Grammar graph ID}
                            {--file= : File with test sentences (one per line)}
                            {--interactive : Interactive mode}
                            {--compare : Compare V3 vs V4 results}
                            {--detailed : Show detailed output}
                            {--log-progress : Enable progress logging}';

    protected $description = 'Test the V4 Incremental Constructional Parser';

    private ParserService $parserService;

    public function handle(): int
    {
        $this->info('Parser V4 Test Command');
        $this->newLine();

        $this->parserService = app(ParserService::class);
        $grammarId = (int) $this->option('grammar');

        // Interactive mode
        if ($this->option('interactive')) {
            return $this->runInteractiveMode($grammarId);
        }

        // File mode
        if ($file = $this->option('file')) {
            return $this->runFileMode($file, $grammarId);
        }

        // Single sentence mode
        if ($sentence = $this->argument('sentence')) {
            return $this->testSentence($sentence, $grammarId);
        }

        // No input provided - show examples
        $this->showExamples($grammarId);

        return 0;
    }

    /**
     * Run interactive mode
     */
    private function runInteractiveMode(int $grammarId): int
    {
        $this->info('Interactive Mode - Enter sentences to parse (type "exit" to quit)');
        $this->newLine();

        while (true) {
            $sentence = $this->ask('Enter sentence');

            if (! $sentence || strtolower($sentence) === 'exit') {
                $this->info('Exiting...');
                break;
            }

            $this->testSentence($sentence, $grammarId);
            $this->newLine();
        }

        return 0;
    }

    /**
     * Run file mode - parse sentences from a file
     */
    private function runFileMode(string $filepath, int $grammarId): int
    {
        if (! file_exists($filepath)) {
            $this->error("File not found: {$filepath}");

            return 1;
        }

        $sentences = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->info('Parsing '.count($sentences).' sentences from file...');
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        $bar = $this->output->createProgressBar(count($sentences));
        $bar->start();

        foreach ($sentences as $sentence) {
            // Skip comments
            if (str_starts_with(trim($sentence), '#')) {
                continue;
            }

            try {
                $result = $this->parseSentence($sentence, $grammarId);
                if ($result->isValid) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\Exception $e) {
                $failCount++;
                if ($this->option('detailed')) {
                    $this->error("\nError parsing: {$sentence}");
                    $this->error($e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Parsing Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failCount],
                ['Total', count($sentences)],
            ]
        );

        return $failCount > 0 ? 1 : 0;
    }

    /**
     * Test a single sentence
     */
    private function testSentence(string $sentence, int $grammarId): int
    {
        $this->info("Testing: \"{$sentence}\"");
        $this->newLine();

        // Enable logging if requested
        if ($this->option('log-progress')) {
            config(['parser.v4.logProgress' => true]);
            config(['parser.logging.logStages' => true]);
        }

        // Compare mode - test both V3 and V4
        if ($this->option('compare')) {
            return $this->compareV3AndV4($sentence, $grammarId);
        }

        // V4 only
        try {
            $startTime = microtime(true);
            $result = $this->parseSentence($sentence, $grammarId);
            $elapsed = microtime(true) - $startTime;

            $this->displayResults($result, $elapsed);

            return $result->isValid ? 0 : 1;
        } catch (\Exception $e) {
            $this->error('Parse Error: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Parse a sentence with current configuration
     */
    private function parseSentence(string $sentence, int $grammarId)
    {
        $input = new ParseInputData(
            sentence: $sentence,
            idGrammarGraph: $grammarId,
            queueStrategy: config('parser.queueStrategy', 'fifo')
        );

        return $this->parserService->parse($input);
    }

    /**
     * Compare V3 and V4 parsing results
     */
    private function compareV3AndV4(string $sentence, int $grammarId): int
    {
        $this->info('Comparing V3 vs V4 parsers...');
        $this->newLine();

        // Parse with V3
        $this->line('→ Parsing with V3...');
        config(['parser.version' => 'v3']);
        config(['parser.v4.enabled' => false]);

        try {
            $startV3 = microtime(true);
            $v3Result = $this->parseSentence($sentence, $grammarId);
            $v3Time = microtime(true) - $startV3;
        } catch (\Exception $e) {
            $this->error('V3 Error: '.$e->getMessage());
            $v3Result = null;
            $v3Time = 0;
        }

        // Parse with V4
        $this->line('→ Parsing with V4...');
        config(['parser.version' => 'v4']);
        config(['parser.v4.enabled' => true]);

        try {
            $startV4 = microtime(true);
            $v4Result = $this->parseSentence($sentence, $grammarId);
            $v4Time = microtime(true) - $startV4;
        } catch (\Exception $e) {
            $this->error('V4 Error: '.$e->getMessage());
            $v4Result = null;
            $v4Time = 0;
        }

        // Compare results
        $this->newLine();
        $this->info('Comparison Results:');
        $this->table(
            ['Metric', 'V3', 'V4', 'Difference'],
            [
                ['Status', $v3Result?->status ?? 'error', $v4Result?->status ?? 'error', ''],
                ['Nodes', $v3Result?->nodeCount ?? 0, $v4Result?->nodeCount ?? 0, ($v4Result?->nodeCount ?? 0) - ($v3Result?->nodeCount ?? 0)],
                ['Edges', $v3Result?->edgeCount ?? 0, $v4Result?->edgeCount ?? 0, ($v4Result?->edgeCount ?? 0) - ($v3Result?->edgeCount ?? 0)],
                ['Time (s)', number_format($v3Time, 4), number_format($v4Time, 4), number_format($v4Time - $v3Time, 4)],
                ['Valid', $v3Result?->isValid ? 'Yes' : 'No', $v4Result?->isValid ? 'Yes' : 'No', ''],
            ]
        );

        if ($this->option('verbose')) {
            if ($v3Result) {
                $this->displayResults($v3Result, $v3Time, 'V3');
            }
            if ($v4Result) {
                $this->displayResults($v4Result, $v4Time, 'V4');
            }
        }

        return 0;
    }

    /**
     * Display parsing results
     */
    private function displayResults($result, float $elapsed, string $label = 'V4'): void
    {
        $this->info("{$label} Parse Results:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Status', $result->status],
                ['Valid', $result->isValid ? 'Yes' : 'No'],
                ['Nodes', $result->nodeCount],
                ['Edges', $result->edgeCount],
                ['Focus Nodes', $result->focusNodeCount ?? 'N/A'],
                ['MWE Nodes', $result->mweNodeCount ?? 'N/A'],
                ['Parse Time', number_format($elapsed, 4).'s'],
            ]
        );

        if ($this->option('verbose') && ! empty($result->nodes)) {
            $this->newLine();
            $this->info('Nodes:');
            $nodeData = [];
            foreach ($result->nodes as $node) {
                $nodeData[] = [
                    $node->label ?? '',
                    $node->pos ?? '',
                    $node->type ?? '',
                    $node->phrasalCE ?? '-',
                    $node->clausalCE ?? '-',
                    $node->sententialCE ?? '-',
                ];
            }
            $this->table(
                ['Label', 'POS', 'Type', 'Phrasal', 'Clausal', 'Sentential'],
                $nodeData
            );
        }

        if ($this->option('verbose') && ! empty($result->edges)) {
            $this->newLine();
            $this->info('Edges:');
            $edgeData = [];
            foreach ($result->edges as $edge) {
                $edgeData[] = [
                    $edge->sourceLabel ?? '',
                    $edge->relation ?? '',
                    $edge->targetLabel ?? '',
                ];
            }
            $this->table(
                ['Source', 'Relation', 'Target'],
                $edgeData
            );
        }

        if (! $result->isValid && $result->errorMessage) {
            $this->newLine();
            $this->error('Error: '.$result->errorMessage);
        }
    }

    /**
     * Show usage examples
     */
    private function showExamples(int $grammarId): void
    {
        $this->info('No sentence provided. Here are some usage examples:');
        $this->newLine();

        $examples = [
            'php artisan parser:test-v4 "O menino chegou"',
            'php artisan parser:test-v4 "Tomei café da manhã" --detailed',
            'php artisan parser:test-v4 "O jogador marcou um gol contra" --log-progress',
            'php artisan parser:test-v4 --interactive',
            'php artisan parser:test-v4 --file=test_sentences.txt',
            'php artisan parser:test-v4 "Quando eu cheguei, ela estava lá" --compare',
        ];

        foreach ($examples as $example) {
            $this->line("  {$example}");
        }

        $this->newLine();
        $this->info('Sample test sentences:');
        $samples = [
            'O menino chegou',
            'O menino chegou cedo',
            'Tomei café da manhã',
            'O jogador marcou um gol contra',
            'O jogador marcou um gol contra o time',
            'Quando eu cheguei, ela estava lá',
            'O menino que eu vi chegou',
        ];

        foreach ($samples as $sample) {
            $this->line("  - {$sample}");
        }
    }
}

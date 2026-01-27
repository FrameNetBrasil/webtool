<?php

namespace App\Console\Commands\Parser;

use App\Data\Parser\ParseInputData;
use App\Services\Parser\ParserService;
use Illuminate\Console\Command;

/**
 * Test V4 Incremental Constructional Parser
 *
 * This command allows testing the V4 parser with sample sentences
 * to verify correct operation.
 *
 * Usage:
 *   php artisan parser:test-v4 "O menino chegou"
 *   php artisan parser:test-v4 --file=test_sentences.txt
 *   php artisan parser:test-v4 --interactive
 */
class TestV4Command extends Command
{
    protected $signature = 'parser:test-v4
                            {sentence? : Sentence to parse}
                            {--grammar=1 : Grammar graph ID}
                            {--file= : File with test sentences (one per line)}
                            {--interactive : Interactive mode}
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

        // V4 parser
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

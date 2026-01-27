<?php

namespace App\Console\Commands\Parser;

use App\Repositories\Parser\GrammarGraph;
use App\Repositories\Parser\TypeGraphRepository;
use App\Services\Parser\IncrementalParserEngineV5;
use App\Services\Parser\StateSnapshotService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Test V5 Incremental Constructional Parser with Ghost Nodes
 *
 * This command allows testing the V5 parser with sample sentences
 * and provides access to state snapshots for debugging.
 *
 * Usage:
 *   php artisan parser:test-v5 "O menino chegou"
 *   php artisan parser:test-v5 --file=test_sentences.txt
 *   php artisan parser:test-v5 --file=test_sentences.txt --save-snapshots
 *   php artisan parser:test-v5 --interactive
 */
class TestParseV5Command extends Command
{
    protected $signature = 'parser:test-v5
                            {sentence? : Sentence to parse}
                            {--grammar=1 : Grammar graph ID}
                            {--file= : File with test sentences (one per line)}
                            {--interactive : Interactive mode}
                            {--detailed : Show detailed parse output}
                            {--snapshots : Show state snapshots at each position}
                            {--save-snapshots : Save snapshots to database}
                            {--ghost-only : Show only positions with ghost nodes}
                            {--log-progress : Enable progress logging}';

    protected $description = 'Test the V5 Incremental Constructional Parser with Ghost Nodes';

    /**
     * Execute the command
     */
    public function handle(): int
    {
        $this->displayHeader();

        $grammarId = (int) $this->option('grammar');

        // Validate grammar exists
        if (! $this->validateGrammar($grammarId)) {
            return self::FAILURE;
        }

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

        // No input - show examples
        $this->showExamples($grammarId);

        return self::SUCCESS;
    }

    /**
     * Display header
     */
    private function displayHeader(): void
    {
        $this->info('╔════════════════════════════════════════════════╗');
        $this->info('║  Parser V5 Test Command (Ghost Nodes)          ║');
        $this->info('╚════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Validate grammar exists
     */
    private function validateGrammar(int $grammarId): bool
    {
        try {
            $grammar = GrammarGraph::byId($grammarId);
            $this->info("Using Grammar: {$grammar->name} (ID: {$grammarId})");
            $this->newLine();

            return true;
        } catch (\Exception $e) {
            $this->error("Grammar ID {$grammarId} not found");

            return false;
        }
    }

    /**
     * Run interactive mode
     */
    private function runInteractiveMode(int $grammarId): int
    {
        $this->info('🔄 Interactive Mode - Enter sentences to parse (type "exit" to quit)');
        $this->newLine();

        while (true) {
            $sentence = $this->ask('📝 Enter sentence');

            if (! $sentence || strtolower($sentence) === 'exit') {
                $this->info('👋 Exiting...');
                break;
            }

            $this->testSentence($sentence, $grammarId);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /**
     * Run file mode - parse sentences from a file
     */
    private function runFileMode(string $filepath, int $grammarId): int
    {
        if (! file_exists($filepath)) {
            $this->error("❌ File not found: {$filepath}");

            return self::FAILURE;
        }

        $sentences = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $sentences = array_filter($sentences, fn ($s) => ! str_starts_with(trim($s), '#'));

        $this->info('📁 Parsing '.count($sentences).' sentences from file...');
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        $bar = $this->output->createProgressBar(count($sentences));
        $bar->start();

        foreach ($sentences as $idx => $sentence) {
            $sentence = trim($sentence);

            if (empty($sentence)) {
                continue;
            }

            try {
                $this->parseSentence($sentence, $grammarId, $idx + 1);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                if ($this->option('detailed')) {
                    $this->newLine();
                    $this->error("  ❌ Error parsing: {$sentence}");
                    $this->error("     {$e->getMessage()}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->displaySummary($successCount, $failCount, count($sentences));

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Test a single sentence (user-facing)
     */
    private function testSentence(string $sentence, int $grammarId): int
    {
        try {
            $this->info("🔍 Parsing: \"{$sentence}\"");
            $this->newLine();

            $result = $this->parseSentence($sentence, $grammarId);

            $this->displayParseResults($result);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Parse failed: {$e->getMessage()}");

            if ($this->option('detailed')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    /**
     * Parse a sentence (internal)
     */
    private function parseSentence(string $sentence, int $grammarId, ?int $lineNumber = null): array
    {
        $startTime = microtime(true);

        // Get grammar details
        $grammar = GrammarGraph::byId($grammarId);
        $idLanguage = config('parser.languageMap')[$grammar->language] ?? 1;

        // Step 1: Parse with UD (Trankit)
        $tokens = $this->parseWithUD($sentence, $idLanguage);

        if (empty($tokens)) {
            throw new \Exception('UD parsing returned no tokens');
        }

        // Step 2: Load Type Graph
        $typeGraphRepo = app(TypeGraphRepository::class);
        $typeGraph = $typeGraphRepo->loadByGrammar($grammarId);

        if (! $typeGraph) {
            throw new \Exception('Type Graph not found. Run: php artisan parser:build-type-graph --grammar='.$grammarId);
        }

        // Step 3: Create parser graph ID (unique for this test)
        $idParserGraph = DB::table('parser_graph')->insertGetId([
            'sentence' => $sentence,
            'idGrammarGraph' => $grammarId,
            'status' => 'testing',
            'created_at' => now(),
        ]);

        // Step 4: Run V5 parser
        $v5Engine = app(IncrementalParserEngineV5::class);

        // Convert tokens to objects
        $tokenObjects = array_map(fn ($t) => (object) $t, $tokens);

        // Parse!
        $parseState = $v5Engine->parse($tokenObjects, $grammarId, $idParserGraph, $typeGraph);

        // Step 5: Save snapshots if requested
        if ($this->option('save-snapshots')) {
            $snapshotService = app(StateSnapshotService::class);
            $snapshotIds = $snapshotService->saveAllSnapshots($parseState);

            $this->line('  💾 Saved '.count($snapshotIds).' snapshots to database');
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'sentence' => $sentence,
            'lineNumber' => $lineNumber,
            'tokens' => $tokens,
            'parseState' => $parseState,
            'idParserGraph' => $idParserGraph,
            'duration' => $duration,
        ];
    }

    /**
     * Parse with UD (Trankit)
     */
    private function parseWithUD(string $sentence, int $idLanguage): array
    {
        $trankitService = app(TrankitService::class);
        $trankitUrl = config('parser.trankit.url');
        $trankitService->init($trankitUrl);

        $udResult = $trankitService->getUDTrankit($sentence, $idLanguage);

        return $udResult->udpipe ?? [];
    }

    /**
     * Display parse results
     */
    private function displayParseResults(array $result): void
    {
        $state = $result['parseState'];

        // Basic info
        $this->info("✅ Parse completed in {$result['duration']}ms");
        $this->newLine();

        $this->displayMetrics($state, $result['tokens']);

        // Show tokens
        if ($this->option('detailed')) {
            $this->displayTokens($result['tokens']);
        }

        // Show snapshots
        if ($this->option('snapshots')) {
            $this->displaySnapshots($state);
        }

        // Show ghost nodes summary
        $this->displayGhostNodesSummary($state);
    }

    /**
     * Display parse metrics
     */
    private function displayMetrics($state, array $tokens): void
    {
        $ghostStats = $state->ghostManager?->getStatistics() ?? ['total' => 0];

        $this->info('📊 Parse Metrics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tokens', count($tokens)],
                ['Snapshots', count($state->stateSnapshots)],
                ['Ghost Nodes Created', $ghostStats['total']],
                ['Confirmed Nodes', count($state->confirmedNodes)],
                ['Confirmed Edges', count($state->confirmedEdges)],
                ['Active Alternatives', count($state->alternatives)],
            ]
        );
        $this->newLine();
    }

    /**
     * Display tokens
     */
    private function displayTokens(array $tokens): void
    {
        $this->info('🔤 Tokens:');

        $rows = [];
        foreach ($tokens as $idx => $token) {
            $rows[] = [
                $idx,
                $token['form'] ?? $token['word'] ?? '',
                $token['upos'] ?? $token['pos'] ?? '',
                $token['lemma'] ?? '',
                $token['deprel'] ?? '',
            ];
        }

        $this->table(
            ['Position', 'Word', 'POS', 'Lemma', 'DepRel'],
            $rows
        );
        $this->newLine();
    }

    /**
     * Display state snapshots
     */
    private function displaySnapshots($state): void
    {
        $this->info('📸 State Snapshots:');
        $this->newLine();

        $ghostOnly = $this->option('ghost-only');

        foreach ($state->stateSnapshots as $snapshot) {
            $position = $snapshot['position'];
            $tokenData = $snapshot['tokenData'] ?? null;
            $ghostNodes = $snapshot['ghostNodes'] ?? [];

            // Skip if ghost-only mode and no ghosts
            if ($ghostOnly && empty($ghostNodes)) {
                continue;
            }

            // Handle both object and array tokenData
            if (is_object($tokenData)) {
                $word = $tokenData->word ?? $tokenData->form ?? "position {$position}";
            } elseif (is_array($tokenData)) {
                $word = $tokenData['word'] ?? $tokenData['form'] ?? "position {$position}";
            } else {
                $word = "position {$position}";
            }

            $this->info("  Position {$position}: \"{$word}\"");

            if (! empty($ghostNodes)) {
                $this->warn('    👻 Ghost Nodes: '.count($ghostNodes));

                foreach ($ghostNodes as $ghost) {
                    $ghostType = is_array($ghost) ? ($ghost['ghostType'] ?? 'N/A') : ($ghost->ghostType ?? 'N/A');
                    $expectedCE = is_array($ghost) ? ($ghost['expectedCE'] ?? 'N/A') : ($ghost->expectedCE ?? 'N/A');
                    $this->line("       - Type: {$ghostType}, Expected CE: {$expectedCE}");
                }
            }

            $tokenGraph = $snapshot['tokenGraph'] ?? [];
            $nodes = is_array($tokenGraph) ? ($tokenGraph['nodes'] ?? []) : [];
            $edges = is_array($tokenGraph) ? ($tokenGraph['edges'] ?? []) : [];

            $this->line('    📊 Token Graph: '.count($nodes).' nodes, '.count($edges).' edges');
            $this->line('    🔄 Active Alternatives: '.(is_countable($snapshot['activeAlternatives'] ?? 0) ? count($snapshot['activeAlternatives']) : ($snapshot['activeAlternatives'] ?? 0)));

            if (! empty($snapshot['reconfigurations'])) {
                $this->line('    ⚡ Reconfigurations: '.count($snapshot['reconfigurations']));
            }

            $this->newLine();
        }
    }

    /**
     * Display ghost nodes summary
     */
    private function displayGhostNodesSummary($state): void
    {
        $ghostsArray = $state->ghostManager?->toArray() ?? [];

        if (empty($ghostsArray)) {
            $this->info('ℹ️  No ghost nodes were created during parsing.');

            return;
        }

        $this->info('👻 Ghost Nodes Summary:');
        $this->newLine();

        $rows = [];
        foreach ($ghostsArray as $idx => $ghost) {
            $rows[] = [
                $idx + 1,
                $ghost['ghostType'] ?? 'N/A',
                $ghost['expectedCE'] ?? 'N/A',
                $ghost['createdAtPosition'] ?? 'N/A',
                ($ghost['state'] ?? 'pending') === 'fulfilled' ? '✅ Yes' : '❌ No',
            ];
        }

        $this->table(
            ['#', 'Ghost Type', 'Expected CE', 'Position', 'Fulfilled'],
            $rows
        );
        $this->newLine();
    }

    /**
     * Display summary
     */
    private function displaySummary(int $success, int $failed, int $total): void
    {
        $this->info('📋 Parsing Summary:');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['✅ Success', $success, round(($success / $total) * 100, 1).'%'],
                ['❌ Failed', $failed, round(($failed / $total) * 100, 1).'%'],
                ['📊 Total', $total, '100%'],
            ]
        );
    }

    /**
     * Show usage examples
     */
    private function showExamples(int $grammarId): void
    {
        $this->warn('ℹ️  No input provided. Here are usage examples:');
        $this->newLine();

        $this->line('📝 Parse a single sentence:');
        $this->line('   php artisan parser:test-v5 "O menino chegou"');
        $this->newLine();

        $this->line('📁 Parse sentences from a file:');
        $this->line('   php artisan parser:test-v5 --file=test_sentences.txt');
        $this->newLine();

        $this->line('🔄 Interactive mode:');
        $this->line('   php artisan parser:test-v5 --interactive');
        $this->newLine();

        $this->line('🔍 Show detailed output with snapshots:');
        $this->line('   php artisan parser:test-v5 "A menina comeu" --detailed --snapshots');
        $this->newLine();

        $this->line('💾 Save snapshots to database:');
        $this->line('   php artisan parser:test-v5 --file=test.txt --save-snapshots');
        $this->newLine();

        $this->line('👻 Show only positions with ghost nodes:');
        $this->line('   php artisan parser:test-v5 "Ela chegou" --snapshots --ghost-only');
        $this->newLine();
    }
}

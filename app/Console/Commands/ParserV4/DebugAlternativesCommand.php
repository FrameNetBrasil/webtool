<?php

namespace App\Console\Commands\ParserV4;

use App\Services\Parser\V4\IncrementalParserEngine;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;

/**
 * Debug V4 Parser Alternatives Tracking
 *
 * This command provides detailed debugging output for the V4 parser,
 * showing how alternatives are created, updated, and resolved during
 * incremental parsing.
 *
 * Usage:
 *   php artisan parser:debug-alternatives "O menino chegou"
 *   php artisan parser:debug-alternatives "Tomei café da manhã" --show-pruned
 *   php artisan parser:debug-alternatives "O jogador marcou um gol contra" --mwe-only
 */
class DebugAlternativesCommand extends Command
{
    protected $signature = 'parser:debug-alternatives
                            {sentence : Sentence to parse and debug}
                            {--grammar=1 : Grammar graph ID}
                            {--show-pruned : Show pruned alternatives}
                            {--show-constraints : Show constraint violations}
                            {--mwe-only : Only show MWE alternatives}
                            {--export= : Export debug data to JSON file}';

    protected $description = 'Debug V4 parser alternatives tracking with detailed output';

    private array $alternativeHistory = [];

    private array $positionSnapshots = [];

    public function handle(): int
    {
        $sentence = $this->argument('sentence');
        $grammarId = (int) $this->option('grammar');

        $this->info('Parser V4 Alternatives Debugging');
        $this->line("Sentence: \"{$sentence}\"");
        $this->line('Grammar ID: '.$grammarId);
        $this->newLine();

        // Enable detailed logging
        config(['parser.v4.logProgress' => true]);
        config(['parser.v4.logAlternatives' => true]);
        config(['parser.v4.saveHistory' => true]);

        // Get UD tokens
        $tokens = $this->getTokens($sentence);

        if (empty($tokens)) {
            $this->error('Failed to parse sentence with UD parser');

            return 1;
        }

        $this->info('Tokens:');
        $this->displayTokens($tokens);
        $this->newLine();

        // Parse with V4 and capture state
        try {
            $result = $this->parseAndCapture($tokens, $grammarId);

            // Display results
            $this->displayDebugOutput($result);

            // Export if requested
            if ($exportPath = $this->option('export')) {
                $this->exportDebugData($result, $exportPath);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Parse error: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Get UD tokens for sentence
     */
    private function getTokens(string $sentence): array
    {
        $trankitService = app(TrankitService::class);
        $trankitUrl = config('parser.trankit.url');
        $trankitService->init($trankitUrl);

        // Default to Portuguese (language ID 1)
        $udResult = $trankitService->getUDTrankit($sentence, 1);

        return $udResult->udpipe ?? [];
    }

    /**
     * Display tokens table
     */
    private function displayTokens(array $tokens): void
    {
        $data = [];
        foreach ($tokens as $i => $token) {
            $data[] = [
                $i,
                $token['word'] ?? $token['form'] ?? '',
                $token['lemma'] ?? '',
                $token['pos'] ?? $token['upos'] ?? '',
                $token['feats'] ?? '',
            ];
        }

        $this->table(
            ['Pos', 'Word', 'Lemma', 'POS', 'Features'],
            $data
        );
    }

    /**
     * Parse with V4 and capture debug information
     */
    private function parseAndCapture(array $tokens, int $grammarId): array
    {
        $engine = app(IncrementalParserEngine::class);

        // Convert to objects
        $tokenObjects = array_map(fn ($t) => (object) $t, $tokens);

        // Intercept logging to capture alternatives
        $this->setupLoggingCapture();

        // Parse
        $state = $engine->parse($tokenObjects, $grammarId);

        return [
            'state' => $state,
            'tokens' => $tokens,
            'history' => $this->alternativeHistory,
            'snapshots' => $this->positionSnapshots,
        ];
    }

    /**
     * Setup logging capture for alternatives tracking
     */
    private function setupLoggingCapture(): void
    {
        // This would ideally hook into the logger, but for simplicity
        // we can use the state history that's already being saved
    }

    /**
     * Display debug output
     */
    private function displayDebugOutput(array $result): void
    {
        $state = $result['state'];
        $tokens = $result['tokens'];

        $this->info('Parse Statistics:');
        $stats = $state->getStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Position', $stats['currentPosition']],
                ['Active Alternatives', $stats['activeAlternatives']],
                ['Confirmed Nodes', $stats['confirmedNodes']],
                ['Confirmed Edges', $stats['confirmedEdges']],
                ['Aggregated MWEs', $stats['aggregatedMWEs']],
                ['Consumed Positions', $stats['consumedPositions']],
            ]
        );

        $this->newLine();
        $this->info('Confirmed Nodes:');
        $this->displayNodes($state->confirmedNodes);

        if (! empty($state->confirmedEdges)) {
            $this->newLine();
            $this->info('Confirmed Edges:');
            $this->displayEdges($state->confirmedEdges);
        }

        if (! empty($state->aggregatedMWEs)) {
            $this->newLine();
            $this->info('Aggregated MWEs:');
            $this->displayMWEs($state->aggregatedMWEs);
        }

        if (! empty($state->stateHistory)) {
            $this->newLine();
            $this->info('State History:');
            $this->displayHistory($state->stateHistory);
        }

        // Analyze alternative behavior
        $this->newLine();
        $this->analyzeAlternativeBehavior($state);
    }

    /**
     * Display nodes table
     */
    private function displayNodes(array $nodes): void
    {
        if (empty($nodes)) {
            $this->line('  No nodes confirmed');

            return;
        }

        $mweOnly = $this->option('mwe-only');
        $data = [];

        foreach ($nodes as $node) {
            if ($mweOnly && ($node['type'] ?? null) !== 'mwe') {
                continue;
            }

            $data[] = [
                $node['constructionName'] ?? 'N/A',
                $node['type'] ?? 'N/A',
                $node['position'] ?? $node['startPosition'] ?? 'N/A',
                $node['phrasalCE'] ?? '-',
                $node['clausalCE'] ?? '-',
                $node['sententialCE'] ?? '-',
            ];
        }

        if (empty($data)) {
            $this->line('  No nodes to display');

            return;
        }

        $this->table(
            ['Construction', 'Type', 'Position', 'Phrasal', 'Clausal', 'Sentential'],
            $data
        );
    }

    /**
     * Display edges table
     */
    private function displayEdges(array $edges): void
    {
        $data = [];
        foreach ($edges as $edge) {
            $data[] = [
                $edge['sourceId'] ?? 'N/A',
                $edge['relation'] ?? 'N/A',
                $edge['targetId'] ?? 'N/A',
            ];
        }

        $this->table(
            ['Source', 'Relation', 'Target'],
            $data
        );
    }

    /**
     * Display MWEs table
     */
    private function displayMWEs(array $mwes): void
    {
        $data = [];
        foreach ($mwes as $mwe) {
            $components = $mwe['components'] ?? [];
            $componentStr = count($components).' components';

            $data[] = [
                $mwe['constructionName'] ?? 'N/A',
                $mwe['startPosition'] ?? 'N/A',
                $mwe['endPosition'] ?? 'N/A',
                $componentStr,
            ];
        }

        $this->table(
            ['Construction', 'Start', 'End', 'Components'],
            $data
        );
    }

    /**
     * Display state history
     */
    private function displayHistory(array $history): void
    {
        $data = [];
        foreach ($history as $snapshot) {
            $data[] = [
                $snapshot['position'] ?? 'N/A',
                $snapshot['confirmedNodesCount'] ?? 0,
                $snapshot['confirmedEdgesCount'] ?? 0,
                $snapshot['activeAlternativesCount'] ?? 0,
            ];
        }

        $this->table(
            ['Position', 'Nodes', 'Edges', 'Active Alts'],
            $data
        );
    }

    /**
     * Analyze alternative behavior patterns
     */
    private function analyzeAlternativeBehavior($state): void
    {
        $this->info('Alternative Behavior Analysis:');

        // Analyze from history
        if (! empty($state->stateHistory)) {
            $maxAlts = 0;
            $totalAlts = 0;
            $count = 0;

            foreach ($state->stateHistory as $snapshot) {
                $alts = $snapshot['activeAlternativesCount'] ?? 0;
                $maxAlts = max($maxAlts, $alts);
                $totalAlts += $alts;
                $count++;
            }

            $avgAlts = $count > 0 ? round($totalAlts / $count, 2) : 0;

            $this->line("  Max Active Alternatives: {$maxAlts}");
            $this->line("  Avg Active Alternatives: {$avgAlts}");
        }

        // MWE analysis
        $mweCount = count($state->aggregatedMWEs ?? []);
        if ($mweCount > 0) {
            $this->line("  MWEs Aggregated: {$mweCount}");

            foreach ($state->aggregatedMWEs as $mwe) {
                $name = $mwe['constructionName'] ?? 'Unknown';
                $this->line("    - {$name}");
            }
        } else {
            $this->line('  No MWEs aggregated');
        }

        // Coverage analysis
        $confirmedPositions = [];
        foreach ($state->confirmedNodes as $node) {
            if (isset($node['position'])) {
                $confirmedPositions[] = $node['position'];
            } elseif (isset($node['startPosition']) && isset($node['endPosition'])) {
                for ($i = $node['startPosition']; $i <= $node['endPosition']; $i++) {
                    $confirmedPositions[] = $i;
                }
            }
        }

        $uniquePositions = count(array_unique($confirmedPositions));
        $this->line("  Positions Covered: {$uniquePositions}");
    }

    /**
     * Export debug data to JSON
     */
    private function exportDebugData(array $result, string $path): void
    {
        $data = [
            'sentence' => $this->argument('sentence'),
            'grammarId' => $this->option('grammar'),
            'timestamp' => now()->toIso8601String(),
            'state' => [
                'confirmedNodes' => $result['state']->confirmedNodes,
                'confirmedEdges' => $result['state']->confirmedEdges,
                'aggregatedMWEs' => $result['state']->aggregatedMWEs,
                'consumedPositions' => $result['state']->consumedPositions,
                'statistics' => $result['state']->getStatistics(),
                'history' => $result['state']->stateHistory,
            ],
            'tokens' => $result['tokens'],
        ];

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Debug data exported to: {$path}");
    }
}

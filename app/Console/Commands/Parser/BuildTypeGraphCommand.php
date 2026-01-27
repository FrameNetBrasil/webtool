<?php

namespace App\Console\Commands\Parser;

use App\Repositories\Parser\TypeGraphRepository;
use App\Services\Parser\TypeGraphBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Build Type Graph Command
 *
 * Manually builds or rebuilds Type Graphs for grammars.
 *
 * Usage:
 *   php artisan parser:build-type-graph --grammar=1
 *   php artisan parser:build-type-graph --all
 */
class BuildTypeGraphCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'parser:build-type-graph
                            {--grammar= : Grammar ID to build Type Graph for}
                            {--all : Build Type Graphs for all grammars}
                            {--force : Force rebuild even if Type Graph exists}';

    /**
     * The console command description.
     */
    protected $description = 'Build or rebuild Type Graph for parser grammars';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $builder = app(TypeGraphBuilder::class);
        $repository = app(TypeGraphRepository::class);

        // Determine which grammars to process
        $grammars = $this->getGrammars();

        if (empty($grammars)) {
            $this->error('No grammars found. Use --grammar=ID or --all');

            return self::FAILURE;
        }

        $this->info('Building Type Graphs for '.count($grammars).' grammar(s)...');
        $this->newLine();

        $successCount = 0;
        $failureCount = 0;

        foreach ($grammars as $idGrammar) {
            try {
                // Check if Type Graph already exists
                $existing = $repository->loadByGrammar($idGrammar);

                if ($existing && ! $this->option('force')) {
                    $this->warn("⊘ Grammar {$idGrammar}: Type Graph already exists (use --force to rebuild)");

                    continue;
                }

                $this->info("Building Type Graph for grammar {$idGrammar}...");

                $startTime = microtime(true);

                // Build Type Graph
                $typeGraph = $builder->buildForGrammar($idGrammar);

                // Save to database
                $repository->save($typeGraph);

                $duration = round(microtime(true) - $startTime, 2);

                $this->info("✓ Grammar {$idGrammar}: Type Graph saved ({$duration}s)");
                $this->line('  - Nodes: '.count($typeGraph->nodes));
                $this->line('  - Edges: '.count($typeGraph->edges));
                $this->line('  - Mandatory elements: '.count($typeGraph->mandatoryElements));
                $this->newLine();

                $successCount++;
            } catch (\Exception $e) {
                $this->error("✗ Grammar {$idGrammar}: Failed - {$e->getMessage()}");
                $this->newLine();

                $failureCount++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->info("  - Success: {$successCount}");

        if ($failureCount > 0) {
            $this->error("  - Failed: {$failureCount}");
        }

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get list of grammar IDs to process
     */
    private function getGrammars(): array
    {
        if ($this->option('all')) {
            return DB::table('parser_grammar_graph')
                ->pluck('idGrammarGraph')
                ->toArray();
        }

        if ($grammarId = $this->option('grammar')) {
            return [(int) $grammarId];
        }

        return [];
    }
}

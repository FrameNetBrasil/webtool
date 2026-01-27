<?php

namespace App\Console\Commands\CLN;

use App\Services\Parser\PatternCompiler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compile Construction Patterns Command
 *
 * Compiles BNF patterns for existing constructions that don't have compiled patterns.
 *
 * Usage:
 *   php artisan parser:compile-patterns --construction=3139
 *   php artisan parser:compile-patterns --all
 *   php artisan parser:compile-patterns --force
 */
class CompilePatternsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cln:compile-patterns
                            {--construction= : Construction ID to compile pattern for}
                            {--all : Compile patterns for all constructions}
                            {--force : Force recompile even if pattern already exists}';

    /**
     * The console command description.
     */
    protected $description = 'Compile BNF patterns for parser constructions into executable graph structures';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $compiler = new PatternCompiler;

        // Determine which constructions to process
        $constructions = $this->getConstructions();

        if (empty($constructions)) {
            $this->error('No constructions found. Use --construction=ID or --all');

            return self::FAILURE;
        }

        $this->info('Compiling patterns for '.count($constructions).' construction(s)...');
        $this->newLine();

        $successCount = 0;
        $skipCount = 0;
        $failureCount = 0;

        foreach ($constructions as $construction) {
            try {
                // Check if already compiled
                if (! $this->option('force') && ! empty($construction->compiledPattern)) {
                    $this->warn("⊘ Construction {$construction->idConstruction} ({$construction->name}): Already compiled (use --force to recompile)");
                    $skipCount++;

                    continue;
                }

                // Check if pattern exists
                if (empty($construction->pattern)) {
                    $this->warn("⊘ Construction {$construction->idConstruction} ({$construction->name}): No pattern to compile");
                    $skipCount++;

                    continue;
                }

                $this->info("Compiling pattern for construction {$construction->idConstruction} ({$construction->name})...");
                $this->line("  Pattern: {$construction->pattern}");

                $startTime = microtime(true);

                // Compile pattern
                $compiled = $compiler->compile($construction->pattern,$construction->constructionType);

                // Save to database
                DB::table('parser_construction_v4')
                    ->where('idConstruction', $construction->idConstruction)
                    ->update(['compiledPattern' => json_encode($compiled)]);

                $duration = round(microtime(true) - $startTime, 3);

                $this->info("✓ Construction {$construction->idConstruction}: Pattern compiled ({$duration}s)");
                $this->line('  - Nodes: '.count($compiled['nodes']));
                $this->line('  - Edges: '.count($compiled['edges']));
                $this->line('  - Mandatory elements: '.count($compiled['mandatoryElements']));
                $this->newLine();

                $successCount++;
            } catch (\Exception $e) {
                $this->error("✗ Construction {$construction->idConstruction} ({$construction->name}): Failed - {$e->getMessage()}");
                $this->newLine();

                $failureCount++;
            }
        }

        // Summary
        $this->newLine();
        $this->info('Summary:');
        $this->info("  - Success: {$successCount}");
        $this->info("  - Skipped: {$skipCount}");

        if ($failureCount > 0) {
            $this->error("  - Failed: {$failureCount}");
        }

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get list of constructions to process
     */
    private function getConstructions(): array
    {
        if ($this->option('all')) {
            return DB::table('parser_construction_v4')
                ->select('idConstruction', 'name', 'pattern', 'compiledPattern','constructionType')
                ->get()
                ->all();
        }

        if ($constructionId = $this->option('construction')) {
            $construction = DB::table('parser_construction_v4')
                ->select('idConstruction', 'name', 'pattern', 'compiledPattern','constructionType')
                ->where('idConstruction', $constructionId)
                ->first();

            return $construction ? [$construction] : [];
        }

        return [];
    }
}

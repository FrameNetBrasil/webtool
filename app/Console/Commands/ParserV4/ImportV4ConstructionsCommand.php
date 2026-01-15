<?php

namespace App\Console\Commands\ParserV4;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import V4 Construction Definitions
 *
 * Imports construction patterns from JSON files into parser_construction_v4 table.
 * Files:
 * - phrasal_constructions.json (Head, Mod, Adp, Lnk, etc.)
 * - clausal_constructions.json (Pred, Arg, CPP, FPM, Gen, etc.)
 * - sentential_constructions.json (Main, Adv, Rel, Comp, etc.)
 * - mwe_constructions.json (Multi-word expressions)
 *
 * Usage:
 *   php artisan parser:import-v4-constructions --grammar=1
 *   php artisan parser:import-v4-constructions --grammar=1 --clear
 */
class ImportV4ConstructionsCommand extends Command
{
    protected $signature = 'parser:import-v4-constructions
                            {--grammar=1 : Grammar graph ID}
                            {--clear : Clear existing constructions before import}
                            {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import V4 construction definitions from JSON files';

    private int $imported = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $grammarId = (int) $this->option('grammar');
        $clear = $this->option('clear');
        $dryRun = $this->option('dry-run');

        $this->info('Parser V4 Construction Import');
        $this->info("Grammar Graph ID: {$grammarId}");
        $this->newLine();

        // Clear existing constructions if requested
        if ($clear && ! $dryRun) {
            $this->clearExistingConstructions($grammarId);
        }

        // Import construction files in order
        $files = [
            'phrasal_constructions.json' => 'Phrasal CE Constructions',
            'clausal_constructions.json' => 'Clausal CE Constructions',
            'sentential_constructions.json' => 'Sentential CE Constructions',
            'mwe_constructions.json' => 'MWE Constructions',
        ];

        foreach ($files as $filename => $label) {
            $this->importFile($filename, $label, $grammarId, $dryRun);
        }

        // Summary
        $this->newLine();
        $this->info('Import Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $this->imported],
                ['Skipped', $this->skipped],
                ['Errors', $this->errors],
            ]
        );

        if ($dryRun) {
            $this->warn('This was a dry run. No data was actually imported.');
        }

        return $this->errors > 0 ? 1 : 0;
    }

    private function clearExistingConstructions(int $grammarId): void
    {
        $this->warn("Clearing existing constructions for grammar {$grammarId}...");

        $deleted = DB::table('parser_construction_v4')
            ->where('idGrammarGraph', $grammarId)
            ->delete();

        $this->info("Deleted {$deleted} existing constructions");
        $this->newLine();
    }

    private function importFile(string $filename, string $label, int $grammarId, bool $dryRun): void
    {
        $path = resource_path("data/v4/{$filename}");

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            $this->errors++;

            return;
        }

        $this->info("Importing {$label}...");

        $json = file_get_contents($path);
        $constructions = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON error: '.json_last_error_msg());
            $this->errors++;

            return;
        }

        $bar = $this->output->createProgressBar(count($constructions));
        $bar->start();

        foreach ($constructions as $construction) {
            $result = $this->importConstruction($construction, $grammarId, $dryRun);

            if ($result === 'imported') {
                $this->imported++;
            } elseif ($result === 'skipped') {
                $this->skipped++;
            } else {
                $this->errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function importConstruction(array $data, int $grammarId, bool $dryRun): string
    {
        try {
            // Check if construction already exists
            $existing = DB::table('parser_construction_v4')
                ->where('idGrammarGraph', $grammarId)
                ->where('name', $data['name'])
                ->first();

            if ($existing) {
                return 'skipped';
            }

            if ($dryRun) {
                return 'imported'; // Count as imported in dry run
            }

            // Insert construction
            DB::table('parser_construction_v4')->insert([
                'idGrammarGraph' => $grammarId,
                'name' => $data['name'],
                'constructionType' => $data['constructionType'],
                'pattern' => $data['pattern'],
                'compiledPattern' => isset($data['compiledPattern']) ? json_encode($data['compiledPattern']) : null,
                'priority' => $data['priority'] ?? 50,
                'enabled' => $data['enabled'] ?? true,
                'phrasalCE' => $data['phrasalCE'] ?? null,
                'clausalCE' => $data['clausalCE'] ?? null,
                'sententialCE' => $data['sententialCE'] ?? null,
                'constraints' => json_encode($data['constraints'] ?? []),
                'aggregateAs' => $data['aggregateAs'] ?? null,
                'semanticType' => $data['semanticType'] ?? null,
                'semantics' => isset($data['semantics']) ? json_encode($data['semantics']) : null,
                'lookaheadEnabled' => $data['lookaheadEnabled'] ?? false,
                'lookaheadMaxDistance' => $data['lookaheadMaxDistance'] ?? 2,
                'invalidationPatterns' => json_encode($data['invalidationPatterns'] ?? []),
                'confirmationPatterns' => json_encode($data['confirmationPatterns'] ?? []),
                'description' => $data['description'] ?? null,
                'examples' => isset($data['examples']) ? json_encode($data['examples']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 'imported';
        } catch (\Exception $e) {
            $this->error("Error importing {$data['name']}: ".$e->getMessage());

            return 'error';
        }
    }
}

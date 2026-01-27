<?php

namespace App\Console\Commands\CLN;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import CLN Construction Definitions
 *
 * Imports construction patterns from JSON files into parser_construction_v4 table
 * with ghost node support (mandatoryElements, optionalElements, ghostCreationRules).
 *
 * Files:
 * - phrasal_constructions.json (Head, Mod, Adp, Lnk, etc.)
 * - clausal_constructions.json (Pred, Arg, CPP, FPM, Gen, etc.)
 * - sentential_constructions.json (Main, Adv, Rel, Comp, etc.)
 * - mwe_constructions.json (Multi-word expressions)
 *
 * Ghost Node Schema Extensions:
 * - mandatoryElements: Elements required by this construction (can create ghosts)
 * - optionalElements: Elements that are optional in this construction
 * - ghostCreationRules: Rules for when/how to create ghost nodes
 *
 * Usage:
 *   php artisan cln:import-constructions --grammar=1
 *   php artisan cln:import-constructions --grammar=1 --clear
 *   php artisan cln:import-constructions --grammar=1 --data-path=resources/data/cln_01
 */
class ImportConstructionsCommand extends Command
{
    protected $signature = 'cln:import-constructions
                            {--grammar=1 : Grammar graph ID}
                            {--clear : Clear existing constructions before import}
                            {--dry-run : Show what would be imported without actually importing}
                            {--data-path=resources/data/cln_01 : Path to construction JSON files}';

    protected $description = 'Import construction definitions from JSON files with ghost node support';

    private int $imported = 0;

    private int $skipped = 0;

    private int $errors = 0;

    public function handle(): int
    {
        $grammarId = (int) $this->option('grammar');
        $clear = $this->option('clear');
        $dryRun = $this->option('dry-run');
        $dataPath = $this->option('data-path');

        $this->info('CLN Construction Import');
        $this->info("Grammar Graph ID: {$grammarId}");
        $this->info("Data Path: {$dataPath}");
        $this->newLine();

        // Verify ghost node schema extensions exist
        if (! $this->verifyGhostNodeSchema($dryRun)) {
            $this->error('Ghost node schema extensions not found. Please run the migration first.');

            return 1;
        }

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
            $this->importFile($filename, $label, $grammarId, $dataPath, $dryRun);
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

    private function verifyGhostNodeSchema(bool $dryRun): bool
    {
        if ($dryRun) {
            return true; // Skip verification in dry-run mode
        }

        // Check if ghost node schema columns exist in parser_construction_v4
        $columns = DB::select(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'parser_construction_v4'
             AND COLUMN_NAME IN ('mandatoryElements', 'optionalElements', 'ghostCreationRules')"
        );

        $foundColumns = array_column($columns, 'COLUMN_NAME');
        $requiredColumns = ['mandatoryElements', 'optionalElements', 'ghostCreationRules'];

        $missingColumns = array_diff($requiredColumns, $foundColumns);

        if (! empty($missingColumns)) {
            $this->warn('Missing ghost node schema columns: '.implode(', ', $missingColumns));

            return false;
        }

        $this->info('Ghost node schema extensions verified');

        return true;
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

    private function importFile(string $filename, string $label, int $grammarId, string $dataPath, bool $dryRun): void
    {
        $path = base_path("{$dataPath}/{$filename}");

        if (! file_exists($path)) {
            $this->warn("File not found: {$path} (skipping)");

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

            // Prepare V4 fields
            $insertData = [
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
            ];

            // Add ghost node schema extensions
            $insertData['mandatoryElements'] = isset($data['mandatoryElements'])
                ? json_encode($data['mandatoryElements'])
                : null;

            $insertData['optionalElements'] = isset($data['optionalElements'])
                ? json_encode($data['optionalElements'])
                : null;

            $insertData['ghostCreationRules'] = isset($data['ghostCreationRules'])
                ? json_encode($data['ghostCreationRules'])
                : null;

            // Insert construction
            DB::table('parser_construction_v4')->insert($insertData);

            return 'imported';
        } catch (\Exception $e) {
            $this->error("Error importing {$data['name']}: ".$e->getMessage());

            return 'error';
        }
    }
}

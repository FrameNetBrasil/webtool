<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportDulRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-dul-relations {--dry-run : Display relations without creating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DUL relations (classes, properties, and frame elements) into the database';

    private array $files = [
        'fe_relations.csv',
        'dul_class_relations_mapped.csv',
        'dul_property_relations_mapped.csv',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseDir = base_path('app/Console/Commands/FN3');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No relations will be created');
        }

        $totalImported = 0;
        $totalErrors = 0;

        foreach ($this->files as $filename) {
            $filepath = $baseDir . '/' . $filename;

            if (!File::exists($filepath)) {
                $this->error("File not found: {$filepath}");
                continue;
            }

            $this->info("\nProcessing file: {$filename}");
            $relations = $this->readCSV($filepath);
            $totalRelations = count($relations);
            $this->info("Found {$totalRelations} relations to import");

            $imported = 0;
            $errors = 0;

            foreach ($relations as $index => $relation) {
                try {
                    if ($dryRun) {
                        $this->line("Would create: idEntity1={$relation[0]}, idEntity2={$relation[1]}, idEntity3={$relation[2]}");
                    } else {
                        $this->createRelation($relation[0], $relation[1], $relation[2]);
                    }
                    $imported++;

                    // Show progress every 10 relations
                    if (($imported % 10) === 0) {
                        $this->info("  Processed {$imported}/{$totalRelations} relations...");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Failed to create relation: idEntity1={$relation[0]}, idEntity2={$relation[1]}, idEntity3={$relation[2]}");
                    $this->error("  Error: " . $e->getMessage());
                }
            }

            $this->info("Completed {$filename}: {$imported} imported, {$errors} errors");
            $totalImported += $imported;
            $totalErrors += $errors;
        }

        $this->info("\n=== Summary ===");
        $this->info("Total relations imported: {$totalImported}");
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
        }

        return self::SUCCESS;
    }

    /**
     * Create a relation in the database.
     */
    private function createRelation(int $idEntity1, int $idEntity2, int $idEntity3): void
    {
        $json = json_encode([
            'relationType' => 'rel_microframe',
            'idEntity1' => $idEntity1,
            'idEntity2' => $idEntity2,
            'idEntity3' => $idEntity3,
        ]);

        DB::select('SELECT relation_create(?)', [$json]);
    }

    /**
     * Read CSV file and return data rows (skipping header).
     */
    private function readCSV(string $filepath): array
    {
        $rows = [];
        $fp = fopen($filepath, 'r');

        // Skip header
        fgetcsv($fp);

        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 3) {
                $rows[] = [(int)$row[0], (int)$row[1], (int)$row[2]];
            }
        }

        fclose($fp);

        return $rows;
    }
}

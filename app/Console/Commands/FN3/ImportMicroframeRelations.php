<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMicroframeRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:microframe-relations
                            {input? : Input CSV file name (default: dul_class_relations_mapped_fixed.csv)}
                            {--relation-type=rel_microframe : Relation type to use}
                            {--dry-run : Run without creating relations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import microframe relations from CSV file using relation_create function';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputFileName = $this->argument('input') ?? 'dul_class_relations_mapped_fixed.csv';
        $relationType = $this->option('relation-type');
        $csvFile = app_path('Console/Commands/FN3/'.$inputFileName);
        $dryRun = $this->option('dry-run');

        if (! file_exists($csvFile)) {
            $this->error("CSV file not found: {$csvFile}");

            return self::FAILURE;
        }

        $this->info('Reading CSV file...');
        $handle = fopen($csvFile, 'r');

        if (! $handle) {
            $this->error('Failed to open CSV file');

            return self::FAILURE;
        }

        $lineNumber = 0;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Skip header
        fgetcsv($handle);

        $this->info($dryRun ? 'DRY RUN MODE - No relations will be created' : 'Creating relations...');
        $this->newLine();

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if (count($row) < 3) {
                $this->warn("Line {$lineNumber}: Skipped - insufficient columns");
                $errorCount++;

                continue;
            }

            $idEntity1 = $row[0];
            $idEntity2 = $row[1];
            $idEntity3 = $row[2];

            // Build JSON for relation_create
            $json = json_encode([
                'relationType' => $relationType,
                'idEntity1' => (int) $idEntity1,
                'idEntity2' => (int) $idEntity2,
                'idEntity3' => (int) $idEntity3,
            ], JSON_UNESCAPED_SLASHES);

            try {
                if (! $dryRun) {
                    // Call relation_create function
                    $result = DB::select('select relation_create(?) as result', [$json]);
                    $relationId = $result[0]->result ?? null;

                    if ($relationId) {
                        $this->info("Line {$lineNumber}: Created relation {$relationId} ({$idEntity1}, {$idEntity2}, {$idEntity3})");
                        $successCount++;
                    } else {
                        $this->warn("Line {$lineNumber}: Function returned null");
                        $errorCount++;
                        $errors[] = "Line {$lineNumber}: Null result";
                    }
                } else {
                    $this->info("Line {$lineNumber}: Would create relation ({$idEntity1}, {$idEntity2}, {$idEntity3})");
                    $successCount++;
                }
            } catch (\Exception $e) {
                $this->error("Line {$lineNumber}: Error - {$e->getMessage()}");
                $errors[] = "Line {$lineNumber}: {$e->getMessage()}";
                $errorCount++;
            }
        }

        fclose($handle);

        // Summary
        $this->newLine();
        $this->info('=== Import Summary ===');
        $this->info("Total rows processed: {$lineNumber}");
        $this->info("Successful: {$successCount}");
        $this->info("Errors: {$errorCount}");

        if (! empty($errors)) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($errors as $error) {
                $this->warn("  - {$error}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('This was a DRY RUN. Run without --dry-run to actually create relations.');
        }

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}

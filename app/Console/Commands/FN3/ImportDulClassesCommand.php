<?php

namespace App\Console\Commands\FN3;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDulClassesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:import-dul-classes
                            {--csv= : Path to CSV file (default: dul_classes.csv in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DUL classes from CSV into class table using class_create routine';

    private array $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped_empty' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 DUL Classes Import to Class Table');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__ . '/dul_classes.csv';
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user') ?? 6;

        // Validate CSV file exists
        if (! file_exists($csvPath)) {
            $this->error("❌ CSV file not found: {$csvPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  • CSV File: {$csvPath}");
        $this->line("  • User ID: {$userId}");
        if ($isDryRun) {
            $this->warn('  • DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Read and validate CSV
        $this->info('📊 Reading CSV file...');
        $rows = $this->readCsv($csvPath);

        if (empty($rows)) {
            $this->error('❌ No data rows found in CSV file');

            return 1;
        }

        $this->stats['total_rows'] = count($rows);
        $this->info("Found {$this->stats['total_rows']} rows to process");
        $this->newLine();

        // Process rows
        $this->info('🔄 Processing classes...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $userId) {
            $this->processRow($row, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Display statistics
        $this->displayStatistics($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('✅ Dry run completed. Use without --dry-run to import data.');
        } else {
            $this->newLine();
            $this->info('✅ Import completed successfully!');
        }

        return 0;
    }

    private function readCsv(string $csvPath): array
    {
        $rows = [];
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('❌ Failed to open CSV file');

            return [];
        }

        // Read data rows (no header in this CSV file)
        while (($data = fgetcsv($handle)) !== false) {
            if (! empty($data[0])) {
                $rows[] = [
                    'nameEn' => trim($data[0]),
                ];
            }
        }

        fclose($handle);

        return $rows;
    }

    private function processRow(array $row, bool $isDryRun, int $userId): void
    {
        // Skip rows with empty name
        if (empty($row['nameEn'])) {
            $this->stats['skipped_empty']++;

            return;
        }

        try {
            if (! $isDryRun) {
                $this->createClass($row['nameEn'], $userId);
            }

            $this->stats['imported']++;

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    private function createClass(string $nameEn, int $userId): void
    {
        // Prepare JSON parameter for class_create function
        $classData = [
            'nameEn' => $nameEn,
            'idUser' => $userId,
        ];

        $jsonData = json_encode($classData);

        // Call the class_create database function
        DB::select('SELECT class_create(?) as idClass', [$jsonData]);
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Import Statistics:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->stats['total_rows']],
            ['Successfully imported', $this->stats['imported'].($isDryRun ? ' (would be imported)' : '')],
            ['Skipped (empty name)', $this->stats['skipped_empty']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['errors']} classes failed to import due to errors");
        }
    }
}

<?php

namespace App\Console\Commands\Namespace;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFrameNamespaceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'namespace:import-frames
                            {--csv= : Path to CSV file (default: frames_namespaces_v01.csv in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--confidence-threshold=0.5 : Minimum confidence to import (0.0-1.0)}
                            {--language=2 : Language ID for namespace lookup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import frame namespace classifications from CSV into entityrelation table';

    private const RELATION_TYPE_NAMESPACE = 242;

    private array $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped_error' => 0,
        'skipped_low_confidence' => 0,
        'frame_not_found' => 0,
        'namespace_not_found' => 0,
        'updated_existing' => 0,
    ];

    private array $namespaceCache = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Frame Namespace Classification Import');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__.'/frames_namespaces_v01.csv';
        $isDryRun = $this->option('dry-run');
        $confidenceThreshold = (float) $this->option('confidence-threshold');
        $languageId = (int) $this->option('language');

        // Validate CSV file exists
        if (! file_exists($csvPath)) {
            $this->error("❌ CSV file not found: {$csvPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  • CSV File: {$csvPath}");
        $this->line("  • Language ID: {$languageId}");
        $this->line("  • Confidence Threshold: {$confidenceThreshold}");
        $this->line('  • Relation Type: '.self::RELATION_TYPE_NAMESPACE.' (rel_namespace)');
        if ($isDryRun) {
            $this->warn('  • DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Load namespace cache
        $this->loadNamespaceCache($languageId);

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
        $this->info('🔄 Processing classifications...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $confidenceThreshold) {
            $this->processRow($row, $isDryRun, $confidenceThreshold);
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

    private function loadNamespaceCache(int $languageId): void
    {
        try {
            $namespaces = Criteria::table('view_namespace')
                ->select('name', 'idEntity')
                ->where('idlanguage', '=', $languageId)
                ->all();

            foreach ($namespaces as $namespace) {
                $namespaceLower = strtolower($namespace->name);
                $this->namespaceCache[$namespaceLower] = $namespace->idEntity;
            }

            $this->info('  • Loaded '.count($this->namespaceCache).' namespaces from database');
        } catch (Exception $e) {
            $this->error('❌ Failed to load namespace cache: '.$e->getMessage());
            exit(1);
        }
    }

    private function readCsv(string $csvPath): array
    {
        $rows = [];
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('❌ Failed to open CSV file');

            return [];
        }

        // Read header
        $header = fgetcsv($handle);

        if (! $header || ! in_array('idFrame', $header) || ! in_array('namespace1', $header) || ! in_array('confidence1', $header)) {
            $this->error('❌ Invalid CSV format. Expected columns: idFrame, namespace1, confidence1');
            fclose($handle);

            return [];
        }

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 4) {
                $rows[] = [
                    'idFrame' => (int) $data[0],
                    'name' => $data[1],
                    'namespace' => $data[2],
                    'confidence' => ! empty($data[3]) ? (float) $data[3] : 0.0,
                ];
            }
        }

        fclose($handle);

        return $rows;
    }

    private function processRow(array $row, bool $isDryRun, float $confidenceThreshold): void
    {
        // Skip ERROR classifications
        if (strtoupper($row['namespace']) === 'ERROR' || empty($row['namespace'])) {
            $this->stats['skipped_error']++;

            return;
        }

        // Skip low confidence
        if ($row['confidence'] < $confidenceThreshold) {
            $this->stats['skipped_low_confidence']++;

            return;
        }

        try {
            // Get frame entity ID
            $frameEntity = $this->getFrameEntity($row['idFrame']);

            if (! $frameEntity) {
                $this->stats['frame_not_found']++;

                return;
            }

            // Get namespace entity ID
            $namespaceEntity = $this->getNamespaceEntity($row['namespace']);

            if (! $namespaceEntity) {
                $this->stats['namespace_not_found']++;

                return;
            }

            // Check if relation already exists
            $existingRelation = $this->checkExistingRelation($frameEntity);

            if (! $isDryRun) {
                // Delete existing relation if present
                if ($existingRelation) {
                    $this->deleteExistingRelation($frameEntity);
                    $this->stats['updated_existing']++;
                }

                // Create new relation
                $this->createRelation($frameEntity, $namespaceEntity);
            }

            $this->stats['imported']++;

        } catch (Exception $e) {
            // Silent error handling during progress bar
            $this->stats['frame_not_found']++;
        }
    }

    private function getFrameEntity(int $idFrame): ?int
    {
        try {
            $result = Criteria::table('frame')
                ->select('idEntity')
                ->where('idFrame', '=', $idFrame)
                ->first();

            return $result?->idEntity;
        } catch (Exception $e) {
            return null;
        }
    }

    private function getNamespaceEntity(string $namespace): ?int
    {
        $namespaceLower = strtolower(trim($namespace));

        return $this->namespaceCache[$namespaceLower] ?? null;
    }

    private function checkExistingRelation(int $frameEntity): bool
    {
        try {
            $result = Criteria::table('entityrelation')
                ->where('idRelationType', '=', self::RELATION_TYPE_NAMESPACE)
                ->where('idEntity1', '=', $frameEntity)
                ->first();

            return ! is_null($result);
        } catch (Exception $e) {
            return false;
        }
    }

    private function deleteExistingRelation(int $frameEntity): void
    {
        DB::table('entityrelation')
            ->where('idRelationType', '=', self::RELATION_TYPE_NAMESPACE)
            ->where('idEntity1', '=', $frameEntity)
            ->delete();
    }

    private function createRelation(int $frameEntity, int $namespaceEntity): void
    {
        DB::table('entityrelation')->insert([
            'idRelationType' => self::RELATION_TYPE_NAMESPACE,
            'idEntity1' => $frameEntity,
            'idEntity2' => $namespaceEntity,
        ]);
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Import Statistics:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->stats['total_rows']],
            ['Successfully imported', $this->stats['imported'].($isDryRun ? ' (would be imported)' : '')],
            ['Updated existing relations', $this->stats['updated_existing']],
            ['Skipped (ERROR classification)', $this->stats['skipped_error']],
            ['Skipped (low confidence)', $this->stats['skipped_low_confidence']],
            ['Skipped (frame not found)', $this->stats['frame_not_found']],
            ['Skipped (namespace not found)', $this->stats['namespace_not_found']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        // Show breakdown of errors if any
        if ($this->stats['skipped_error'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_error']} frames had ERROR classification and were skipped");
        }
    }
}

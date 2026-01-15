<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDulPropertiesFeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:import-dul-properties-fe
                            {--csv= : Path to CSV file (default: DUL_properties_edited.csv in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DUL properties frame elements from CSV using fe_create routine';

    private array $stats = [
        'total_rows' => 0,
        'fe_pairs_created' => 0,
        'skipped_property_not_found' => 0,
        'skipped_domain_not_found' => 0,
        'skipped_range_not_found' => 0,
        'skipped_missing_domain_or_range' => 0,
        'errors' => 0,
    ];

    private array $frameCache = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 DUL Properties Frame Elements Import');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__ . '/DUL_properties_edited.csv';
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

        // Load caches
        $this->info('📚 Loading frame data...');
        $this->loadFrameCache();
        $this->line("  • Loaded {$this->countFrames()} frames");
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
        $this->info('🔄 Processing frame elements...');
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

    private function loadFrameCache(): void
    {
        try {
            $frames = Criteria::table('view_frame_all')
                ->select('idFrame', 'name')
                ->where('idLanguage', '=', 2)
                ->all();

            foreach ($frames as $frame) {
                $this->frameCache[$frame->name] = $frame->idFrame;
            }
        } catch (Exception $e) {
            $this->error('❌ Failed to load frame cache: '.$e->getMessage());
            exit(1);
        }
    }

    private function countFrames(): int
    {
        return count($this->frameCache);
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

        if (! $header) {
            $this->error('❌ Failed to read CSV header');
            fclose($handle);

            return [];
        }

        // Trim header values to handle any whitespace
        $header = array_map('trim', $header);

        if (! in_array('property', $header) || ! in_array('domain', $header) || ! in_array('range', $header)) {
            $this->error('❌ Invalid CSV format. Expected columns: property, domain, range');
            fclose($handle);

            return [];
        }

        // Get column indices
        $propertyIdx = array_search('property', $header);
        $domainIdx = array_search('domain', $header);
        $rangeIdx = array_search('range', $header);

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (isset($data[$propertyIdx]) && isset($data[$domainIdx]) && isset($data[$rangeIdx])) {
                $property = trim($data[$propertyIdx]);
                $domain = trim($data[$domainIdx]);
                $range = trim($data[$rangeIdx]);

                if (! empty($property) && ! empty($domain) && ! empty($range)) {
                    $rows[] = [
                        'property' => $property,
                        'domain' => $domain,
                        'range' => $range,
                    ];
                }
            }
        }

        fclose($handle);

        return $rows;
    }

    private function processRow(array $row, bool $isDryRun, int $userId): void
    {
        // 1. Convert property name from camelCase to snake_case for lookup
        $propertySnakeCase = $this->camelToSnake($row['property']);

        // 2. Lookup property frame ID
        $idFrame = $this->frameCache[$propertySnakeCase] ?? null;

        if (! $idFrame) {
            $this->stats['skipped_property_not_found']++;

            return;
        }

        // 2. Normalize and verify domain frame exists
        $domainNormalized = $this->normalizeClassName($row['domain']);
        $domainFrameExists = isset($this->frameCache[$domainNormalized]);

        if (! $domainFrameExists) {
            $this->stats['skipped_domain_not_found']++;
            $this->stats['skipped_missing_domain_or_range']++;

            return;
        }

        // 3. Normalize and verify range frame exists
        $rangeNormalized = $this->normalizeClassName($row['range']);
        $rangeFrameExists = isset($this->frameCache[$rangeNormalized]);

        if (! $rangeFrameExists) {
            $this->stats['skipped_range_not_found']++;
            $this->stats['skipped_missing_domain_or_range']++;

            return;
        }

        try {
            if (! $isDryRun) {
                // 4. Create domain frame element
                // fe_create(idFrame, domain_name, 'cty_core', 78, 6)
                $this->createFrameElement($idFrame, $row['domain'], $userId);

                // 5. Create range frame element
                // fe_create(idFrame, range_name, 'cty_core', 78, 6)
                $this->createFrameElement($idFrame, $row['range'], $userId);
            }

            $this->stats['fe_pairs_created']++;

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    private function createFrameElement(int $idFrame, string $name, int $userId): ?int
    {
        // Call fe_create(<idFrame>, <name>, 'cty_core', 78, <userId>)
        $result = DB::select(
            'SELECT fe_create(?, ?, ?, ?, ?) as idFrameElement',
            [$idFrame, $name, 'cty_core', 78, $userId]
        );

        return $result[0]->idFrameElement ?? null;
    }

    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    private function normalizeClassName(string $className): string
    {
        // Remove numeric suffixes like "Entity1" -> "Entity", "Object2" -> "Object"
        return preg_replace('/\d+$/', '', $className);
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Import Statistics:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->stats['total_rows']],
            ['FE pairs created', $this->stats['fe_pairs_created'].($isDryRun ? ' (would be created)' : '')],
            ['Total FEs created', ($this->stats['fe_pairs_created'] * 2).($isDryRun ? ' (would be created)' : '')],
            ['Skipped (property frame not found)', $this->stats['skipped_property_not_found']],
            ['Skipped (domain/range frame missing)', $this->stats['skipped_missing_domain_or_range']],
            ['  - Domain frame not found', $this->stats['skipped_domain_not_found']],
            ['  - Range frame not found', $this->stats['skipped_range_not_found']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['skipped_property_not_found'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_property_not_found']} rows skipped due to property frame not found in database");
        }

        if ($this->stats['skipped_missing_domain_or_range'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_missing_domain_or_range']} rows skipped due to domain or range frame not found in database");
        }

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['errors']} frame element pairs failed to import due to errors");
        }
    }
}

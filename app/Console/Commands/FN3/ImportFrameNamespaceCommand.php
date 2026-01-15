<?php

namespace App\Console\Commands\FN3;

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
    protected $signature = 'fn3:import-frame-namespace
                            {--csv= : Path to CSV file (default: frame_namespace.csv in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import frame-namespace relationships from CSV and update entity relations';

    private array $stats = [
        'total_rows' => 0,
        'processed' => 0,
        'skipped_invalid' => 0,
        'errors' => 0,
    ];

    private array $frameCache = [];

    private array $namespaceCache = [];

    private array $invalidRows = [];

    private array $errorDetails = [];

    private int $relationType242 = 242;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Frame Namespace Import');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__ . '/frame_namespace.csv';
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

        // Pre-cache frames and namespaces
        $this->info('📦 Caching frames and namespaces from database...');
        $this->cacheFrames();
        $this->cacheNamespaces();
        $this->info("  • Cached {$this->formatNumber(count($this->frameCache))} frames");
        $this->info("  • Cached {$this->formatNumber(count($this->namespaceCache))} namespaces");
        $this->newLine();

        // Read and validate CSV
        $this->info('📊 Reading CSV file...');
        $rows = $this->readCsv($csvPath);

        if (empty($rows)) {
            $this->error('❌ No data rows found in CSV file');

            return 1;
        }

        $this->stats['total_rows'] = count($rows);
        $this->info("Found {$this->formatNumber($this->stats['total_rows'])} rows to process");
        $this->newLine();

        // Process rows
        $this->info('🔄 Processing frame-namespace relationships...');
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

    private function cacheFrames(): void
    {
        $frames = Criteria::table('view_frame')
            ->where('idLanguage', 1)
            ->select('idFrame', 'idEntity')
            ->get();

        foreach ($frames as $frame) {
            $this->frameCache[$frame->idFrame] = $frame->idEntity;
        }
    }

    private function cacheNamespaces(): void
    {
        $namespaces = Criteria::table('view_namespace')
            ->where('idLanguage', 1)
            ->select('name', 'idEntity')
            ->get();

        foreach ($namespaces as $namespace) {
            $this->namespaceCache[$namespace->name] = $namespace->idEntity;
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

        // Skip header row
        fgetcsv($handle);

        $lineNumber = 2; // Start at 2 because of header row

        // Read data rows (columns: namespace_name, idFrame, frame_name, description, namespace_name)
        while (($data = fgetcsv($handle)) !== false) {
            if (! empty($data[0]) && ! empty($data[1])) {
                $rows[] = [
                    'line_number' => $lineNumber,
                    'namespace_name' => trim($data[0]),
                    'idFrame' => (int) trim($data[1]),
                    'frame_name' => trim($data[2] ?? ''),
                ];
            }
            $lineNumber++;
        }

        fclose($handle);

        return $rows;
    }

    private function processRow(array $row, bool $isDryRun, int $userId): void
    {
        // Validate frame exists in cache
        if (! isset($this->frameCache[$row['idFrame']])) {
            $this->stats['skipped_invalid']++;
            $this->invalidRows[] = [
                'line' => $row['line_number'],
                'idFrame' => $row['idFrame'],
                'frame_name' => $row['frame_name'],
                'namespace' => $row['namespace_name'],
                'reason' => 'Frame not found',
            ];

            return;
        }

        // Validate namespace exists in cache
        if (! isset($this->namespaceCache[$row['namespace_name']])) {
            $this->stats['skipped_invalid']++;
            $this->invalidRows[] = [
                'line' => $row['line_number'],
                'idFrame' => $row['idFrame'],
                'frame_name' => $row['frame_name'],
                'namespace' => $row['namespace_name'],
                'reason' => 'Namespace not found',
            ];

            return;
        }

        $frameIdEntity = $this->frameCache[$row['idFrame']];
        $namespaceIdEntity = $this->namespaceCache[$row['namespace_name']];

        try {
            if (! $isDryRun) {
                // Delete existing namespace relations for this frame
                Criteria::table('entityrelation')
                    ->where('idEntity1', $frameIdEntity)
                    ->where('idRelationType', $this->relationType242)
                    ->delete();

                // Create new namespace relation using database function directly
                $relationData = json_encode([
                    'relationType' => 'rel_namespace',
                    'idEntity1' => $frameIdEntity,
                    'idEntity2' => $namespaceIdEntity,
                    'idEntity3' => null,
                    'idRelation' => null,
                    'idUser' => $userId,
                ]);

                DB::select('SELECT relation_create(?) as idRelation', [$relationData]);
            }

            $this->stats['processed']++;

        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'idFrame' => $row['idFrame'],
                'frame_name' => $row['frame_name'],
                'namespace' => $row['namespace_name'],
                'error' => $e->getMessage(),
            ];
        }
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Import Statistics:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->formatNumber($this->stats['total_rows'])],
            ['Successfully processed', $this->formatNumber($this->stats['processed']).($isDryRun ? ' (would be processed)' : '')],
            ['Skipped (invalid data)', $this->formatNumber($this->stats['skipped_invalid'])],
            ['Errors', $this->formatNumber($this->stats['errors'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->formatNumber($this->stats['errors'])} relationships failed due to errors");
            $this->newLine();
            $this->displayErrorDetails();
        }

        if ($this->stats['skipped_invalid'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->formatNumber($this->stats['skipped_invalid'])} rows skipped (frame or namespace not found in database)");
            $this->newLine();
            $this->displayInvalidRows();
        }
    }

    private function displayErrorDetails(): void
    {
        if (empty($this->errorDetails)) {
            return;
        }

        $this->error('🐛 Error Details (showing first 10):');
        $this->newLine();

        // Show only first 10 errors to avoid overwhelming output
        $sampleErrors = array_slice($this->errorDetails, 0, 10);

        $tableData = [];
        foreach ($sampleErrors as $error) {
            $tableData[] = [
                $error['line'],
                $error['idFrame'],
                $error['frame_name'],
                $error['namespace'],
                str_replace("\n", ' ', substr($error['error'], 0, 80)).'...',
            ];
        }

        $this->table(
            ['CSV Line', 'idFrame', 'Frame Name', 'Namespace', 'Error Message'],
            $tableData
        );

        if (count($this->errorDetails) > 10) {
            $this->line('  ... and '.(count($this->errorDetails) - 10).' more errors');
        }
    }

    private function displayInvalidRows(): void
    {
        if (empty($this->invalidRows)) {
            return;
        }

        $this->info('📋 Invalid Rows Details:');
        $this->newLine();

        $tableData = [];
        foreach ($this->invalidRows as $invalid) {
            $tableData[] = [
                $invalid['line'],
                $invalid['idFrame'],
                $invalid['frame_name'],
                $invalid['namespace'],
                $invalid['reason'],
            ];
        }

        $this->table(
            ['CSV Line', 'idFrame', 'Frame Name', 'Namespace', 'Reason'],
            $tableData
        );
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}

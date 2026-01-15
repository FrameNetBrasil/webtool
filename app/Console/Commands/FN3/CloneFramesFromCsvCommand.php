<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use App\Services\AppService;
use App\Services\Frame\ResourceService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CloneFramesFromCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:clone-frames-from-csv
                            {--csv= : Path to CSV file (default: new_frames.csv in Data directory)}
                            {--dry-run : Preview changes without modifying the database}
                            {--user=6 : User ID for frame creation operations}
                            {--verbose-errors : Show full error messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clone frames from CSV file with custom names';

    private array $stats = [
        'total_rows' => 0,
        'frames_found' => 0,
        'frames_not_found' => 0,
        'frames_cloned' => 0,
        'errors' => 0,
    ];

    private array $missingFrames = [];
    private array $errorDetails = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Frame Cloning from CSV');
        $this->newLine();

        // This command works with Portuguese frames (idLanguage = 1)
        $idLanguage = 1;

        // Set the current language context for the clone operation
        AppService::setCurrentLanguage($idLanguage);

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__.'/Data/new_frames.csv';
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user');

        // Authenticate as the specified user for the clone operation
        Auth::loginUsingId($userId);

        if (! Auth::check()) {
            $this->error("User ID {$userId} not found. Please specify a valid user ID.");

            return 1;
        }

        // Validate CSV file exists
        if (! file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  - CSV File: {$csvPath}");
        $this->line("  - Language: Portuguese (idLanguage={$idLanguage})");
        $this->line("  - User ID: {$userId}");
        if ($isDryRun) {
            $this->warn('  - DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Read CSV
        $this->info('Reading CSV file...');
        $rows = $this->readCsv($csvPath);

        if (empty($rows)) {
            $this->error('No data rows found in CSV file');

            return 1;
        }

        $this->stats['total_rows'] = count($rows);
        $this->info("Found {$this->formatNumber($this->stats['total_rows'])} rows to process");
        $this->newLine();

        // Stage 1: Verify all existing frames exist
        $this->info('Stage 1: Verifying existing frames...');
        $this->newLine();

        $frameCache = [];
        $this->withProgressBar($rows, function ($row) use (&$frameCache, $idLanguage) {
            $this->verifyFrame($row, $frameCache, $idLanguage);
        });

        $this->newLine(2);

        // Display verification results
        $this->displayVerificationStats();

        // If there are missing frames, stop
        if (count($this->missingFrames) > 0) {
            $this->newLine();
            $this->warn("Found {$this->formatNumber(count($this->missingFrames))} missing frames.");
            $this->newLine();
            $this->displayMissingFramesSample();
            $this->newLine();
            $this->error('Cannot proceed with cloning until all existing frames are found in the database.');

            return 1;
        }

        $this->newLine();
        $this->info('All existing frames verified successfully!');
        $this->newLine();

        // Stage 2: Clone frames
        $this->info('Stage 2: Cloning frames...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $frameCache, $idLanguage) {
            $this->cloneFrame($row, $isDryRun, $frameCache, $idLanguage);
        });

        $this->newLine(2);

        // Display cloning statistics
        $this->displayCloningStats($isDryRun);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->displayErrorDetails();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('Dry run completed. Use without --dry-run to clone frames.');
        } else {
            $this->newLine();
            $this->info('Frame cloning completed!');
        }

        return 0;
    }

    private function readCsv(string $csvPath): array
    {
        $rows = [];
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('Failed to open CSV file');

            return [];
        }

        // Skip header row
        fgetcsv($handle);

        $lineNumber = 2;

        // Read data rows: existing_frame,new_frame
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 2 && ! empty($data[0]) && ! empty($data[1])) {
                $rows[] = [
                    'line_number' => $lineNumber,
                    'existing_frame' => trim($data[0]),
                    'new_frame' => trim($data[1]),
                ];
            }
            $lineNumber++;
        }

        fclose($handle);

        return $rows;
    }

    private function verifyFrame(array $row, array &$frameCache, int $idLanguage): void
    {
        $existingFrameName = $row['existing_frame'];

        // Check cache first
        if (isset($frameCache[$existingFrameName])) {
            $this->stats['frames_found']++;

            return;
        }

        // Query view_frame for specified language
        $frame = Criteria::table('view_frame')
            ->where('name', $existingFrameName)
            ->where('idLanguage', $idLanguage)
            ->first();

        if ($frame) {
            $frameCache[$existingFrameName] = [
                'idFrame' => $frame->idFrame,
                'idEntity' => $frame->idEntity,
            ];
            $this->stats['frames_found']++;
        } else {
            $this->missingFrames[] = [
                'line' => $row['line_number'],
                'existing_frame' => $existingFrameName,
                'new_frame' => $row['new_frame'],
            ];
            $this->stats['frames_not_found']++;
        }
    }

    private function cloneFrame(array $row, bool $isDryRun, array $frameCache, int $idLanguage): void
    {
        $existingFrameName = $row['existing_frame'];
        $newFrameName = $row['new_frame'];

        // Get cached frame info
        $frameInfo = $frameCache[$existingFrameName] ?? null;

        if (! $frameInfo) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'existing_frame' => $existingFrameName,
                'new_frame' => $newFrameName,
                'error' => 'Frame not in cache (should not happen)',
            ];

            return;
        }

        $idFrame = $frameInfo['idFrame'];

        try {
            if (! $isDryRun) {
                // Clone the frame - returns new frame ID
                $newFrameId = ResourceService::clone((string) $idFrame);

                // Get the new frame's entity ID
                $newFrame = Criteria::table('frame')
                    ->where('idFrame', $newFrameId)
                    ->first();

                if (! $newFrame) {
                    throw new Exception("Cloned frame with idFrame={$newFrameId} not found");
                }

                // Update the frame name in entry table for specified language
                Criteria::table('entry')
                    ->where('idEntity', $newFrame->idEntity)
                    ->where('idLanguage', $idLanguage)
                    ->update([
                        'name' => $newFrameName,
                    ]);
            }

            $this->stats['frames_cloned']++;
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'existing_frame' => $existingFrameName,
                'new_frame' => $newFrameName,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function displayVerificationStats(): void
    {
        $this->info('Frame Verification Results:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->formatNumber($this->stats['total_rows'])],
            ['Frames found', $this->formatNumber($this->stats['frames_found'])],
            ['Frames not found', $this->formatNumber($this->stats['frames_not_found'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function displayCloningStats(bool $isDryRun): void
    {
        $this->info('Frame Cloning Results:');
        $this->newLine();

        $suffix = $isDryRun ? ' (would be)' : '';

        $tableData = [
            ['Total rows in CSV', $this->formatNumber($this->stats['total_rows'])],
            ['Frames cloned'.$suffix, $this->formatNumber($this->stats['frames_cloned'])],
            ['Errors', $this->formatNumber($this->stats['errors'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function displayMissingFramesSample(): void
    {
        if (empty($this->missingFrames)) {
            return;
        }

        $this->info('Missing Frames (first 20):');
        $this->newLine();

        $sample = array_slice($this->missingFrames, 0, 20);
        $tableData = [];

        foreach ($sample as $missing) {
            $tableData[] = [
                $missing['line'],
                $missing['existing_frame'],
                $missing['new_frame'],
            ];
        }

        $this->table(['CSV Line', 'Existing Frame', 'New Frame'], $tableData);

        if (count($this->missingFrames) > 20) {
            $this->line('  ... and '.(count($this->missingFrames) - 20).' more missing frames');
        }
    }

    private function displayErrorDetails(): void
    {
        if (empty($this->errorDetails)) {
            return;
        }

        $this->error('Error Details (first 10):');
        $this->newLine();

        $sample = array_slice($this->errorDetails, 0, 10);
        $tableData = [];

        foreach ($sample as $error) {
            $tableData[] = [
                $error['line'],
                $error['existing_frame'],
                $error['new_frame'],
                substr($error['error'], 0, 80),
            ];
        }

        $this->table(['CSV Line', 'Existing Frame', 'New Frame', 'Error'], $tableData);

        if (count($this->errorDetails) > 10) {
            $this->line('  ... and '.(count($this->errorDetails) - 10).' more errors');
        }
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}

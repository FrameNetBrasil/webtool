<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;

class CreateLusFromCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:create-lus-from-csv
                            {--csv= : Path to CSV file (default: combined_lus_corrected.csv in Data directory)}
                            {--create : Enable LU creation (Stage 2). Without this flag, only lemma verification is performed}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for LU creation}
                            {--output= : Output path for missing lemmas CSV (default: Data/missing_lemmas.csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Portuguese LUs from CSV file. Stage 1: Verify lemmas exist. Stage 2 (--create): Create LUs.';

    private array $stats = [
        'total_rows' => 0,
        'lemmas_found' => 0,
        'lemmas_missing' => 0,
        'lus_created' => 0,
        'lus_skipped' => 0,
        'errors' => 0,
    ];

    /** @var array<int, array{idUDPOS: int, udPOS: string, pos: string}> */
    private array $posMapping = [];

    /** @var array<string, array{idLemma: int, idUDPOS: int, udPOS: string}> */
    private array $lemmaCache = [];

    /** @var array<int, array{idFrame: int, frameName: string}> */
    private array $luFrameCache = [];

    private array $missingLemmas = [];

    private array $errorDetails = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('LU Creation from CSV');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__.'/Data/combined_lus_corrected.csv';
        $outputPath = $this->option('output') ?? __DIR__.'/Data/missing_lemmas.csv';
        $isDryRun = $this->option('dry-run');
        $isCreateMode = $this->option('create');
        $userId = (int) $this->option('user');

        // Validate CSV file exists
        if (! file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  - CSV File: {$csvPath}");
        $this->line("  - User ID: {$userId}");
        $this->line('  - Mode: '.($isCreateMode ? 'CREATE LUs (Stage 2)' : 'VERIFY LEMMAS (Stage 1)'));
        if ($isDryRun) {
            $this->warn('  - DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Load POS mapping
        $this->info('Loading POS mapping...');
        $this->loadPosMapping();
        $this->info("  - Loaded {$this->formatNumber(count($this->posMapping))} POS mappings");
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

        // Cache LU frames from source idLU values (needed for both stages)
        $this->info('Caching source LU frames...');
        $this->cacheLuFrames($rows);
        $this->info("  - Cached {$this->formatNumber(count($this->luFrameCache))} frames from source LUs");
        $this->newLine();

        // Stage 1: Verify lemmas
        $this->info('Stage 1: Verifying lemmas...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) {
            $this->verifyLemma($row);
        });

        $this->newLine(2);

        // Display lemma verification results
        $this->displayLemmaStats();

        // If there are missing lemmas, export them and stop
        if (count($this->missingLemmas) > 0) {
            $this->newLine();
            $this->warn("Found {$this->formatNumber(count($this->missingLemmas))} missing lemmas.");

            if (! $isDryRun) {
                $this->exportMissingLemmas($outputPath);
                $this->info("Missing lemmas exported to: {$outputPath}");
            } else {
                $this->info('DRY RUN: Would export missing lemmas to: '.$outputPath);
            }

            $this->newLine();
            $this->displayMissingLemmasSample();

            if ($isCreateMode) {
                $this->newLine();
                $this->error('Cannot proceed with LU creation until all lemmas exist.');
                $this->info('Please create the missing lemmas first, then run this command again.');
            }

            return 1;
        }

        $this->newLine();
        $this->info('All lemmas verified successfully!');

        // Stage 2: Create LUs (if --create flag is set)
        if (! $isCreateMode) {
            $this->newLine();
            $this->info('Stage 1 completed. Run with --create flag to create LUs.');

            return 0;
        }

        $this->newLine();
        $this->info('Stage 2: Creating LUs...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $userId) {
            $this->createLu($row, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Display creation statistics
        $this->displayCreationStats($isDryRun);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->displayErrorDetails();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('Dry run completed. Use without --dry-run to create LUs.');
        } else {
            $this->newLine();
            $this->info('LU creation completed!');
        }

        return 0;
    }

    private function loadPosMapping(): void
    {
        $mappings = Criteria::table('pos')
            ->join('pos_udpos', 'pos.idPOS', '=', 'pos_udpos.idPOS')
            ->join('udpos', 'pos_udpos.idUDPOS', '=', 'udpos.idUDPOS')
            ->select('pos.idPOS', 'pos.POS as pos', 'udpos.idUDPOS', 'udpos.POS as udPOS')
            ->get();

        foreach ($mappings as $mapping) {
            $this->posMapping[$mapping->idPOS] = [
                'idUDPOS' => $mapping->idUDPOS,
                'udPOS' => $mapping->udPOS,
                'pos' => $mapping->pos,
            ];
        }
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

        // Read data rows: lemmaName(en),lemmaName(pt),idPOS,senseDescription(pt),idLU,source_file
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 5 && ! empty($data[1]) && ! empty($data[2])) {
                $rows[] = [
                    'line_number' => $lineNumber,
                    'lemmaNameEn' => trim($data[0]),
                    'lemmaNamePt' => trim($data[1]),
                    'idPOS' => (int) trim($data[2]),
                    'senseDescription' => trim($data[3] ?? ''),
                    'idLU' => (int) trim($data[4]),
                    'sourceFile' => trim($data[5] ?? ''),
                ];
            }
            $lineNumber++;
        }

        fclose($handle);

        return $rows;
    }

    private function verifyLemma(array $row): void
    {
        $lemmaNamePt = $row['lemmaNamePt'];
        $idPOS = $row['idPOS'];

        // Get frame info from cache
        $frameInfo = $this->luFrameCache[$row['idLU']] ?? null;
        $frameName = $frameInfo['frameName'] ?? 'UNKNOWN';

        // Check if POS mapping exists
        if (! isset($this->posMapping[$idPOS])) {
            $this->missingLemmas[] = [
                'line' => $row['line_number'],
                'lemmaNamePt' => $lemmaNamePt,
                'lemmaNameEn' => $row['lemmaNameEn'],
                'idPOS' => $idPOS,
                'udPOS' => 'UNKNOWN',
                'idUDPOS' => null,
                'senseDescription' => $row['senseDescription'],
                'frameName' => $frameName,
                'reason' => "Invalid idPOS: {$idPOS}",
            ];
            $this->stats['lemmas_missing']++;

            return;
        }

        $posInfo = $this->posMapping[$idPOS];
        $idUDPOS = $posInfo['idUDPOS'];
        $udPOS = $posInfo['udPOS'];

        // Create cache key
        $cacheKey = strtolower($lemmaNamePt).'_'.$idUDPOS;

        // Check cache first
        if (isset($this->lemmaCache[$cacheKey])) {
            $this->stats['lemmas_found']++;

            return;
        }

        // Query view_lemma for Portuguese lemma
        $lemma = Criteria::table('view_lemma')
            ->where('name', $lemmaNamePt)
            ->where('idUDPOS', $idUDPOS)
            ->where('idLanguage', 1)
            ->first();

        if ($lemma) {
            $this->lemmaCache[$cacheKey] = [
                'idLemma' => $lemma->idLemma,
                'idUDPOS' => $lemma->idUDPOS,
                'udPOS' => $lemma->udPOS,
            ];
            $this->stats['lemmas_found']++;
        } else {
            $this->missingLemmas[] = [
                'line' => $row['line_number'],
                'lemmaNamePt' => $lemmaNamePt,
                'lemmaNameEn' => $row['lemmaNameEn'],
                'idPOS' => $idPOS,
                'udPOS' => $udPOS,
                'idUDPOS' => $idUDPOS,
                'senseDescription' => $row['senseDescription'],
                'frameName' => $frameName,
                'reason' => 'Lemma not found in database',
            ];
            $this->stats['lemmas_missing']++;
        }
    }

    private function cacheLuFrames(array $rows): void
    {
        $idLUs = array_unique(array_column($rows, 'idLU'));

        // First, get idFrame from source English LUs
        $luFrames = [];
        foreach (array_chunk($idLUs, 500) as $chunk) {
            $lus = Criteria::table('lu')
                ->whereIn('idLU', $chunk)
                ->select('idLU', 'idFrame')
                ->get();

            foreach ($lus as $lu) {
                $luFrames[$lu->idLU] = $lu->idFrame;
            }
        }

        // Then, get Portuguese frame names for all frames
        $frameIds = array_unique(array_values($luFrames));
        $frameNames = [];

        foreach (array_chunk($frameIds, 500) as $chunk) {
            $frames = Criteria::table('view_frame')
                ->whereIn('idFrame', $chunk)
                ->where('idLanguage', 1) // Portuguese
                ->select('idFrame', 'name')
                ->get();

            foreach ($frames as $frame) {
                $frameNames[$frame->idFrame] = $frame->name;
            }
        }

        // Build the cache with idFrame and Portuguese frame name
        foreach ($luFrames as $idLU => $idFrame) {
            $this->luFrameCache[$idLU] = [
                'idFrame' => $idFrame,
                'frameName' => $frameNames[$idFrame] ?? 'UNKNOWN',
            ];
        }
    }

    private function createLu(array $row, bool $isDryRun, int $userId): void
    {
        $lemmaNamePt = $row['lemmaNamePt'];
        $idPOS = $row['idPOS'];
        $posInfo = $this->posMapping[$idPOS];
        $idUDPOS = $posInfo['idUDPOS'];
        $pos = strtolower($posInfo['udPOS']);

        // Get cached lemma
        $cacheKey = strtolower($lemmaNamePt).'_'.$idUDPOS;
        $lemmaInfo = $this->lemmaCache[$cacheKey] ?? null;

        if (! $lemmaInfo) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'lemmaNamePt' => $lemmaNamePt,
                'error' => 'Lemma not in cache (should not happen)',
            ];

            return;
        }

        $idLemma = $lemmaInfo['idLemma'];

        // Get frame from source LU
        $frameInfo = $this->luFrameCache[$row['idLU']] ?? null;

        if (! $frameInfo) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'lemmaNamePt' => $lemmaNamePt,
                'idLU' => $row['idLU'],
                'error' => 'Source LU not found, cannot get idFrame',
            ];

            return;
        }

        $idFrame = $frameInfo['idFrame'];

        // Check if LU already exists
        $exists = Criteria::table('lu')
            ->where('idLemma', $idLemma)
            ->where('idFrame', $idFrame)
            ->first();

        if ($exists) {
            $this->stats['lus_skipped']++;

            return;
        }

        // Build LU name: lemmaName.pos (lowercase)
        $luName = strtolower($lemmaNamePt.'.'.$pos);

        // Build LU data for lu_create function
        $luData = [
            'idFrame' => $idFrame,
            'idLemma' => $idLemma,
            'name' => $luName,
            'senseDescription' => $row['senseDescription'],
            'incorporatedFE' => null,
            'status' => 'PENDING',
            'active' => 1,
            'idUser' => $userId,
            'origin' => 'FNBK',
        ];

        try {
            if (! $isDryRun) {
                Criteria::function('lu_create(?)', [json_encode($luData)]);
            }
            $this->stats['lus_created']++;
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'lemmaNamePt' => $lemmaNamePt,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function exportMissingLemmas(string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');

        if (! $handle) {
            $this->error("Failed to create output file: {$outputPath}");

            return;
        }

        // Write header
        fputcsv($handle, ['lemmaName(pt)', 'lemmaName(en)', 'idPOS', 'udPOS', 'idUDPOS', 'idLanguage', 'senseDescription(pt)', 'frameName', 'source_line', 'reason']);

        // Write missing lemmas
        foreach ($this->missingLemmas as $missing) {
            fputcsv($handle, [
                $missing['lemmaNamePt'],
                $missing['lemmaNameEn'],
                $missing['idPOS'],
                $missing['udPOS'],
                $missing['idUDPOS'],
                1, // idLanguage (Portuguese)
                $missing['senseDescription'],
                $missing['frameName'],
                $missing['line'],
                $missing['reason'],
            ]);
        }

        fclose($handle);
    }

    private function displayLemmaStats(): void
    {
        $this->info('Lemma Verification Results:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->formatNumber($this->stats['total_rows'])],
            ['Lemmas found', $this->formatNumber($this->stats['lemmas_found'])],
            ['Lemmas missing', $this->formatNumber($this->stats['lemmas_missing'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function displayCreationStats(bool $isDryRun): void
    {
        $this->info('LU Creation Results:');
        $this->newLine();

        $suffix = $isDryRun ? ' (would be)' : '';

        $tableData = [
            ['Total rows in CSV', $this->formatNumber($this->stats['total_rows'])],
            ['LUs created'.$suffix, $this->formatNumber($this->stats['lus_created'])],
            ['LUs skipped (already exist)', $this->formatNumber($this->stats['lus_skipped'])],
            ['Errors', $this->formatNumber($this->stats['errors'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function displayMissingLemmasSample(): void
    {
        if (empty($this->missingLemmas)) {
            return;
        }

        $this->info('Missing Lemmas Sample (first 15):');
        $this->newLine();

        $sample = array_slice($this->missingLemmas, 0, 15);
        $tableData = [];

        foreach ($sample as $missing) {
            $tableData[] = [
                $missing['line'],
                $missing['lemmaNamePt'],
                $missing['idPOS'],
                $missing['udPOS'],
                $missing['reason'],
            ];
        }

        $this->table(['CSV Line', 'Lemma (pt)', 'idPOS', 'udPOS', 'Reason'], $tableData);

        if (count($this->missingLemmas) > 15) {
            $this->line('  ... and '.(count($this->missingLemmas) - 15).' more missing lemmas');
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
                $error['lemmaNamePt'],
                substr($error['error'], 0, 60).'...',
            ];
        }

        $this->table(['CSV Line', 'Lemma (pt)', 'Error'], $tableData);

        if (count($this->errorDetails) > 10) {
            $this->line('  ... and '.(count($this->errorDetails) - 10).' more errors');
        }
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}

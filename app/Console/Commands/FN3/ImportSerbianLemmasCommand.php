<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;

class ImportSerbianLemmasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:import-serbian-lemmas
                            {--file=/mnt/ssd/ely/framenet/mfn/serbian/serbian-dictionary-for-framenet.txt : Path to the input file}
                            {--dry-run : Preview changes without writing to database}
                            {--language=10 : Language ID (default: 10 for Serbian)}
                            {--user=6 : User ID for lemma creation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Serbian lemmas from space-separated file (columns: lexicon, POS, lemma)';

    private array $stats = [
        'total_rows' => 0,
        'lemmas_created' => 0,
        'lemmas_skipped' => 0,
        'errors' => 0,
    ];

    private array $errorDetails = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Serbian Lemma Import');
        $this->newLine();

        // Configuration
        $filePath = $this->option('file');
        $isDryRun = $this->option('dry-run');
        $idLanguage = (int) $this->option('language');
        $idUser = (int) $this->option('user');

        // Validate file exists
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  - Input File: {$filePath}");
        $this->line("  - Language ID: {$idLanguage}");
        $this->line("  - User ID: {$idUser}");
        if ($isDryRun) {
            $this->warn('  - DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Read file
        $this->info('Reading file...');
        $rows = $this->readFile($filePath);

        if (empty($rows)) {
            $this->error('No data rows found in file');

            return 1;
        }

        $this->stats['total_rows'] = count($rows);
        $this->info("Found {$this->formatNumber($this->stats['total_rows'])} rows to process");
        $this->newLine();

        // Process lemmas
        $this->info('Creating lemmas...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $idLanguage, $idUser) {
            $this->createLemma($row, $isDryRun, $idLanguage, $idUser);
        });

        $this->newLine(2);

        // Display statistics
        $this->displayStats($isDryRun);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->displayErrorDetails();
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('Dry run completed. Use without --dry-run to create lemmas.');
        } else {
            $this->newLine();
            $this->info('Lemma import completed!');
        }

        return 0;
    }

    private function readFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            $this->error('Failed to open file');

            return [];
        }

        $lineNumber = 1;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                $lineNumber++;

                continue;
            }

            // Split by space - format: lexicon POS lemma
            $parts = preg_split('/\s+/', $line, 3);

            if (count($parts) >= 3) {
                $rows[] = [
                    'line_number' => $lineNumber,
                    'lexicon' => trim($parts[0]),
                    'pos' => trim($parts[1]),
                    'lemma' => trim($parts[2]),
                ];
            } else {
                $this->stats['errors']++;
                $this->errorDetails[] = [
                    'line' => $lineNumber,
                    'content' => $line,
                    'error' => 'Invalid format - expected 3 columns (lexicon POS lemma)',
                ];
            }

            $lineNumber++;
        }

        fclose($handle);

        return $rows;
    }

    private function createLemma(array $row, bool $isDryRun, int $idLanguage, int $idUser): void
    {
        $lemmaName = $row['lemma'];

        try {
            // Check if lemma already exists for this language
            $existing = Criteria::table('view_lemma')
                ->where('name', $lemmaName)
                ->where('idLanguage', $idLanguage)
                ->first();

            if ($existing) {
                $this->stats['lemmas_skipped']++;

                return;
            }

            // Create lemma data
            $lemmaData = [
                'name' => $lemmaName,
                'idLanguage' => $idLanguage,
                'idUser' => $idUser,
            ];

            if (! $isDryRun) {
                Criteria::function('lemma_create(?)', [json_encode($lemmaData)]);
            }

            $this->stats['lemmas_created']++;
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errorDetails[] = [
                'line' => $row['line_number'],
                'lemma' => $lemmaName,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function displayStats(bool $isDryRun): void
    {
        $this->info('Import Results:');
        $this->newLine();

        $suffix = $isDryRun ? ' (would be)' : '';

        $tableData = [
            ['Total rows in file', $this->formatNumber($this->stats['total_rows'])],
            ['Lemmas created'.$suffix, $this->formatNumber($this->stats['lemmas_created'])],
            ['Lemmas skipped (already exist)', $this->formatNumber($this->stats['lemmas_skipped'])],
            ['Errors', $this->formatNumber($this->stats['errors'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
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
                $error['lemma'] ?? $error['content'] ?? 'N/A',
                substr($error['error'], 0, 60).(strlen($error['error']) > 60 ? '...' : ''),
            ];
        }

        $this->table(['Line', 'Lemma/Content', 'Error'], $tableData);

        if (count($this->errorDetails) > 10) {
            $this->line('  ... and '.(count($this->errorDetails) - 10).' more errors');
        }
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}

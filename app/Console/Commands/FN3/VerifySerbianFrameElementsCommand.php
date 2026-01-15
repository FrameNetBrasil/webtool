<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifySerbianFrameElementsCommand extends Command
{
    protected $signature = 'fn3:verify-serbian-fe
                            {file=/mnt/ssd/ely/framenet/mfn/serbian/english-serbian-frame-elements.txt : Path to Serbian translation file}
                            {--output= : Optional path to save missing entries CSV}';

    protected $description = 'Verify if English frame element entries from Serbian translation file exist in database';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("Reading translation file: {$filePath}");

        // Parse the file
        $entries = $this->parseTranslationFile($filePath);
        $this->info('Found '.count($entries).' entries in translation file');

        // Get all existing frame element entries from database
        $this->info('Querying database for existing frame elements...');
        $existingEntries = $this->getExistingFrameElements();
        $this->info('Found '.count($existingEntries).' frame elements in database');

        // Find missing entries
        $missing = $this->findMissingEntries($entries, $existingEntries);

        // Display results
        $this->displayResults($entries, $existingEntries, $missing);

        // Optionally save to CSV
        if ($outputPath = $this->option('output')) {
            $this->saveMissingToCsv($missing, $outputPath);
            $this->info("Missing entries saved to: {$outputPath}");
        }

        return count($missing) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function parseTranslationFile(string $filePath): array
    {
        $entries = [];
        $handle = fopen($filePath, 'r');
        $lineNumber = 0;

        while (($line = fgets($handle)) !== false) {
            $lineNumber++;

            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            // Remove line number prefix if present (format: "123→")
            $line = preg_replace('/^\s*\d+→/', '', $line);

            // Split by whitespace (one or more spaces/tabs)
            $parts = preg_split('/\s+/', trim($line), 2);

            if (count($parts) >= 2) {
                [$englishEntry, $serbianEntry] = $parts;
                $entries[] = [
                    'line' => $lineNumber,
                    'english' => $englishEntry,
                    'serbian' => $serbianEntry,
                ];
            }
        }

        fclose($handle);

        return $entries;
    }

    private function getExistingFrameElements(): array
    {
        $results = DB::select('
            SELECT DISTINCT entry
            FROM frameelement
            WHERE entry IS NOT NULL
            ORDER BY entry
        ');

        // Convert to associative array for faster lookup
        $entries = [];
        foreach ($results as $row) {
            $entries[$row->entry] = true;
        }

        return $entries;
    }

    private function findMissingEntries(array $translationEntries, array $existingEntries): array
    {
        $missing = [];

        foreach ($translationEntries as $entry) {
            if (! isset($existingEntries[$entry['english']])) {
                $missing[] = $entry;
            }
        }

        return $missing;
    }

    private function displayResults(array $allEntries, array $existingEntries, array $missing): void
    {
        $totalCount = count($allEntries);
        $existingCount = $totalCount - count($missing);
        $missingCount = count($missing);

        $this->newLine();
        $this->info('=== Verification Results ===');
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total entries in file', $totalCount, '100%'],
                ['Found in database', $existingCount, round(($existingCount / $totalCount) * 100, 1).'%'],
                ['Missing from database', $missingCount, round(($missingCount / $totalCount) * 100, 1).'%'],
            ]
        );

        if ($missingCount > 0) {
            $this->newLine();
            $this->warn("=== Missing Frame Element Entries ({$missingCount} total) ===");

            // Show all missing entries
            $this->table(
                ['Line', 'English Entry', 'Serbian Translation'],
                array_map(function ($entry) {
                    return [
                        $entry['line'],
                        $entry['english'],
                        $entry['serbian'],
                    ];
                }, $missing)
            );
        } else {
            $this->newLine();
            $this->info('✓ All frame element entries from the translation file exist in the database!');
        }
    }

    private function saveMissingToCsv(array $missing, string $path): void
    {
        $handle = fopen($path, 'w');

        // Header
        fputcsv($handle, ['Line', 'English Entry', 'Serbian Translation']);

        // Data
        foreach ($missing as $entry) {
            fputcsv($handle, [
                $entry['line'],
                $entry['english'],
                $entry['serbian'],
            ]);
        }

        fclose($handle);
    }
}

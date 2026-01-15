<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;

class ExtractSerbianFrameElementWordsCommand extends Command
{
    protected $signature = 'fn3:extract-serbian-fe-words
                            {file=/mnt/ssd/ely/framenet/mfn/serbian/english-serbian-frame-elements.txt : Path to Serbian translation file}
                            {--output=storage/app/serbian_fe_words.csv : Output CSV file path}';

    protected $description = 'Extract unique word pairs from Serbian frame element translation file';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("Reading translation file: {$filePath}");

        // Parse the file and extract words
        $wordPairs = $this->extractWordPairs($filePath);
        $this->info('Found '.count($wordPairs).' unique word pairs');

        // Save to CSV
        $outputPath = $this->option('output');
        $this->saveWordPairsToCsv($wordPairs, $outputPath);
        $this->info("Word pairs saved to: {$outputPath}");

        // Display sample
        $this->displaySample($wordPairs);

        return Command::SUCCESS;
    }

    private function extractWordPairs(string $filePath): array
    {
        $wordPairs = [];
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

                // Extract the middle part using regex: fe_<word(s)>_<number>
                $englishWord = $this->extractWord($englishEntry);
                $serbianWord = $this->extractWord($serbianEntry);

                if ($englishWord !== null && $serbianWord !== null) {
                    // Use English word as key to avoid duplicates
                    $wordPairs[$englishWord] = [
                        'english' => $englishWord,
                        'serbian' => $serbianWord,
                    ];
                }
            }
        }

        fclose($handle);

        // Return as indexed array sorted by English word
        $result = array_values($wordPairs);
        usort($result, fn ($a, $b) => strcmp($a['english'], $b['english']));

        return $result;
    }

    private function extractWord(?string $entry): ?string
    {
        if ($entry === null) {
            return null;
        }

        // Pattern: fe_<word(s)>_<number>
        // Match everything between "fe_" and "_<number>"
        if (preg_match('/^fe_(.+?)_\d+$/', $entry, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function saveWordPairsToCsv(array $wordPairs, string $path): void
    {
        $handle = fopen($path, 'w');

        // Header
        fputcsv($handle, ['English', 'Serbian']);

        // Data
        foreach ($wordPairs as $pair) {
            fputcsv($handle, [
                $pair['english'],
                $pair['serbian'],
            ]);
        }

        fclose($handle);
    }

    private function displaySample(array $wordPairs): void
    {
        $this->newLine();
        $this->info('=== Sample Word Pairs (first 20) ===');

        $sample = array_slice($wordPairs, 0, 20);
        $this->table(
            ['English', 'Serbian'],
            array_map(function ($pair) {
                return [
                    $pair['english'],
                    $pair['serbian'],
                ];
            }, $sample)
        );

        $this->newLine();
        $this->info('Total unique pairs: '.count($wordPairs));
    }
}

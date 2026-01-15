<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateSerbianFramesCommand extends Command
{
    protected $signature = 'fn3:update-serbian-frames
                            {--dictionary=storage/app/serbian_frame_words.csv : Path to Serbian dictionary CSV file}
                            {--dry-run : Preview changes without updating database}';

    protected $description = 'Update Serbian frame translations in the database';

    private array $dictionary = [];

    private int $updatedCount = 0;

    private int $notFoundCount = 0;

    private int $unchangedCount = 0;

    public function handle(): int
    {
        $dictionaryPath = $this->option('dictionary');
        $dryRun = $this->option('dry-run');

        if (! file_exists($dictionaryPath)) {
            $this->error("Dictionary file not found: {$dictionaryPath}");

            return Command::FAILURE;
        }

        // Load dictionary
        $this->info("Loading dictionary from: {$dictionaryPath}");
        $this->loadDictionary($dictionaryPath);
        $this->info('Loaded '.count($this->dictionary).' dictionary entries');

        // Get Serbian entries from database
        $this->info('Querying database for Serbian frame entries...');
        $entries = $this->getSerbianFrameEntries();
        $this->info('Found '.count($entries).' Serbian entries to process');

        if ($dryRun) {
            $this->warn('=== DRY RUN MODE - No changes will be made ===');
        }

        // Process each entry
        $this->newLine();
        $this->info('Processing entries...');
        $updates = $this->processEntries($entries, $dryRun);

        // Display results
        $this->displayResults($updates, $dryRun);

        return Command::SUCCESS;
    }

    private function loadDictionary(string $path): void
    {
        $handle = fopen($path, 'r');

        // Skip header
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 2) {
                [$english, $serbian] = $row;
                // Store with lowercase key for case-insensitive lookup
                $this->dictionary[strtolower($english)] = $serbian;
            }
        }

        fclose($handle);
    }

    private function getSerbianFrameEntries(): array
    {
        return DB::select('
            SELECT e.idEntry, e.name, f.entry as frame_entry
            FROM entry e
            JOIN frame f ON (e.idEntity = f.idEntity)
            WHERE e.idLanguage = 10
            ORDER BY e.idEntry
        ');
    }

    private function processEntries(array $entries, bool $dryRun): array
    {
        $updates = [];
        $progressBar = $this->output->createProgressBar(count($entries));
        $progressBar->start();

        foreach ($entries as $entry) {
            $idEntry = $entry->idEntry;
            $currentName = $entry->name;
            $frameEntry = $entry->frame_entry;

            // Extract the English word from frame.entry (frm_<word>)
            $englishWord = $this->extractWord($frameEntry);

            if ($englishWord === null) {
                $this->notFoundCount++;
                $updates[] = [
                    'idEntry' => $idEntry,
                    'current' => $currentName,
                    'frame_entry' => $frameEntry,
                    'extracted' => null,
                    'translated' => null,
                    'status' => 'could_not_extract',
                ];
                $progressBar->advance();

                continue;
            }

            // Lowercase and lookup in dictionary
            $englishWordLower = strtolower($englishWord);
            $serbianWord = $this->dictionary[$englishWordLower] ?? null;

            if ($serbianWord === null) {
                $this->notFoundCount++;
                $updates[] = [
                    'idEntry' => $idEntry,
                    'current' => $currentName,
                    'frame_entry' => $frameEntry,
                    'extracted' => $englishWord,
                    'translated' => null,
                    'status' => 'not_found_in_dictionary',
                ];
                $progressBar->advance();

                continue;
            }

            // The new name should be just the Serbian word
            $newName = $serbianWord;

            // Check if it's different
            if ($newName === $currentName) {
                $this->unchangedCount++;
                $updates[] = [
                    'idEntry' => $idEntry,
                    'current' => $currentName,
                    'frame_entry' => $frameEntry,
                    'extracted' => $englishWord,
                    'translated' => $serbianWord,
                    'new' => $newName,
                    'status' => 'unchanged',
                ];
                $progressBar->advance();

                continue;
            }

            // Update database if not dry-run
            if (! $dryRun) {
                DB::update('UPDATE entry SET name = ? WHERE idEntry = ?', [$newName, $idEntry]);
            }

            $this->updatedCount++;
            $updates[] = [
                'idEntry' => $idEntry,
                'current' => $currentName,
                'frame_entry' => $frameEntry,
                'extracted' => $englishWord,
                'translated' => $serbianWord,
                'new' => $newName,
                'status' => 'updated',
            ];

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        return $updates;
    }

    private function extractWord(?string $entry): ?string
    {
        if ($entry === null) {
            return null;
        }

        // Pattern: frm_<word(s)>
        // Match everything after "frm_"
        if (preg_match('/^frm_(.+)$/', $entry, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function displayResults(array $updates, bool $dryRun): void
    {
        $totalCount = count($updates);

        $this->info('=== Update Results ===');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Updated', $this->updatedCount, round(($this->updatedCount / $totalCount) * 100, 1).'%'],
                ['Unchanged', $this->unchangedCount, round(($this->unchangedCount / $totalCount) * 100, 1).'%'],
                ['Not found in dictionary', $this->notFoundCount, round(($this->notFoundCount / $totalCount) * 100, 1).'%'],
                ['Total', $totalCount, '100%'],
            ]
        );

        // Show sample updates
        $updatedSamples = array_filter($updates, fn ($u) => $u['status'] === 'updated');
        if (count($updatedSamples) > 0) {
            $this->newLine();
            $this->info('=== Sample Updates (first 20) ===');
            $sample = array_slice($updatedSamples, 0, 20);
            $this->table(
                ['ID', 'Frame Entry', 'Current', 'Extracted', 'Translated', 'New'],
                array_map(function ($u) {
                    return [
                        $u['idEntry'],
                        $u['frame_entry'] ?? 'N/A',
                        $u['current'],
                        $u['extracted'],
                        $u['translated'],
                        $u['new'],
                    ];
                }, $sample)
            );
        }

        // Show not found samples
        $notFoundSamples = array_filter($updates, fn ($u) => $u['status'] === 'not_found_in_dictionary');
        if (count($notFoundSamples) > 0) {
            $this->newLine();
            $this->warn('=== Not Found in Dictionary (first 20) ===');
            $sample = array_slice($notFoundSamples, 0, 20);
            $this->table(
                ['ID', 'Frame Entry', 'Current', 'Extracted Word'],
                array_map(function ($u) {
                    return [
                        $u['idEntry'],
                        $u['frame_entry'] ?? 'N/A',
                        $u['current'],
                        $u['extracted'] ?? 'N/A',
                    ];
                }, $sample)
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('This was a DRY RUN. To apply changes, run without --dry-run flag.');
        } else {
            $this->newLine();
            $this->info("✓ Database updated successfully! {$this->updatedCount} entries were updated.");
        }
    }
}

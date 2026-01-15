<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateSerbianFrameElementsCommand extends Command
{
    protected $signature = 'fn3:update-serbian-fe
                            {--dictionary=storage/app/serbian_fe_words.csv : Path to Serbian dictionary CSV file}
                            {--dry-run : Preview changes without updating database}';

    protected $description = 'Update Serbian frame element translations in the database';

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
        $this->info('Querying database for Serbian frame element entries...');
        $entries = $this->getSerbianFrameElementEntries();
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

    private function getSerbianFrameElementEntries(): array
    {
        return DB::select('
            SELECT e.idEntry, e.name, fe.entry as fe_entry
            FROM entry e
            JOIN frameelement fe ON (e.idEntity = fe.idEntity)
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
            $feEntry = $entry->fe_entry;

            // Extract the English word from frameelement.entry (fe_<word>_<number>)
            $englishWord = $this->extractWord($feEntry);

            if ($englishWord === null) {
                $this->notFoundCount++;
                $updates[] = [
                    'idEntry' => $idEntry,
                    'current' => $currentName,
                    'fe_entry' => $feEntry,
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
                    'fe_entry' => $feEntry,
                    'extracted' => $englishWord,
                    'translated' => null,
                    'status' => 'not_found_in_dictionary',
                ];
                $progressBar->advance();

                continue;
            }

            // The new name should be just the Serbian word (no fe_ prefix or _number suffix)
            // But we need to keep the same format as entry.name (which uses underscores)
            $newName = $serbianWord;

            // Check if it's different
            if ($newName === $currentName) {
                $this->unchangedCount++;
                $updates[] = [
                    'idEntry' => $idEntry,
                    'current' => $currentName,
                    'fe_entry' => $feEntry,
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
                'fe_entry' => $feEntry,
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

        // Pattern: fe_<word(s)>_<number>
        // Match everything between "fe_" and "_<number>"
        if (preg_match('/^fe_(.+?)_\d+$/', $entry, $matches)) {
            return $matches[1];
        }

        // If no match, try to extract any word pattern
        // This handles cases where the format might be slightly different
        if (preg_match('/^fe_(.+)$/', $entry, $matches)) {
            // Remove trailing _number if present
            $word = preg_replace('/_\d+$/', '', $matches[1]);

            return $word;
        }

        return null;
    }

    private function buildEntryName(string $originalEntry, string $translatedWord): string
    {
        // Replace the middle part with the translated word, keeping the structure
        if (preg_match('/^(fe_)(.+?)(_\d+)$/', $originalEntry, $matches)) {
            return $matches[1].$translatedWord.$matches[3];
        }

        // Fallback: just return the translated word with fe_ prefix
        return 'fe_'.$translatedWord;
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
                ['ID', 'FE Entry', 'Current', 'Extracted', 'Translated', 'New'],
                array_map(function ($u) {
                    return [
                        $u['idEntry'],
                        $u['fe_entry'] ?? 'N/A',
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
                ['ID', 'FE Entry', 'Current', 'Extracted Word'],
                array_map(function ($u) {
                    return [
                        $u['idEntry'],
                        $u['fe_entry'] ?? 'N/A',
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

<?php

namespace App\Console\Commands;

use App\Database\Criteria;
use Illuminate\Console\Command;

class MigrateLexiconGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:lexicon-group {--dry-run : Preview migration without inserting data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate lexicon_group data from webtool42_db to webtool_db';

    /**
     * ID mapping: old ID => new ID.
     *
     * @var array
     */
    protected array $idMapping = [];

    /**
     * Migration statistics.
     *
     * @var array
     */
    protected array $stats = [
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('=== Lexicon Group Migration ===');
        $this->info('Source: webtool42_db.lexicon_group');
        $this->info('Target: webtool_db (using lexicon_group_create routine)');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        $this->newLine();

        // Step 1: Read source data from webtool42
        $this->info('Reading source data from webtool42_db...');
        $sourceRecords = $this->getSourceRecords();

        if (empty($sourceRecords)) {
            $this->warn('No records found in webtool42_db.lexicon_group');
            return Command::SUCCESS;
        }

        $this->stats['total'] = count($sourceRecords);
        $this->info("Found {$this->stats['total']} records to migrate");
        $this->newLine();

        // Step 2: Migrate each record
        $this->info('Starting migration...');
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->start();

        foreach ($sourceRecords as $record) {
            try {
                if ($isDryRun) {
                    $this->previewRecord($record);
                    $this->stats['skipped']++;
                } else {
                    $newId = $this->migrateRecord($record);
                    $this->idMapping[$record->idLexiconGroup] = $newId;
                    $this->stats['successful']++;
                }
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->logError($record, $e);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Step 3: Display results
        $this->displayResults($isDryRun);

        return $this->stats['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get source records from webtool42_db.
     */
    protected function getSourceRecords(): array
    {
        try {
            // Switch to webtool42 database
            Criteria::$database = 'webtool42';

            $records = Criteria::table('lexicon_group')
                ->orderBy('idLexiconGroup')
                ->get()
                ->all();

            return $records;
        } catch (\Exception $e) {
            $this->error('Failed to read from webtool42_db: ' . $e->getMessage());
            return [];
        } finally {
            // Reset to default database
            Criteria::$database = '';
        }
    }

    /**
     * Migrate a single record.
     */
    protected function migrateRecord(object $record): int
    {
        // Reset to default database (webtool)
        Criteria::$database = '';

        // Prepare JSON data for the routine
        $data = json_encode([
            'name' => $record->name,
        ]);

        // Call the lexicon_group_create routine
        $newId = Criteria::function('lexicon_group_create(?)', [$data]);

        if (!$newId) {
            throw new \Exception('lexicon_group_create returned null or 0');
        }

        return $newId;
    }

    /**
     * Preview a record in dry-run mode.
     */
    protected function previewRecord(object $record): void
    {
        // In dry-run mode, we just count it as skipped
        // Detailed preview can be added if needed
    }

    /**
     * Log an error for a failed record.
     */
    protected function logError(object $record, \Exception $e): void
    {
        $error = sprintf(
            'ID %d (name: %s) - Error: %s',
            $record->idLexiconGroup,
            $record->name,
            $e->getMessage()
        );

        // Store error for final report
        if (!isset($this->errors)) {
            $this->errors = [];
        }
        $this->errors[] = $error;
    }

    /**
     * Display migration results.
     */
    protected function displayResults(bool $isDryRun): void
    {
        $this->info('=== Migration Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records', $this->stats['total']],
                ['Successful', $this->stats['successful']],
                ['Failed', $this->stats['failed']],
                ['Skipped (Dry Run)', $this->stats['skipped']],
            ]
        );

        // Display ID mappings if not dry-run
        if (!$isDryRun && !empty($this->idMapping)) {
            $this->newLine();
            $this->info('ID Mappings (Old ID → New ID):');

            $mappingTable = [];
            foreach ($this->idMapping as $oldId => $newId) {
                $mappingTable[] = [$oldId, $newId];
            }

            $this->table(['Old ID', 'New ID'], $mappingTable);
        }

        // Display errors if any
        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach ($this->errors as $error) {
                $this->line("  - {$error}");
            }
        }

        // Final status message
        $this->newLine();
        if ($isDryRun) {
            $this->info('✓ Dry run completed - no data was inserted');
        } elseif ($this->stats['failed'] === 0) {
            $this->info('✓ Migration completed successfully!');
        } else {
            $this->warn("⚠ Migration completed with {$this->stats['failed']} errors");
        }
    }
}

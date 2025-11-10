<?php

namespace App\Console\Commands;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLexicon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:lexicon
                            {--dry-run : Preview migration without inserting data}
                            {--limit= : Limit number of records to migrate (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate lexicon forms from mfn41_db.view_wflexemelemma to webtool_db.lexicon';

    /**
     * Form mapping: form => idLexicon.
     *
     * @var array
     */
    protected array $lexiconMapping = [];

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
        'duplicate' => 0,
    ];

    /**
     * Errors encountered.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('=== Lexicon Migration (mfn41_db → webtool_db) ===');
        $this->info('Source: mfn41_db.view_wflexemelemma');
        $this->info('Target: webtool_db.lexicon (using lexicon_create routine)');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        if ($limit) {
            $this->warn("LIMIT MODE - Only processing {$limit} records");
        }

        $this->newLine();

        // Step 1: Read source data
        $this->info('Reading distinct forms from mfn41_db...');
        $sourceForms = $this->getSourceForms($limit);

        if (empty($sourceForms)) {
            $this->warn('No forms found in mfn41_db.view_wflexemelemma');
            return Command::SUCCESS;
        }

        $this->stats['total'] = count($sourceForms);
        $this->info("Found {$this->stats['total']} distinct forms to migrate");
        $this->newLine();

        // Step 2: Migrate each form
        $this->info('Starting migration...');
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->start();

        foreach ($sourceForms as $form) {
            // Skip empty forms
            if (empty($form) || trim($form) === '') {
                $this->stats['skipped']++;
                $progressBar->advance();
                continue;
            }

            try {
                if ($isDryRun) {
                    $this->stats['skipped']++;
                } else {
                    $idLexicon = $this->migrateForm($form);
                    $this->lexiconMapping[$form] = $idLexicon;
                    $this->stats['successful']++;
                }
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->logError($form, $e);
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
     * Get distinct forms from mfn41_db.
     */
    protected function getSourceForms(?int $limit): array
    {
        try {
            $query = 'SELECT DISTINCT form FROM view_wflexemelemma WHERE form IS NOT NULL AND form != ""';

            if ($limit) {
                $query .= " LIMIT {$limit}";
            }

            $results = DB::connection('mfn41')->select($query);

            return array_map(fn($row) => $row->form, $results);

        } catch (\Exception $e) {
            $this->error('Failed to read from mfn41_db: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Migrate a single form.
     */
    protected function migrateForm(string $form): int
    {
        // Reset to default database (webtool)
        Criteria::$database = '';

        // Prepare JSON data for the routine
        // Using idLexiconGroup=1 (default, likely 'lemma') and idLanguage=1 (Portuguese)
        $data = json_encode([
            'form' => $form,
            'idLexiconGroup' => 1,
        ]);

        // Call the lexicon_create routine
        // Note: This routine checks for duplicates and returns existing idLexicon if found
        $idLexicon = Criteria::function('lexicon_create(?)', [$data]);

        if (!$idLexicon) {
            throw new \Exception('lexicon_create returned null or 0');
        }

        return $idLexicon;
    }

    /**
     * Log an error for a failed form.
     */
    protected function logError(string $form, \Exception $e): void
    {
        $error = sprintf(
            'Form "%s" - Error: %s',
            $form,
            $e->getMessage()
        );

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
                ['Total Forms', $this->stats['total']],
                ['Successful', $this->stats['successful']],
                ['Failed', $this->stats['failed']],
                ['Skipped (Empty/Dry Run)', $this->stats['skipped']],
            ]
        );

        // Display sample mappings if not dry-run
        if (!$isDryRun && !empty($this->lexiconMapping)) {
            $this->newLine();
            $this->info('Sample Lexicon Mappings (Form → idLexicon):');

            $sampleSize = min(10, count($this->lexiconMapping));
            $sample = array_slice($this->lexiconMapping, 0, $sampleSize, true);

            $mappingTable = [];
            foreach ($sample as $form => $idLexicon) {
                $mappingTable[] = [$form, $idLexicon];
            }

            $this->table(['Form', 'idLexicon'], $mappingTable);

            if (count($this->lexiconMapping) > $sampleSize) {
                $this->line("... and " . (count($this->lexiconMapping) - $sampleSize) . " more");
            }
        }

        // Display errors if any
        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            $errorSample = array_slice($this->errors, 0, 10);
            foreach ($errorSample as $error) {
                $this->line("  - {$error}");
            }
            if (count($this->errors) > 10) {
                $this->line("  ... and " . (count($this->errors) - 10) . " more errors");
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

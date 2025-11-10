<?php

namespace App\Console\Commands;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLemma extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:lemma
                            {--dry-run : Preview migration without inserting data}
                            {--limit= : Limit number of records to migrate (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate lemma data from mfn41_db.lemma to webtool_db.lemma';

    /**
     * Lemma ID mapping: old_idLemma => new_idLemma.
     *
     * @var array
     */
    protected array $lemmaMapping = [];

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
     * Errors encountered.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Default user ID for audit trail.
     *
     * @var int
     */
    protected int $defaultUserId = 6;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('=== Lemma Migration (mfn41_db → webtool_db) ===');
        $this->info('Source: mfn41_db.lemma (with POS/UDPOS mapping)');
        $this->info('Target: webtool_db.lemma (using lemma_create routine)');
        $this->info('Note: Routine auto-creates lexicon entries and primary lexicon_expression');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        if ($limit) {
            $this->warn("LIMIT MODE - Only processing {$limit} records");
        }

        $this->newLine();

        // Step 1: Read source data
        $this->info('Reading lemma data from mfn41_db...');
        $sourceLemmas = $this->getSourceLemmas($limit);

        if (empty($sourceLemmas)) {
            $this->warn('No lemmas found in mfn41_db');
            return Command::SUCCESS;
        }

        $this->stats['total'] = count($sourceLemmas);
        $this->info("Found {$this->stats['total']} lemmas to migrate");
        $this->newLine();

        // Step 2: Migrate each lemma
        $this->info('Starting migration...');
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->start();

        foreach ($sourceLemmas as $lemma) {
            try {
                if ($isDryRun) {
                    $this->stats['skipped']++;
                } else {
                    $newId = $this->migrateLemma($lemma);
                    $this->lemmaMapping[$lemma->idLemma] = $newId;
                    $this->stats['successful']++;
                }
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->logError($lemma, $e);
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
     * Get source lemmas from mfn41_db with UDPOS mapping.
     */
    protected function getSourceLemmas(?int $limit): array
    {
        try {
            $query = '
                SELECT lm.idLemma, lm.idLanguage, lm.name as lemma, p2.idUDPOS
                FROM lemma lm
                JOIN pos_udpos p1 ON (lm.idPOS = p1.idPOS)
                JOIN udpos p2 ON (p1.idUDPOS = p2.idUDPOS)
                ORDER BY lm.idLemma
            ';

            if ($limit) {
                $query .= " LIMIT {$limit}";
            }

            return DB::connection('mfn41')->select($query);

        } catch (\Exception $e) {
            $this->error('Failed to read from mfn41_db: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Migrate a single lemma record.
     */
    protected function migrateLemma(object $lemma): int
    {
        // Reset to default database (webtool)
        Criteria::$database = '';

        // Remove suffix from lemma name (everything after '.')
        // E.g., "turista.n" → "turista"
        $lemmaName = $this->removeSuffix($lemma->lemma);

        // Prepare JSON data for the routine
        // Note: lemma_create auto-creates lexicon entry for the lemma name
        // and creates the primary lexicon_expression entry
        $data = json_encode([
            'name' => $lemmaName,
            'idLanguage' => $lemma->idLanguage,
            'idUDPOS' => $lemma->idUDPOS,
            'idUser' => $this->defaultUserId,
        ]);

        // Call the lemma_create routine
        $newIdLemma = Criteria::function('lemma_create(?)', [$data]);

        if (!$newIdLemma) {
            throw new \Exception('lemma_create returned null or 0');
        }

        return $newIdLemma;
    }

    /**
     * Remove suffix from lemma name (everything after '.').
     */
    protected function removeSuffix(string $lemma): string
    {
        if (str_contains($lemma, '.')) {
            return substr($lemma, 0, strpos($lemma, '.'));
        }
        return $lemma;
    }

    /**
     * Log an error for a failed lemma.
     */
    protected function logError(object $lemma, \Exception $e): void
    {
        $error = sprintf(
            'idLemma %d (name: %s) - Error: %s',
            $lemma->idLemma,
            $lemma->lemma,
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
                ['Total Lemmas', $this->stats['total']],
                ['Successful', $this->stats['successful']],
                ['Failed', $this->stats['failed']],
                ['Skipped (Dry Run)', $this->stats['skipped']],
            ]
        );

        // Display sample ID mappings if not dry-run
        if (!$isDryRun && !empty($this->lemmaMapping)) {
            $this->newLine();
            $this->info('Sample Lemma ID Mappings (Old ID → New ID):');

            $sampleSize = min(10, count($this->lemmaMapping));
            $sample = array_slice($this->lemmaMapping, 0, $sampleSize, true);

            $mappingTable = [];
            foreach ($sample as $oldId => $newId) {
                $mappingTable[] = [$oldId, $newId];
            }

            $this->table(['Old ID', 'New ID'], $mappingTable);

            if (count($this->lemmaMapping) > $sampleSize) {
                $this->line("... and " . (count($this->lemmaMapping) - $sampleSize) . " more");
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
            $this->info('Note: lemma_create also created lexicon entries and primary lexicon_expression records');
        } else {
            $this->warn("⚠ Migration completed with {$this->stats['failed']} errors");
        }
    }
}

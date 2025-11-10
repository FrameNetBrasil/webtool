<?php

namespace App\Console\Commands;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLexiconExpression extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:lexicon-expression
                            {--dry-run : Preview migration without inserting data}
                            {--limit= : Limit number of records to migrate (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate lexicon_expression data from mfn41_db.lexemeentry to webtool_db.lexicon_expression';

    /**
     * Lemma lookup cache: lemma_name => idLemma.
     *
     * @var array
     */
    protected array $lemmaCache = [];

    /**
     * Lexicon lookup cache: form => idLexicon.
     *
     * @var array
     */
    protected array $lexiconCache = [];

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
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        $this->info('=== Lexicon Expression Migration (mfn41_db → webtool_db) ===');
        $this->info('Source: mfn41_db.lexemeentry (with lemma and lexeme joins)');
        $this->info('Target: webtool_db.lexicon_expression');
        $this->info('Note: lemma_create already created primary expressions; this adds additional ones');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        if ($limit) {
            $this->warn("LIMIT MODE - Only processing {$limit} records");
        }

        $this->newLine();

        // Step 1: Read source data
        $this->info('Reading lexemeentry data from mfn41_db...');
        $sourceEntries = $this->getSourceEntries($limit);

        if (empty($sourceEntries)) {
            $this->warn('No lexemeentry records found in mfn41_db');
            return Command::SUCCESS;
        }

        $this->stats['total'] = count($sourceEntries);
        $this->info("Found {$this->stats['total']} lexicon expression records to migrate");
        $this->newLine();

        // Step 2: Migrate each entry
        $this->info('Starting migration...');
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->start();

        foreach ($sourceEntries as $entry) {
            try {
                if ($isDryRun) {
                    $this->stats['skipped']++;
                } else {
                    $this->migrateExpression($entry);
                    $this->stats['successful']++;
                }
            } catch (\Exception $e) {
                $this->stats['failed']++;
                $this->logError($entry, $e);
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
     * Get source lexemeentry records from mfn41_db.
     */
    protected function getSourceEntries(?int $limit): array
    {
        try {
            $query = '
                SELECT lm.name as lemma, lx.name as form,
                       le.headword as head, le.breakbefore, le.lexemeorder as position
                FROM lexemeentry le
                JOIN lemma lm ON (le.idLemma = lm.idLemma)
                JOIN lexeme lx ON (le.idLexeme = lx.idLexeme)
                ORDER BY le.idLexemeEntry
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
     * Migrate a single lexicon expression entry.
     */
    protected function migrateExpression(object $entry): void
    {
        Criteria::$database = '';

        // 1. Get idLemma by looking up lemma (after removing suffix)
        $lemmaName = $this->removeSuffix($entry->lemma);
        $idLemma = $this->getOrCacheLemma($lemmaName);

        if (!$idLemma) {
            throw new \Exception("Lemma '{$lemmaName}' not found in target database");
        }

        // 2. Get idLexicon for the form (lexeme name)
        $idExpression = $this->getOrCacheLexicon($entry->form);

        if (!$idExpression) {
            throw new \Exception("Lexicon entry for form '{$entry->form}' not found");
        }

        // 3. Check if this expression already exists (lemma_create creates one automatically)
        $existing = Criteria::table('lexicon_expression')
            ->where('idLemma', $idLemma)
            ->where('idExpression', $idExpression)
            ->where('position', $entry->position)
            ->first();

        if ($existing) {
            // Already exists (probably from lemma_create), skip
            $this->stats['skipped']++;
            return;
        }

        // 4. Create the lexicon_expression entry
        Criteria::create('lexicon_expression', [
            'idLemma' => $idLemma,
            'idExpression' => $idExpression,
            'position' => $entry->position,
            'head' => $entry->head ?? 0,
            'breakBefore' => $entry->breakbefore ?? 0,
        ]);
    }

    /**
     * Get or cache lemma ID by name.
     */
    protected function getOrCacheLemma(string $name): ?int
    {
        if (isset($this->lemmaCache[$name])) {
            return $this->lemmaCache[$name];
        }

        Criteria::$database = '';
        $lemma = Criteria::table('lemma as lm')
            ->join('lexicon as lx', 'lm.idLexicon', '=', 'lx.idLexicon')
            ->where('lx.form', $name)
            ->select('lm.idLemma')
            ->first();

        if ($lemma) {
            $this->lemmaCache[$name] = $lemma->idLemma;
            return $lemma->idLemma;
        }

        return null;
    }

    /**
     * Get or cache lexicon ID by form.
     */
    protected function getOrCacheLexicon(string $form): ?int
    {
        if (isset($this->lexiconCache[$form])) {
            return $this->lexiconCache[$form];
        }

        Criteria::$database = '';
        $lexicon = Criteria::table('lexicon')
            ->where('form', $form)
            ->where('idLexiconGroup', 1)
            ->select('idLexicon')
            ->first();

        if ($lexicon) {
            $this->lexiconCache[$form] = $lexicon->idLexicon;
            return $lexicon->idLexicon;
        }

        return null;
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
     * Log an error for a failed entry.
     */
    protected function logError(object $entry, \Exception $e): void
    {
        $error = sprintf(
            'Lemma "%s", Form "%s", Position %d - Error: %s',
            $entry->lemma,
            $entry->form,
            $entry->position,
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
                ['Total Entries', $this->stats['total']],
                ['Successful', $this->stats['successful']],
                ['Failed', $this->stats['failed']],
                ['Skipped (Duplicates/Dry Run)', $this->stats['skipped']],
            ]
        );

        // Display cache statistics
        if (!$isDryRun) {
            $this->newLine();
            $this->info('Cache Statistics:');
            $this->line("  Lemmas cached: " . count($this->lemmaCache));
            $this->line("  Lexicons cached: " . count($this->lexiconCache));
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

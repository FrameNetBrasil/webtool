<?php

namespace App\Console\Commands\FNJP;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLexiconExpressionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fnjp:migrate-lexicon-expression
                            {--limit= : Number of records to migrate (all if not specified)}
                            {--offset=0 : Offset for reading records}
                            {--where= : Optional WHERE condition}
                            {--dry-run : Preview migration without inserting data}
                            {--batch-size=1000 : Number of records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate lexicon expressions from FNJP database to webtool';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $offset = (int) $this->option('offset');
        $where = $this->option('where');
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('Migrating lexicon expressions from FNJP to webtool database...');
        $this->newLine();

        // Query to get forms and their lemmas
        $query = DB::connection('fnjp')
            ->table('view_wflexemelemma as wf')
            ->select('wf.form', 'wf.lemma')
            ->whereRaw('wf.lemma <> wf.form');

        if ($where) {
            $query->whereRaw($where);
        }

        $totalCount = $query->count();
        $recordsToMigrate = $limit ?? $totalCount;

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be inserted');
        }

        $this->info("Total records matching criteria: {$totalCount}");
        $this->info("Records to migrate: {$recordsToMigrate} (starting from offset: {$offset})");
        $this->info("Batch size: {$batchSize}");
        $this->newLine();

        //        if (! $dryRun && ! $this->confirm('Do you want to proceed with the migration?', true)) {
        //            $this->warn('Migration cancelled.');
        //
        //            return Command::SUCCESS;
        //        }

        $inserted = 0;
        $skipped = 0;
        $errors = 0;
        $currentOffset = $offset;
        $remainingRecords = $recordsToMigrate;

        $bar = $this->output->createProgressBar($recordsToMigrate);
        $bar->start();

        while ($remainingRecords > 0) {
            $currentBatchSize = min($batchSize, $remainingRecords);

            $expressions = DB::connection('fnjp')
                ->table('view_wflexemelemma as wf')
                ->select('wf.form', 'wf.lemma')
                ->whereRaw('wf.lemma <> wf.form')
                ->when($where, fn ($q) => $q->whereRaw($where))
                ->offset($currentOffset)
                ->limit($currentBatchSize)
                ->get();

            if ($expressions->isEmpty()) {
                break;
            }

            foreach ($expressions as $expression) {
                try {
                    if (! $dryRun) {
                        // Set database connection for Criteria
                        Criteria::$database = 'webtool';
                        $name = mb_substr($expression->lemma, 0, mb_strpos($expression->lemma, '.') - 1);

                        // Get idLemma for lemma
                        $lemma = Criteria::table('view_lemma')
                            ->where('name', $name)
                            ->first();

                        if (! $lemma) {
                            $skipped++;
                            if ($this->output->isVerbose()) {
                                $this->warn("Lemma not found: '{$name}'");
                            }
                            $bar->advance();

                            continue;
                        }

                        $idLemma = $lemma->idLemma;

                        // Get idLexicon for form
                        $wf = Criteria::table('lexicon')
                            ->where('form', $expression->form)
                            ->first();

                        if (! $wf) {
                            $skipped++;
                            if ($this->output->isVerbose()) {
                                $this->warn("Form not found: '{$expression->form}'");
                            }
                            $bar->advance();

                            continue;
                        }

                        $idLexiconForm = $wf->idLexicon;

                        $existing = Criteria::table('lexicon_expression')
                            ->where('idLemma', $idLemma)
                            ->where('idExpression', $idLexiconForm)
                            ->first();
                        if (is_null($existing)) {

                            // Insert into lexicon_expression
                            DB::connection('webtool')
                                ->table('lexicon_expression')
                                ->insert([
                                    'head' => 1,
                                    'breakBefore' => 0,
                                    'position' => 1,
                                    'idLemma' => $idLemma,
                                    'idExpression' => $idLexiconForm,
                                ]);
                        }
                    }

                    $inserted++;
                } catch (\Exception $e) {
                    $errors++;
                    //                    if ($this->output->isVerbose()) {
                    $this->error("Error inserting expression '{$expression->form}' -> '{$expression->lemma}': ".$e->getMessage());
                    //                    }
                }

                $bar->advance();
            }

            $currentOffset += $currentBatchSize;
            $remainingRecords -= $expressions->count();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Migration completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Inserted', $inserted],
                ['Skipped', $skipped],
                ['Errors', $errors],
                ['Total Processed', $inserted + $skipped + $errors],
            ]
        );

        return Command::SUCCESS;
    }
}

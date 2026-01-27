<?php

namespace App\Console\Commands\FNJP;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReadWordFormCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fnjp:migrate-wordform
                            {--limit= : Number of records to migrate (all if not specified)}
                            {--offset=0 : Offset for reading records}
                            {--where= : Optional WHERE condition (e.g., "idWordForm > 100")}
                            {--dry-run : Preview migration without inserting data}
                            {--batch-size=1000 : Number of records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate wordform table from FNJP database to webtool lexicon table';

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

        $this->info('Migrating wordform from FNJP to webtool lexicon table...');
        $this->newLine();

        $query = DB::connection('fnjp')->table('wordform');

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

        if (! $dryRun && ! $this->confirm('Do you want to proceed with the migration?', true)) {
            $this->warn('Migration cancelled.');

            return Command::SUCCESS;
        }

        $inserted = 0;
        $skipped = 0;
        $errors = 0;
        $currentOffset = $offset;
        $remainingRecords = $recordsToMigrate;

        $bar = $this->output->createProgressBar($recordsToMigrate);
        $bar->start();

        while ($remainingRecords > 0) {
            $currentBatchSize = min($batchSize, $remainingRecords);

            $wordforms = DB::connection('fnjp')
                ->table('wordform')
                ->when($where, fn ($q) => $q->whereRaw($where))
                ->offset($currentOffset)
                ->limit($currentBatchSize)
                ->get();

            if ($wordforms->isEmpty()) {
                break;
            }

            foreach ($wordforms as $wordform) {
                try {
                    if (! $dryRun) {
                        DB::connection('webtool')->table('lexicon')->insert([
                            'form' => $wordform->form,
                            'idLexiconGroup' => 1,
                        ]);
                    }
                    $inserted++;
                } catch (\Exception $e) {
                    $errors++;
                    if ($this->output->isVerbose()) {
                        $this->error("Error inserting '{$wordform->form}': ".$e->getMessage());
                    }
                }

                $bar->advance();
            }

            $currentOffset += $currentBatchSize;
            $remainingRecords -= $wordforms->count();
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

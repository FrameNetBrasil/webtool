<?php

namespace App\Console\Commands\FNJP;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLemmaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fnjp:migrate-lemma
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
    protected $description = 'Migrate lemma table from FNJP database to webtool using lemma_create function';

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

        $this->info('Migrating lemma from FNJP to webtool database...');
        $this->newLine();

        // Query to get lemmas with their UDPOS from fnjp database
        $query = DB::connection('fnjp')
            ->table('lemma as lm')
            ->leftjoin('webtool42_db.pos_udpos as udpos', 'lm.idpos', '=', 'udpos.idPOS')
            ->select('lm.name', 'udpos.idUDPos');

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

//        while ($remainingRecords > 0) {
//            $currentBatchSize = min($batchSize, $remainingRecords);

            $lemmas = DB::connection('fnjp')
                ->table('lemma as lm')
                ->leftJoin('webtool42_db.pos_udpos as udpos', 'lm.idpos', '=', 'udpos.idPOS')
                ->select('lm.name', 'udpos.idUDPos')
//                ->when($where, fn($q) => $q->whereRaw($where))
//                ->where('lm.idLemma', 11450)
//                ->offset($currentOffset)
//                ->limit($currentBatchSize)
                ->get();

//            if ($lemmas->isEmpty()) {
//                break;
//            }

            foreach ($lemmas as $lemma) {
                try {
                    if (! $dryRun) {
                        // Set database connection for Criteria
                        Criteria::$database = 'webtool';

                        // $len = mb_strlen($lemma->name);
                        // $pos = mb_strpos($lemma->name, '.');
                        // $name = mb_substr($lemma->name, 0, mb_strpos($lemma->name, '.') - 1);
                        $name = mb_strstr($lemma->name, '.', true); // Returns part before '.'

                        $existing = Criteria::table('view_lemma')
                            ->where('name', $name)
                            ->where('idLanguage', 8)
                            ->first();
                        if (is_null($existing)) {
                            // Call lemma_create function
                            $idLemma = Criteria::function('lemma_create(?)', [
                                json_encode([
                                    'name' => $name,
                                    'idLanguage' => 8,
                                    'idUser' => 6,
                                ]),
                            ]);

                            // Update the created lemma with idUDPOS
                            if (! is_null($lemma->idUDPos)) {
                                Criteria::table('lemma')
                                    ->where('idLemma', $idLemma)
                                    ->update(['idUDPOS' => $lemma->idUDPos]);
                            }
                            $inserted++;
                        }
                    }

                } catch (\Exception $e) {
                    $errors++;
                    if ($this->output->isVerbose()) {
                        $this->error("Error inserting lemma '{$lemma->name}': ".$e->getMessage());
                    }
                }

                $bar->advance();
            }

//            $currentOffset += $currentBatchSize;
//            $remainingRecords -= $lemmas->count();
//        }

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

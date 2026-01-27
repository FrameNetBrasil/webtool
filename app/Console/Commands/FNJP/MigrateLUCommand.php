<?php

namespace App\Console\Commands\FNJP;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLUCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fnjp:migrate-lu
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
    protected $description = 'Migrate Lexical Units (LUs) from FNJP database to webtool using lu_create function';

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

        $this->info('Migrating Lexical Units from FNJP to webtool database...');
        $this->newLine();

        // Query to get LUs with their frame mapping
        $query = DB::connection('fnjp')
            ->table('lu')
            ->join('frame_43 as f', 'lu.idFrame', '=', 'f.idFrame')
            ->select(
                'lu.name',
                'lu.senseDescription',
                'lu.active',
                'lu.importNum',
                'lu.incorporatedFE',
                'f.idFrame43 as idFrame'
            );

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

            $lus = DB::connection('fnjp')
                ->table('lu')
                ->join('frame_43 as f', 'lu.idFrame', '=', 'f.idFrame')
                ->select(
                    'lu.name',
                    'lu.senseDescription',
                    'lu.active',
                    'lu.importNum',
                    'lu.incorporatedFE',
                    'f.idFrame43 as idFrame'
                )
                ->when($where, fn ($q) => $q->whereRaw($where))
                ->offset($currentOffset)
                ->limit($currentBatchSize)
                ->get();

            if ($lus->isEmpty()) {
                break;
            }

            foreach ($lus as $lu) {
                try {
                    if (! $dryRun) {
                        // Set database connection for Criteria
                        Criteria::$database = 'webtool';

                        // Extract lemma name (remove suffix after '.')
                        //$lemmaName = mb_substr($lu->name, 0, mb_strpos($lu->name, '.'));
                        $lemmaName = mb_strstr($lu->name, '.', true);

                        // Find the lemma in webtool database
                        $lemma = Criteria::table('view_lemma as lemma')
                            ->where('lemma.name', $lemmaName)
                            ->where('lemma.idLanguage', 8)
                            ->select('lemma.idLemma','lemma.name')
                            ->first();

                        if (!$lemma) {
                            $skipped++;
//                            if ($this->output->isVerbose()) {
                                $this->warn("Lemma not found for LU '{$lu->name}' (lemma: '{$lemmaName}')");
//                            }
                            $bar->advance();

                            continue;
                        }

                        $existing = Criteria::table('view_lu')
                            ->where('idLemma',$lemma->idLemma)
                            ->where('idFrame',$lu->idFrame)
                            ->first();
                        if (is_null($existing)) {


                            // Create LU using lu_create function
                            $data = [
                                'name' => $lemma->name,
                                'senseDescription' => $lu->senseDescription,
                                'active' => $lu->active,
                                'importNum' => $lu->importNum,
                                'idFrame' => $lu->idFrame,
                                'idLemma' => $lemma->idLemma,
                                'status' => 'CREATED',
                                'origin' => 'WEBTOOL'
                            ];

                            Criteria::function('lu_create(?)', [json_encode($data)]);
                        }
                    }

                    $inserted++;
                } catch (\Exception $e) {
                    $errors++;
//                    if ($this->output->isVerbose()) {
                        $this->error("Error inserting LU '{$lu->name}': ".$e->getMessage());
//                    }
                }

                $bar->advance();
            }

            $currentOffset += $currentBatchSize;
            $remainingRecords -= $lus->count();
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

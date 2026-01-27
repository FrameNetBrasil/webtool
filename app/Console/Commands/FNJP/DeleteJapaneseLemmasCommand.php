<?php

namespace App\Console\Commands\FNJP;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteJapaneseLemmasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fnjp:delete-japanese-lemmas
                            {--limit= : Number of records to delete (all if not specified)}
                            {--offset=0 : Offset for reading records}
                            {--dry-run : Preview deletion without actually deleting}
                            {--batch-size=1000 : Number of records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Japanese lemmas (idLanguage = 8) using lemma_delete function';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $offset = (int) $this->option('offset');
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');

        $this->info('Deleting Japanese lemmas from webtool database...');
        $this->newLine();

        // Set database connection for Criteria
        Criteria::$database = 'webtool';

        // Query to get Japanese lemma IDs
        $query = DB::connection('webtool')
            ->table('lemma')
            ->select('idLemma')
            ->where('idLanguage', 8);

        $totalCount = $query->count();
        $recordsToDelete = $limit ?? $totalCount;

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        $this->info("Total Japanese lemmas found: {$totalCount}");
        $this->info("Records to delete: {$recordsToDelete} (starting from offset: {$offset})");
        $this->info("Batch size: {$batchSize}");
        $this->newLine();

        if (! $dryRun && ! $this->confirm('Do you want to proceed with the deletion? This action cannot be undone.', false)) {
            $this->warn('Deletion cancelled.');

            return Command::SUCCESS;
        }

        $deleted = 0;
        $errors = 0;
        $currentOffset = $offset;
        $remainingRecords = $recordsToDelete;

        $bar = $this->output->createProgressBar($recordsToDelete);
        $bar->start();

        while ($remainingRecords > 0) {
            $currentBatchSize = min($batchSize, $remainingRecords);

            $lemmas = DB::connection('webtool')
                ->table('lemma')
                ->select('idLemma')
                ->where('idLanguage', 8)
                ->offset($currentOffset)
                ->limit($currentBatchSize)
                ->get();

            if ($lemmas->isEmpty()) {
                break;
            }

            foreach ($lemmas as $lemma) {
                try {
                    if (! $dryRun) {
                        // Call lemma_delete function with idLemma and idUser = 6
                        Criteria::function('lemma_delete(?,?)', [$lemma->idLemma, 6]);
                    }

                    $deleted++;
                } catch (\Exception $e) {
                    $errors++;
                    if ($this->output->isVerbose()) {
                        $this->error("Error deleting lemma ID {$lemma->idLemma}: ".$e->getMessage());
                    }
                }

                $bar->advance();
            }

            $currentOffset += $currentBatchSize;
            $remainingRecords -= $lemmas->count();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Deletion completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Deleted', $deleted],
                ['Errors', $errors],
                ['Total Processed', $deleted + $errors],
            ]
        );

        return Command::SUCCESS;
    }
}

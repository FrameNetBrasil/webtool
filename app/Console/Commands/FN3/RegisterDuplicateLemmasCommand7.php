<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegisterDuplicateLemmasCommand7 extends Command
{
    protected $signature = 'lemma:register-duplicates7';

    protected $description = 'Register all lemmas with their minimum idLemma in lemma_temp table for refactoring';

    private array $stats = [
        'unique_names' => 0,
        'total_lemmas' => 0,
        'registrations' => 0,
    ];

    public function handle(): int
    {
        $this->info('Registering all lemmas with their minimum idLemma...');
        $this->newLine();

        // Clear existing temp data
        //        $this->clearTempTable();

        // Find and register lemmas
        $this->registerDuplicates();

        // Display summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function clearTempTable(): void
    {
        DB::table('lemma_temp')->truncate();
        $this->info('Cleared lemma_temp table');
        $this->newLine();
    }

    private function registerDuplicates(): void
    {
        // Find the minimum idLemma for each name
        $lemmaNames = DB::select("
            SELECT name, MIN(idLemma) as min
            FROM view_lemma
            WHERE (name >= 'o') and (name < 'r')
            GROUP BY name COLLATE 'utf8mb4_bin'
        ");

        $this->stats['unique_names'] = count($lemmaNames);

        if (empty($lemmaNames)) {
            $this->info('No lemmas found!');

            return;
        }

        $progressBar = $this->output->createProgressBar(count($lemmaNames));
        $progressBar->start();

        foreach ($lemmaNames as $lemmaName) {

            $temp = Criteria::table('lemma_temp')
                ->where('idLemmaMantido', $lemmaName->min)
                ->first();

            if ($temp) {
                continue;
            }

            $name = $lemmaName->name;
            $minId = $lemmaName->min;

            // Find all lemmas with this name
            $lemmas = DB::select("
                SELECT idLemma
                FROM lemma lm
                JOIN lexicon lx on (lm.idLexicon = lx.idLexicon)
                WHERE lx.form COLLATE 'utf8mb4_bin' = ?
            ", [$name]);

            $this->stats['total_lemmas'] += count($lemmas);

            // Register each lemma with the minimum id
            foreach ($lemmas as $lemma) {
                DB::table('lemma_temp')->insert([
                    'idLemmaAtual' => $lemma->idLemma,
                    'idLemmaMantido' => $minId,
                ]);
                $this->stats['registrations']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    private function displaySummary(): void
    {
        $this->info('=== Lemma Registration Summary ===');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Unique lemma names', $this->stats['unique_names']],
                ['Total lemmas', $this->stats['total_lemmas']],
                ['Registrations created', $this->stats['registrations']],
            ]
        );

        $this->newLine();
        $this->info('✓ All lemmas registered in lemma_temp table');
        $this->newLine();
        $this->comment('Note: Each lemma is paired with the minimum idLemma for its name.');
        $this->comment('idLemmaAtual = current lemma ID, idLemmaMantido = minimum ID to keep');
    }
}

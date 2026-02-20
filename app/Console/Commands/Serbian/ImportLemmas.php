<?php

namespace App\Console\Commands\Serbian;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLemmas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serbian:import-lemmas {file : Path to the Serbian lemmas file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Serbian lemmas from a tab-separated file (lemma POS)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading lemmas from: {$filePath}");

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return Command::FAILURE;
        }

        $count = 0;
        $this->output->progressStart();

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Split by tab
            $parts = explode("\t", $line);
            if (count($parts) !== 2) {
                $this->warn("Invalid line format: {$line}");
                continue;
            }

            [$lemma, $pos] = $parts;

            // Process each lemma here
            $this->processLemma($lemma, $pos);

            $count++;
            if ($count % 100 === 0) {
                $this->output->progressAdvance(100);
            }
        }

        fclose($handle);
        $this->output->progressFinish();

        $this->info("Successfully processed {$count} lemmas.");
        return Command::SUCCESS;
    }

    /**
     * Process a single lemma-POS pair
     */
    protected function processLemma(string $lemma, string $pos): void
    {
        $json = json_encode([
            'name' => $lemma,
            'idLanguage' => 10,
            'isMWE' => 0,
            'udPOS' => $pos
        ]);

        try {
            DB::select("SELECT lemma_create(?)", [$json]);
        } catch (\Exception $e) {
            $this->warn("Error creating lemma '{$lemma}': {$e->getMessage()}");
        }
    }
}

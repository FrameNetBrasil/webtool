<?php

namespace App\Console\Commands\Serbian;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLexiconExpressions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serbian:import-lexicon {file : Path to the Serbian dictionary file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Serbian lexicon expressions from dictionary file (word POS lemma)';

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

        $this->info("Reading lexicon expressions from: {$filePath}");

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

            // Split by whitespace
            $parts = preg_split('/\s+/', $line, 3);
            if (count($parts) !== 3) {
                $this->warn("Invalid line format: {$line}");
                continue;
            }

            [$word, $pos, $lemma] = $parts;

            // Process each expression
            $this->processExpression($word, $lemma);

            $count++;
            if ($count % 100 === 0) {
                $this->output->progressAdvance(100);
            }
        }

        fclose($handle);
        $this->output->progressFinish();

        $this->info("Successfully processed {$count} lexicon expressions.");
        return Command::SUCCESS;
    }

    /**
     * Process a single lexicon expression
     */
    protected function processExpression(string $form, string $lemma): void
    {
        $json = json_encode([
            'lemma' => $lemma,
            'form' => $form,
            'idLanguage' => 10
        ]);

        try {
            DB::select("SELECT lexicon_expression_create(?)", [$json]);
        } catch (\Exception $e) {
            $this->warn("Error creating expression '{$form}': {$e->getMessage()}");
        }
    }
}

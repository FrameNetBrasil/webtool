<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;

class ProcessDuplicateLUs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:process-duplicate-lus
                            {--input= : Path to input CSV file (default: app/Console/Commands/FN3/Data/lus_duplicadas.csv)}
                            {--output= : Path to output CSV file (default: app/Console/Commands/FN3/Data/lus_to_delete.csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process duplicate LUs and generate a CSV of deletable idLU values';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputFile = $this->option('input') ?: app_path('Console/Commands/FN3/Data/lus_duplicadas.csv');
        $outputFile = $this->option('output') ?: app_path('Console/Commands/FN3/Data/lus_to_delete.csv');

        if (! file_exists($inputFile)) {
            $this->error("Error: Input file not found: {$inputFile}");

            return Command::FAILURE;
        }

        // Read and parse CSV
        $handle = fopen($inputFile, 'r');
        if ($handle === false) {
            $this->error('Error: Could not open input file');

            return Command::FAILURE;
        }

        // Skip header row
        $header = fgetcsv($handle);

        // Group LUs by (idLemma, idFrame)
        $groups = [];
        $allLus = [];

        while (($row = fgetcsv($handle)) !== false) {
            $idLU = (int) $row[0];
            $idLemma = (int) $row[1];
            $idFrame = (int) $row[2];
            $frameName = $row[3];
            $lu = $row[4];
            $as = $row[5]; // annotations

            $groupKey = "{$idLemma}_{$idFrame}";

            $luData = [
                'idLU' => $idLU,
                'idLemma' => $idLemma,
                'idFrame' => $idFrame,
                'frameName' => $frameName,
                'lu' => $lu,
                'as' => $as,
                'hasAnnotations' => $as !== '-',
            ];

            $groups[$groupKey][] = $luData;
            $allLus[$idLU] = $luData;
        }

        fclose($handle);

        $this->info('Loaded '.count($allLus).' LUs in '.count($groups)." duplicate groups\n");

        // Process each group to determine deletable LUs
        $deletableLUs = [];
        $keptLUs = [];
        $statistics = [
            'totalGroups' => count($groups),
            'groupsWithAnnotations' => 0,
            'groupsWithoutAnnotations' => 0,
            'lusWithAnnotations' => 0,
            'lusWithoutAnnotations' => 0,
            'deletableLus' => 0,
            'keptLus' => 0,
        ];

        foreach ($groups as $groupKey => $groupLus) {
            // Separate LUs with and without annotations
            $withAnnotations = [];
            $withoutAnnotations = [];

            foreach ($groupLus as $lu) {
                if ($lu['hasAnnotations']) {
                    $withAnnotations[] = $lu;
                } else {
                    $withoutAnnotations[] = $lu;
                }
            }

            // Count statistics
            if (! empty($withAnnotations)) {
                $statistics['groupsWithAnnotations']++;
            } else {
                $statistics['groupsWithoutAnnotations']++;
            }

            $statistics['lusWithAnnotations'] += count($withAnnotations);
            $statistics['lusWithoutAnnotations'] += count($withoutAnnotations);

            // All LUs with annotations are kept
            foreach ($withAnnotations as $lu) {
                $keptLUs[] = $lu['idLU'];
            }

            // Among LUs without annotations, keep only the one with lowest idLU
            if (! empty($withoutAnnotations)) {
                // Sort by idLU ascending
                usort($withoutAnnotations, fn ($a, $b) => $a['idLU'] <=> $b['idLU']);

                // Keep the first one (lowest idLU)
                $keptLUs[] = $withoutAnnotations[0]['idLU'];

                // Mark the rest for deletion
                for ($i = 1; $i < count($withoutAnnotations); $i++) {
                    $deletableLUs[] = $withoutAnnotations[$i]['idLU'];
                }
            }
        }

        $statistics['deletableLus'] = count($deletableLUs);
        $statistics['keptLus'] = count($keptLUs);

        // Sort deletable LUs for consistent output
        sort($deletableLUs);

        // Write output CSV
        $outputHandle = fopen($outputFile, 'w');
        if ($outputHandle === false) {
            $this->error('Error: Could not create output file');

            return Command::FAILURE;
        }

        // Write header
        fputcsv($outputHandle, ['idLU']);

        // Write deletable LU IDs
        foreach ($deletableLUs as $idLU) {
            fputcsv($outputHandle, [$idLU]);
        }

        fclose($outputHandle);

        // Print statistics
        $this->info('=== Processing Statistics ===');
        $this->info("Total duplicate groups: {$statistics['totalGroups']}");
        $this->info("  - Groups with at least one annotation: {$statistics['groupsWithAnnotations']}");
        $this->info("  - Groups without any annotations: {$statistics['groupsWithoutAnnotations']}");
        $this->newLine();
        $this->info('Total LUs processed: '.count($allLus));
        $this->info("  - LUs with annotations (always kept): {$statistics['lusWithAnnotations']}");
        $this->info("  - LUs without annotations: {$statistics['lusWithoutAnnotations']}");
        $this->newLine();
        $this->info('Results:');
        $this->info("  - LUs to KEEP: {$statistics['keptLus']}");
        $this->info("  - LUs to DELETE: {$statistics['deletableLus']}");
        $this->newLine();
        $this->info("Output file created: {$outputFile}");

        // Show some examples
        $this->info("\n=== Sample Deletable LUs ===");
        for ($i = 0; $i < min(10, count($deletableLUs)); $i++) {
            $idLU = $deletableLUs[$i];
            $lu = $allLus[$idLU];
            $this->line("idLU={$lu['idLU']} - {$lu['lu']} (Frame: {$lu['frameName']}, as={$lu['as']})");
        }

        $this->newLine();
        $this->info('Done!');

        return Command::SUCCESS;
    }
}

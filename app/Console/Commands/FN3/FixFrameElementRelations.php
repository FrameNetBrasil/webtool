<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixFrameElementRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:frame-element-relations
                            {input? : Input CSV file name (default: dul_class_relations_mapped.csv)}
                            {output? : Output CSV file name (default: input_fixed.csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix frame entity relations CSV by replacing frame IDs with their corresponding Frame Element IDs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputFileName = $this->argument('input') ?? 'dul_class_relations_mapped.csv';
        $outputFileName = $this->argument('output');

        if (! $outputFileName) {
            $outputFileName = str_replace('.csv', '_fixed.csv', $inputFileName);
        }

        $inputFile = app_path('Console/Commands/FN3/'.$inputFileName);
        $outputFile = app_path('Console/Commands/FN3/'.$outputFileName);

        if (! file_exists($inputFile)) {
            $this->error("Input file not found: {$inputFile}");

            return self::FAILURE;
        }

        $this->info('Building Frame → Frame Element mapping...');

        // Query database to build mapping: frameIdEntity → FE idEntity
        $mapping = DB::table('view_frameelement')
            ->select('frameIdEntity', 'idEntity')
            ->where('coreType', 'cty_target')
            ->where('idLanguage', 2)
            ->get()
            ->pluck('idEntity', 'frameIdEntity')
            ->toArray();

        $this->info(sprintf('Found %d frame → FE mappings', count($mapping)));

        // Read and transform CSV
        $inputHandle = fopen($inputFile, 'r');
        $outputHandle = fopen($outputFile, 'w');

        if (! $inputHandle || ! $outputHandle) {
            $this->error('Failed to open CSV files');

            return self::FAILURE;
        }

        $lineNumber = 0;
        $transformedCount = 0;
        $unmappedFrames = [];

        while (($row = fgetcsv($inputHandle)) !== false) {
            $lineNumber++;

            // Write header as-is
            if ($lineNumber === 1) {
                fputcsv($outputHandle, $row);

                continue;
            }

            // Transform data rows
            $idRelationType = $row[0];
            $idEntity1 = $row[1];
            $idEntity2 = $row[2];

            // Map frame IDs to FE IDs
            $feEntity1 = $mapping[$idEntity1] ?? null;
            $feEntity2 = $mapping[$idEntity2] ?? null;

            // Track unmapped frames
            if ($feEntity1 === null) {
                $unmappedFrames[] = $idEntity1;
            }
            if ($feEntity2 === null) {
                $unmappedFrames[] = $idEntity2;
            }

            // Write transformed row
            if ($feEntity1 !== null && $feEntity2 !== null) {
                fputcsv($outputHandle, [$idRelationType, $feEntity1, $feEntity2]);
                $transformedCount++;
            } else {
                $this->warn(sprintf(
                    'Line %d: Skipped due to unmapped frame(s) - Entity1: %s → %s, Entity2: %s → %s',
                    $lineNumber,
                    $idEntity1,
                    $feEntity1 ?? 'UNMAPPED',
                    $idEntity2,
                    $feEntity2 ?? 'UNMAPPED'
                ));
            }
        }

        fclose($inputHandle);
        fclose($outputHandle);

        // Display results
        $this->newLine();
        $this->info(sprintf('Transformation complete!'));
        $this->info(sprintf('- Total rows processed: %d', $lineNumber - 1));
        $this->info(sprintf('- Rows transformed: %d', $transformedCount));
        $this->info(sprintf('- Rows skipped: %d', ($lineNumber - 1) - $transformedCount));

        if (! empty($unmappedFrames)) {
            $this->newLine();
            $this->warn(sprintf('Found %d unmapped frame(s):', count(array_unique($unmappedFrames))));
            foreach (array_unique($unmappedFrames) as $frameId) {
                $this->warn("  - Frame ID: {$frameId}");
            }
        }

        $this->newLine();
        $this->info("Output saved to: {$outputFile}");

        return self::SUCCESS;
    }
}

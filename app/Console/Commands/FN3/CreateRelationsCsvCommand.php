<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateRelationsCsvCommand extends Command
{
    protected $signature = 'fn3:create-relations-csv
                            {--input=app/Console/Commands/FN3/fe_property_mapping.csv : Input CSV file path}
                            {--output=app/Console/Commands/FN3/fe_relations.csv : Output CSV file path}';

    protected $description = 'Create relations CSV with three entity IDs for frame element relations';

    private array $stats = [
        'total' => 0,
        'unmapped' => 0,
        'success' => 0,
        'missing_microframe' => 0,
        'missing_target_fe' => 0,
        'ambiguous_microframe' => 0,
        'ambiguous_target_fe' => 0,
    ];

    private array $missingMicroframes = [];
    private array $missingTargetFes = [];

    public function handle(): int
    {
        $inputPath = $this->option('input');
        $outputPath = $this->option('output');

        // Make paths absolute if relative
        if (!str_starts_with($inputPath, '/')) {
            $inputPath = base_path($inputPath);
        }
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = base_path($outputPath);
        }

        if (!file_exists($inputPath)) {
            $this->error("Input CSV file not found: {$inputPath}");
            return Command::FAILURE;
        }

        $this->info('Creating relations CSV from frame element mappings...');
        $this->newLine();

        // Process the CSV
        $relations = $this->processInputCsv($inputPath);

        // Write output CSV
        $this->writeOutputCsv($outputPath, $relations);

        // Display summary
        $this->displaySummary($outputPath);

        return Command::SUCCESS;
    }

    private function processInputCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        // Find column indices
        $propertyNameIdx = array_search('property_name', $header);
        $relatedClassIdx = array_search('related_class', $header);
        $idEntityIdx = array_search('idEntity', $header);

        if ($propertyNameIdx === false || $relatedClassIdx === false || $idEntityIdx === false) {
            $this->error('Input CSV must have property_name, related_class, and idEntity columns');
            fclose($handle);
            return [];
        }

        $relations = [];
        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        while (($row = fgetcsv($handle)) !== false) {
            $this->stats['total']++;
            $progressBar->advance();

            $propertyName = $row[$propertyNameIdx] ?? '';
            $relatedClass = $row[$relatedClassIdx] ?? '';
            $idEntity2 = $row[$idEntityIdx] ?? '';

            // Skip unmapped rows
            if ($propertyName === 'UNMAPPED' || empty($propertyName)) {
                $this->stats['unmapped']++;
                continue;
            }

            // Get microframe idEntity (idEntity1)
            $idEntity1 = $this->getMicroframeEntity($propertyName);

            if ($idEntity1 === null) {
                $this->stats['missing_microframe']++;
                if (!in_array($propertyName, $this->missingMicroframes)) {
                    $this->missingMicroframes[] = $propertyName;
                }
                continue;
            }

            // Get target frame element idEntity (idEntity3)
            $idEntity3 = $this->getTargetFrameElementEntity($relatedClass);

            if ($idEntity3 === null) {
                $this->stats['missing_target_fe']++;
                if (!in_array($relatedClass, $this->missingTargetFes)) {
                    $this->missingTargetFes[] = $relatedClass;
                }
                continue;
            }

            // Success - add to relations
            $relations[] = [
                'idEntity1' => $idEntity1,
                'idEntity2' => $idEntity2,
                'idEntity3' => $idEntity3,
            ];
            $this->stats['success']++;
        }

        $progressBar->finish();
        $this->newLine(2);

        fclose($handle);

        return $relations;
    }

    private function getMicroframeEntity(string $propertyName): ?int
    {
        $results = DB::select("
            SELECT idEntity
            FROM view_microframe
            WHERE name = ?
        ", [$propertyName]);

        if (empty($results)) {
            return null;
        }

        if (count($results) > 1) {
            $this->stats['ambiguous_microframe']++;
            // Take the first one
        }

        return $results[0]->idEntity;
    }

    private function getTargetFrameElementEntity(string $relatedClass): ?int
    {
        $results = DB::select("
            SELECT fe.idEntity
            FROM frameelement fe
            JOIN view_class vc ON fe.idFrame = vc.idFrame
            WHERE vc.name = ?
            AND fe.coreType = 'cty_target'
        ", [$relatedClass]);

        if (empty($results)) {
            return null;
        }

        if (count($results) > 1) {
            $this->stats['ambiguous_target_fe']++;
            // Take the first one
        }

        return $results[0]->idEntity;
    }

    private function writeOutputCsv(string $path, array $relations): void
    {
        $handle = fopen($path, 'w');

        // Write header
        fputcsv($handle, ['idEntity1', 'idEntity2', 'idEntity3']);

        // Write data
        foreach ($relations as $relation) {
            fputcsv($handle, [
                $relation['idEntity1'],
                $relation['idEntity2'],
                $relation['idEntity3'],
            ]);
        }

        fclose($handle);

        $this->info("Relations CSV created: {$path}");
        $this->info("Total relations: " . count($relations));
    }

    private function displaySummary(string $outputPath): void
    {
        $this->newLine();
        $this->info('=== Processing Summary ===');

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total input rows', $this->stats['total'], '100%'],
                ['Unmapped (skipped)', $this->stats['unmapped'], $this->percent($this->stats['unmapped'], $this->stats['total'])],
                ['Successfully processed', $this->stats['success'], $this->percent($this->stats['success'], $this->stats['total'])],
                ['Missing microframe', $this->stats['missing_microframe'], $this->percent($this->stats['missing_microframe'], $this->stats['total'])],
                ['Missing target FE', $this->stats['missing_target_fe'], $this->percent($this->stats['missing_target_fe'], $this->stats['total'])],
            ]
        );

        if ($this->stats['ambiguous_microframe'] > 0 || $this->stats['ambiguous_target_fe'] > 0) {
            $this->newLine();
            $this->warn('=== Ambiguous Cases (first match used) ===');
            $this->table(
                ['Type', 'Count'],
                [
                    ['Microframes with multiple entities', $this->stats['ambiguous_microframe']],
                    ['Target FEs with multiple entities', $this->stats['ambiguous_target_fe']],
                ]
            );
        }

        if (count($this->missingMicroframes) > 0) {
            $this->newLine();
            $this->warn('Missing Microframes (' . count($this->missingMicroframes) . ' unique):');
            $this->line('  ' . implode(', ', array_slice($this->missingMicroframes, 0, 20)));
            if (count($this->missingMicroframes) > 20) {
                $this->line('  ... and ' . (count($this->missingMicroframes) - 20) . ' more');
            }
        }

        if (count($this->missingTargetFes) > 0) {
            $this->newLine();
            $this->warn('Missing Target Frame Elements (' . count($this->missingTargetFes) . ' unique):');
            $this->line('  ' . implode(', ', array_slice($this->missingTargetFes, 0, 20)));
            if (count($this->missingTargetFes) > 20) {
                $this->line('  ... and ' . (count($this->missingTargetFes) - 20) . ' more');
            }
        }

        $this->newLine();
        if ($this->stats['success'] === ($this->stats['total'] - $this->stats['unmapped'])) {
            $this->info('✓ All mapped rows successfully processed!');
        } else {
            $this->info("✓ Output CSV created at: {$outputPath}");
            $this->warn("⚠ Some rows could not be processed (see summary above)");
        }
    }

    private function percent(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100, 1) . '%';
    }
}

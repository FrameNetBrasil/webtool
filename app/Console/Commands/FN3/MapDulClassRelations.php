<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MapDulClassRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:map-dul-class-relations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map DUL class subsumption relationships to database entity IDs';

    private const RELATION_TYPE_ID = 2749814;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inputPath = base_path('app/Console/Commands/FN3/dul_class_subsumption.csv');
        $outputPath = base_path('app/Console/Commands/FN3/dul_class_relations_mapped.csv');

        if (!File::exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return self::FAILURE;
        }

        $this->info('Reading class subsumption relationships...');
        $relations = $this->readCSV($inputPath);

        $this->info('Looking up entity IDs in database...');
        $mappedRelations = [];
        $notFoundClasses = [];
        $successCount = 0;

        foreach ($relations as $index => $relation) {
            [$subclass, $superclass] = $relation;

            $subclassId = $this->getEntityId($subclass);
            $superclassId = $this->getEntityId($superclass);

            if ($subclassId && $superclassId) {
                $mappedRelations[] = [
                    self::RELATION_TYPE_ID,
                    $subclassId,
                    $superclassId,
                ];
                $successCount++;
            } else {
                if (!$subclassId) {
                    $notFoundClasses[$subclass] = ($notFoundClasses[$subclass] ?? 0) + 1;
                }
                if (!$superclassId) {
                    $notFoundClasses[$superclass] = ($notFoundClasses[$superclass] ?? 0) + 1;
                }
                $this->warn("Skipping relation: {$subclass} -> {$superclass} (missing entity IDs)");
            }
        }

        $this->info("Successfully mapped {$successCount} out of " . count($relations) . " relations");

        if (!empty($notFoundClasses)) {
            $this->warn("\nClasses not found in database:");
            foreach ($notFoundClasses as $className => $count) {
                $this->line("  - {$className} ({$count} occurrences)");
            }
        }

        if (empty($mappedRelations)) {
            $this->error('No relations could be mapped. Output file not created.');
            return self::FAILURE;
        }

        $this->info("\nWriting mapped relations to CSV...");
        $this->writeToCSV($mappedRelations, $outputPath);
        $this->info("Mapped relations written to: {$outputPath}");

        return self::SUCCESS;
    }

    /**
     * Get entity ID from database by class name.
     */
    private function getEntityId(string $className): ?int
    {
        $result = DB::selectOne(
            'SELECT idEntity FROM view_class WHERE idLanguage = 2 AND name = ?',
            [$className]
        );

        return $result?->idEntity;
    }

    /**
     * Read CSV file and return data rows (skipping header).
     */
    private function readCSV(string $filepath): array
    {
        $rows = [];
        $fp = fopen($filepath, 'r');

        // Skip header
        fgetcsv($fp);

        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 2) {
                $rows[] = [$row[0], $row[1]];
            }
        }

        fclose($fp);

        return $rows;
    }

    /**
     * Write relationships to CSV file.
     */
    private function writeToCSV(array $relations, string $filepath): void
    {
        $fp = fopen($filepath, 'w');

        // Write header
        fputcsv($fp, ['idRelationType', 'idEntity1', 'idEntity2']);

        // Write data
        foreach ($relations as $relation) {
            fputcsv($fp, $relation);
        }

        fclose($fp);
    }
}

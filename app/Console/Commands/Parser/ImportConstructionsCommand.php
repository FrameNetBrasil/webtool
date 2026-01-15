<?php

namespace App\Console\Commands\Parser;

use App\Services\Parser\ConstructionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportConstructionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parser:import-constructions {file? : Specific JSON file to import (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import BNF constructions from JSON files into the database';

    public function __construct(
        private readonly ConstructionService $constructionService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');
        $constructionsPath = resource_path('data/constructions');

        if ($file) {
            // Import specific file
            $filePath = $constructionsPath.'/'.$file;
            if (! File::exists($filePath)) {
                $this->error("File not found: {$filePath}");

                return Command::FAILURE;
            }

            $result = $this->importFile($filePath);

            if (is_array($result) && $result['success']) {
                $this->newLine();
                $this->info('Import complete!');
                $this->info("Total imported: {$result['imported']}");
                if ($result['skipped'] > 0) {
                    $this->warn("Total skipped (already exist): {$result['skipped']}");
                }

                return Command::SUCCESS;
            }

            return is_int($result) ? $result : Command::FAILURE;
        }

        // Import all JSON files in the directory
        if (! File::isDirectory($constructionsPath)) {
            $this->error("Constructions directory not found: {$constructionsPath}");

            return Command::FAILURE;
        }

        $files = File::glob($constructionsPath.'/*.json');

        if (empty($files)) {
            $this->warn('No JSON files found in constructions directory.');

            return Command::SUCCESS;
        }

        $this->info('Importing constructions from '.count($files).' file(s)...');

        $totalImported = 0;
        $totalSkipped = 0;

        foreach ($files as $filePath) {
            $result = $this->importFile($filePath);
            if ($result['success']) {
                $totalImported += $result['imported'];
                $totalSkipped += $result['skipped'];
            }
        }

        $this->newLine();
        $this->info('Import complete!');
        $this->info("Total imported: {$totalImported}");
        if ($totalSkipped > 0) {
            $this->warn("Total skipped (already exist): {$totalSkipped}");
        }

        return Command::SUCCESS;
    }

    private function importFile(string $filePath): array|int
    {
        $fileName = basename($filePath);
        $this->line("Processing: {$fileName}");

        try {
            $json = File::get($filePath);
            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("  Invalid JSON in {$fileName}: ".json_last_error_msg());

                return Command::FAILURE;
            }

            if (! isset($data['constructions']) || ! is_array($data['constructions'])) {
                $this->error("  No 'constructions' array found in {$fileName}");

                return Command::FAILURE;
            }

            $idGrammarGraph = $data['idGrammarGraph'] ?? 1;
            $constructions = $data['constructions'];
            $imported = 0;
            $skipped = 0;

            foreach ($constructions as $construction) {
                if (! isset($construction['name']) || ! isset($construction['pattern'])) {
                    $this->warn('  Skipping construction: missing name or pattern');

                    continue;
                }

                $name = $construction['name'];
                $pattern = $construction['pattern'];
                $metadata = [
                    'description' => $construction['description'] ?? null,
                    'semanticType' => $construction['semanticType'] ?? 'Head',
                    'semantics' => $construction['semantics'] ?? null,
                    'priority' => $construction['priority'] ?? 0,
                    'enabled' => $construction['enabled'] ?? true,
                ];

                try {
                    $this->constructionService->compileAndStore(
                        idGrammarGraph: $idGrammarGraph,
                        name: $name,
                        pattern: $pattern,
                        metadata: $metadata
                    );
                    $this->info("  ✓ Imported: {$name}");
                    $imported++;
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Duplicate entry') ||
                        str_contains($e->getMessage(), 'already exists')) {
                        $this->warn("  ⊘ Skipped (exists): {$name}");
                        $skipped++;
                    } else {
                        $this->error("  ✗ Error importing {$name}: ".$e->getMessage());
                    }
                }
            }

            $this->line("  Summary: {$imported} imported, {$skipped} skipped");

            return ['success' => true, 'imported' => $imported, 'skipped' => $skipped];
        } catch (\Exception $e) {
            $this->error("  Error processing {$fileName}: ".$e->getMessage());

            return Command::FAILURE;
        }
    }
}

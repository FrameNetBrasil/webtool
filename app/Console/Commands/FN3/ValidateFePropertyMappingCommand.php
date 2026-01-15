<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateFePropertyMappingCommand extends Command
{
    protected $signature = 'fn3:validate-fe-mapping
                            {--csv=storage/app/fe_property_mapping.csv : Path to CSV file to validate}';

    protected $description = 'Validate that property names exist in view_microframe and related classes exist in view_class';

    private array $microframeNames = [];
    private array $classNames = [];

    public function handle(): int
    {
        $csvPath = $this->option('csv');

        // Make path absolute if relative
        if (!str_starts_with($csvPath, '/')) {
            $csvPath = base_path($csvPath);
        }

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return Command::FAILURE;
        }

        $this->info('Validating frame element to property mappings...');
        $this->newLine();

        // Step 1: Load microframe names from database
        $this->info('Loading microframe names from view_microframe...');
        $this->loadMicroframeNames();
        $this->info("Loaded " . count($this->microframeNames) . " unique microframe names");

        // Step 2: Load class names from database
        $this->info('Loading class names from view_class...');
        $this->loadClassNames();
        $this->info("Loaded " . count($this->classNames) . " unique class names");

        // Step 3: Read and validate CSV
        $this->newLine();
        $this->info('Validating CSV mappings...');
        $results = $this->validateCsv($csvPath);

        // Step 4: Display results
        $this->displayResults($results);

        return Command::SUCCESS;
    }

    private function loadMicroframeNames(): void
    {
        $microframes = DB::select("SELECT DISTINCT name FROM view_microframe");

        foreach ($microframes as $mf) {
            // Store both original and normalized versions
            $this->microframeNames[$mf->name] = true;
            // Also store normalized version (lowercase, no underscores)
            $normalized = strtolower(str_replace('_', '', $mf->name));
            $this->microframeNames[$normalized] = true;
        }
    }

    private function loadClassNames(): void
    {
        $classes = DB::select("SELECT DISTINCT name FROM view_class");

        foreach ($classes as $class) {
            // Store both original and normalized versions
            $this->classNames[$class->name] = true;
            // Also store normalized version (lowercase, no spaces)
            $normalized = strtolower(str_replace(' ', '', $class->name));
            $this->classNames[$normalized] = true;
        }
    }

    private function validateCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        // Find column indices
        $propertyNameIdx = array_search('property_name', $header);
        $relatedClassIdx = array_search('related_class', $header);

        if ($propertyNameIdx === false || $relatedClassIdx === false) {
            $this->error('CSV must have property_name and related_class columns');
            fclose($handle);
            return [];
        }

        $results = [
            'total' => 0,
            'unmapped' => 0,
            'property_found' => 0,
            'property_missing' => 0,
            'class_found' => 0,
            'class_missing' => 0,
            'both_found' => 0,
            'missing_properties' => [],
            'missing_classes' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            $results['total']++;

            $propertyName = $row[$propertyNameIdx] ?? '';
            $relatedClass = $row[$relatedClassIdx] ?? '';

            // Skip unmapped rows
            if ($propertyName === 'UNMAPPED' || empty($propertyName)) {
                $results['unmapped']++;
                continue;
            }

            // Check property_name in view_microframe
            $propertyExists = $this->checkPropertyExists($propertyName);
            if ($propertyExists) {
                $results['property_found']++;
            } else {
                $results['property_missing']++;
                if (!in_array($propertyName, $results['missing_properties'])) {
                    $results['missing_properties'][] = $propertyName;
                }
            }

            // Check related_class in view_class
            $classExists = false;
            if (!empty($relatedClass)) {
                $classExists = $this->checkClassExists($relatedClass);
                if ($classExists) {
                    $results['class_found']++;
                } else {
                    $results['class_missing']++;
                    if (!in_array($relatedClass, $results['missing_classes'])) {
                        $results['missing_classes'][] = $relatedClass;
                    }
                }
            }

            // Both found
            if ($propertyExists && $classExists) {
                $results['both_found']++;
            }
        }

        fclose($handle);

        return $results;
    }

    private function checkPropertyExists(string $propertyName): bool
    {
        // Check exact match
        if (isset($this->microframeNames[$propertyName])) {
            return true;
        }

        // Check normalized version
        $normalized = strtolower(str_replace('_', '', $propertyName));
        if (isset($this->microframeNames[$normalized])) {
            return true;
        }

        return false;
    }

    private function checkClassExists(string $className): bool
    {
        // Check exact match
        if (isset($this->classNames[$className])) {
            return true;
        }

        // Check normalized version
        $normalized = strtolower(str_replace(' ', '', $className));
        if (isset($this->classNames[$normalized])) {
            return true;
        }

        return false;
    }

    private function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('=== Validation Results ===');

        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Rows', $results['total'], '100%'],
                ['Unmapped (skipped)', $results['unmapped'], $this->percent($results['unmapped'], $results['total'])],
                ['Mapped Rows', $results['total'] - $results['unmapped'], $this->percent($results['total'] - $results['unmapped'], $results['total'])],
            ]
        );

        $validRows = $results['total'] - $results['unmapped'];

        $this->newLine();
        $this->info('=== Property Name Validation (view_microframe) ===');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Found in view_microframe', $results['property_found'], $this->percent($results['property_found'], $validRows)],
                ['Missing from view_microframe', $results['property_missing'], $this->percent($results['property_missing'], $validRows)],
            ]
        );

        if (count($results['missing_properties']) > 0) {
            $this->newLine();
            $this->warn('Missing Properties (' . count($results['missing_properties']) . ' unique):');
            $chunks = array_chunk($results['missing_properties'], 10);
            foreach ($chunks as $idx => $chunk) {
                if ($idx === 0) {
                    $this->line('  ' . implode(', ', $chunk));
                } else {
                    $this->line('  ' . implode(', ', $chunk));
                }
                if ($idx >= 2) {
                    $remaining = count($results['missing_properties']) - ($idx + 1) * 10;
                    if ($remaining > 0) {
                        $this->line("  ... and {$remaining} more");
                    }
                    break;
                }
            }
        }

        $this->newLine();
        $this->info('=== Related Class Validation (view_class) ===');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Found in view_class', $results['class_found'], $this->percent($results['class_found'], $validRows)],
                ['Missing from view_class', $results['class_missing'], $this->percent($results['class_missing'], $validRows)],
            ]
        );

        if (count($results['missing_classes']) > 0) {
            $this->newLine();
            $this->warn('Missing Classes (' . count($results['missing_classes']) . ' unique):');
            $chunks = array_chunk($results['missing_classes'], 10);
            foreach ($chunks as $idx => $chunk) {
                if ($idx === 0) {
                    $this->line('  ' . implode(', ', $chunk));
                } else {
                    $this->line('  ' . implode(', ', $chunk));
                }
                if ($idx >= 2) {
                    $remaining = count($results['missing_classes']) - ($idx + 1) * 10;
                    if ($remaining > 0) {
                        $this->line("  ... and {$remaining} more");
                    }
                    break;
                }
            }
        }

        $this->newLine();
        $this->info('=== Overall Validation ===');
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Both property AND class found', $results['both_found'], $this->percent($results['both_found'], $validRows)],
            ]
        );

        // Summary
        $this->newLine();
        if ($results['property_missing'] === 0 && $results['class_missing'] === 0) {
            $this->info('✓ All mapped properties and classes are valid!');
        } else {
            if ($results['property_missing'] > 0) {
                $this->warn("⚠ {$results['property_missing']} properties not found in view_microframe");
            }
            if ($results['class_missing'] > 0) {
                $this->warn("⚠ {$results['class_missing']} classes not found in view_class");
            }
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

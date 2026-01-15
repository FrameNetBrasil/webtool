<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapFrameElementsFromCsvCommand extends Command
{
    protected $signature = 'fn3:map-fe-from-csv
                            {--output=storage/app/fe_property_mapping.csv : Output CSV file path}';

    protected $description = 'Map frame elements to DUL properties using pre-computed CSV file';

    public function handle(): int
    {
        $this->info('Mapping frame elements to DUL properties using CSV...');

        // Step 1: Load the CSV file
        $csvPath = app_path('Console/Commands/FN3/dul_class_restrictions.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return Command::FAILURE;
        }

        $this->info('Loading DUL class restrictions from CSV...');
        $restrictions = $this->loadRestrictionsCsv($csvPath);
        $this->info("Loaded " . count($restrictions) . " class restrictions");

        // Step 2: Query frame elements from database
        $this->info('Querying frame elements from database...');
        $frameElements = $this->getFrameElements();
        $this->info("Found " . count($frameElements) . " frame elements");

        // Step 3: Map frame elements to properties
        $this->info('Mapping frame elements to properties...');
        $mappings = $this->mapFrameElements($frameElements, $restrictions);

        $mappedCount = count(array_filter($mappings, fn($m) => $m['property_name'] !== null));
        $unmappedCount = count($mappings) - $mappedCount;

        $this->info("Successfully mapped: {$mappedCount}");
        $this->warn("Unmapped: {$unmappedCount}");

        // Step 4: Generate output
        $outputPath = $this->option('output');
        $this->generateCsvOutput($mappings, $outputPath);
        $this->info("Output saved to: {$outputPath}");

        // Step 5: Display summary
        $this->displaySummary($mappings);

        return Command::SUCCESS;
    }

    private function loadRestrictionsCsv(string $path): array
    {
        $restrictions = [];
        $handle = fopen($path, 'r');

        // Skip header
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) {
                continue;
            }

            [$className, $propertyName, $quantifier, $cardinality, $relatedClass] = $row;

            // Create a key for matching: class_name + related_class
            $key = $className . '::' . $relatedClass;

            // Store all properties for this combination
            if (!isset($restrictions[$key])) {
                $restrictions[$key] = [];
            }

            $restrictions[$key][] = [
                'class_name' => $className,
                'property_name' => $propertyName,
                'quantifier' => $quantifier,
                'cardinality' => $cardinality,
                'related_class' => $relatedClass,
            ];
        }

        fclose($handle);

        return $restrictions;
    }

    private function getFrameElements(): array
    {
        return DB::select("
            SELECT idFrameElement, idEntity, frameName, name
            FROM view_frameelement
            WHERE idFrame IN (SELECT idFrame FROM view_class)
            AND coreType = 'cty_property'
            AND idLanguage = 2
            ORDER BY frameName, name
        ");
    }

    private function mapFrameElements(array $frameElements, array $restrictions): array
    {
        $mappings = [];

        // Build a reverse index by class_name for faster lookup
        // Normalize class names to handle case and space differences
        $restrictionsByClass = [];
        foreach ($restrictions as $key => $restrictionList) {
            foreach ($restrictionList as $restriction) {
                $className = $restriction['class_name'];
                $normalizedClassName = strtolower(str_replace(' ', '', $className));

                if (!isset($restrictionsByClass[$normalizedClassName])) {
                    $restrictionsByClass[$normalizedClassName] = [];
                }
                $restrictionsByClass[$normalizedClassName][] = $restriction;
            }
        }

        foreach ($frameElements as $fe) {
            $frameName = $fe->frameName;
            $feName = $fe->name;

            // Normalize frame name to match the index (lowercase, no spaces)
            $normalizedFrameName = strtolower(str_replace(' ', '', $frameName));
            $classRestrictions = $restrictionsByClass[$normalizedFrameName] ?? [];

            // Try to find best matching property based on FE name
            $bestMatch = $this->findBestPropertyMatch($feName, $classRestrictions);

            if ($bestMatch) {
                $mappings[] = [
                    'idFrameElement' => $fe->idFrameElement,
                    'idEntity' => $fe->idEntity,
                    'frameName' => $frameName,
                    'feName' => $feName,
                    'property_name' => $bestMatch['property_name'],
                    'related_class' => $bestMatch['related_class'],
                    'quantifier' => $bestMatch['quantifier'],
                    'cardinality' => $bestMatch['cardinality'],
                    'match_count' => 1,
                ];
            } else {
                // No match found
                $mappings[] = [
                    'idFrameElement' => $fe->idFrameElement,
                    'idEntity' => $fe->idEntity,
                    'frameName' => $frameName,
                    'feName' => $feName,
                    'property_name' => null,
                    'related_class' => null,
                    'quantifier' => null,
                    'cardinality' => null,
                    'match_count' => 0,
                ];
            }
        }

        return $mappings;
    }

    private function findBestPropertyMatch(string $feName, array $restrictions): ?array
    {
        if (empty($restrictions)) {
            return null;
        }

        // Normalize FE name for comparison (remove underscores, lowercase)
        $normalizedFeName = strtolower(str_replace('_', '', $feName));

        // Try exact and fuzzy matching
        $bestMatch = null;
        $bestScore = 0;

        foreach ($restrictions as $restriction) {
            $propertyName = $restriction['property_name'];
            $normalizedProperty = strtolower(str_replace('_', '', $propertyName));

            $score = 0;

            // Exact match after normalization
            if ($normalizedFeName === $normalizedProperty) {
                $score = 100;
            }
            // Property contains FE name
            elseif (strpos($normalizedProperty, $normalizedFeName) !== false) {
                $score = 80;
            }
            // FE name contains property
            elseif (strpos($normalizedFeName, $normalizedProperty) !== false) {
                $score = 70;
            }
            // Check for semantic patterns
            else {
                // Map common FE names to property patterns
                $patterns = [
                    'constitution' => ['constituent', 'constitutes'],
                    'participation' => ['participant', 'participatesin'],
                    'parthood' => ['part', 'partof'],
                    'partinclusion' => ['includespart', 'haspart'],
                    'wholeinclusion' => ['includeswhole'],
                    'location' => ['location', 'locatedin', 'atlocation'],
                    'classification' => ['classifiedby', 'classifies'],
                    'temporalinterval' => ['timeinterval', 'attime'],
                    'execution' => ['executes', 'executedin'],
                    'expression' => ['expressedby', 'expresses'],
                    'definition' => ['definedin', 'defines'],
                    'satisfaction' => ['satisfies', 'satisfiedby'],
                    'setting' => ['settingfor', 'region', 'issettingfor'],
                    'overlap' => ['overlaps', 'overlapswith'],
                    'sequence' => ['precedes', 'follows', 'directlyprecedes'],
                    'region' => ['region', 'regionfor', 'hasregion'],
                    'membership' => ['member', 'memberof'],
                    'introduction' => ['introduces', 'introducedby'],
                    'description' => ['describes', 'describedby'],
                    'composition' => ['component', 'composedof'],
                    'proxyagency' => ['actsfor', 'actsthrough'],
                    'quale' => ['quale', 'qualeof', 'qualityof', 'isqualityof'],
                    'roledefinition' => ['definesrole'],
                    'taskdefinition' => ['definestask', 'taskdefinedin', 'istaskdefinedin'],
                    'taskattribution' => ['taskof', 'istaskof'],
                    'eventinclusion' => ['includesevent', 'includesevents'],
                    'objectinclusion' => ['includesobject', 'includesobjects'],
                    'parametricspecification' => ['parametrizes', 'parametrize', 'hasparameter'],
                ];

                if (isset($patterns[$normalizedFeName])) {
                    foreach ($patterns[$normalizedFeName] as $pattern) {
                        if (strpos($normalizedProperty, $pattern) !== false || strpos($pattern, $normalizedProperty) !== false) {
                            $score = 90;
                            break;
                        }
                    }
                }

                // Fuzzy similarity as last resort
                if ($score === 0) {
                    similar_text($normalizedFeName, $normalizedProperty, $percent);
                    if ($percent > 60) {
                        $score = (int)($percent * 0.5);
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $restriction;
            }
        }

        // Only return matches with score >= 50
        return $bestScore >= 50 ? $bestMatch : null;
    }

    private function generateCsvOutput(array $mappings, string $path): void
    {
        $handle = fopen($path, 'w');

        // Header
        fputcsv($handle, [
            'idFrameElement',
            'idEntity',
            'frameName',
            'feName',
            'property_name',
            'related_class',
            'quantifier',
            'cardinality',
            'match_count',
        ]);

        // Data
        foreach ($mappings as $mapping) {
            fputcsv($handle, [
                $mapping['idFrameElement'],
                $mapping['idEntity'],
                $mapping['frameName'],
                $mapping['feName'],
                $mapping['property_name'] ?? 'UNMAPPED',
                $mapping['related_class'],
                $mapping['quantifier'] ?? '',
                $mapping['cardinality'] ?? '',
                $mapping['match_count'],
            ]);
        }

        fclose($handle);
    }

    private function displaySummary(array $mappings): void
    {
        $mapped = array_filter($mappings, fn($m) => $m['property_name'] !== null);
        $unmapped = array_filter($mappings, fn($m) => $m['property_name'] === null);

        $this->newLine();
        $this->info('=== Mapping Summary ===');
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Total Frame Elements', count($mappings), '100%'],
                ['Successfully Mapped', count($mapped), round((count($mapped) / count($mappings)) * 100, 1) . '%'],
                ['Unmapped', count($unmapped), round((count($unmapped) / count($mappings)) * 100, 1) . '%'],
            ]
        );

        // Show sample mappings
        if (count($mapped) > 0) {
            $this->newLine();
            $this->info('=== Sample Mappings (first 15) ===');
            $sample = array_slice($mapped, 0, 15);
            $this->table(
                ['Frame', 'FE Name', 'Property Name', 'Related Class', 'Quantifier'],
                array_map(function ($row) {
                    return [
                        $row['frameName'],
                        $row['feName'],
                        $row['property_name'],
                        $row['related_class'],
                        $row['quantifier'],
                    ];
                }, $sample)
            );
        }

        // Show unmapped examples
        if (count($unmapped) > 0) {
            $this->newLine();
            $this->warn('=== Unmapped Examples (first 10) ===');
            $sample = array_slice($unmapped, 0, 10);
            $this->table(
                ['Frame', 'FE Name'],
                array_map(function ($row) {
                    return [
                        $row['frameName'],
                        $row['feName'],
                    ];
                }, $sample)
            );
        }

        // Property distribution
        $this->newLine();
        $this->info('=== Top 10 Most Common Properties ===');
        $propertyCount = [];
        foreach ($mapped as $m) {
            $prop = $m['property_name'];
            if (!isset($propertyCount[$prop])) {
                $propertyCount[$prop] = 0;
            }
            $propertyCount[$prop]++;
        }
        arsort($propertyCount);
        $topProps = array_slice($propertyCount, 0, 10, true);

        $this->table(
            ['Property Name', 'Count'],
            array_map(function ($prop, $count) {
                return [$prop, $count];
            }, array_keys($topProps), $topProps)
        );
    }
}

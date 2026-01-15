<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MapFrameElementsToPropertiesCommand extends Command
{
    protected $signature = 'fn3:map-fe-to-properties
                            {--format=both : Output format: csv, json, or both}';

    protected $description = 'Map frame elements to DUL object properties and generate report';

    private array $classPropertyMap = [];
    private array $results = [];
    private array $unmapped = [];

    public function handle(): int
    {
        $this->info('Starting Frame Element to Object Property mapping...');

        // Step 1: Parse DUL.jsonld
        $this->info('Parsing DUL.jsonld ontology...');
        if (!$this->parseDulOntology()) {
            $this->error('Failed to parse DUL.jsonld');
            return Command::FAILURE;
        }
        $this->info("Parsed {$this->countMappings()} property mappings for classes");

        // Step 2: Query frame elements
        $this->info('Querying frame elements from database...');
        $frameElements = $this->getFrameElements();
        $this->info("Found " . count($frameElements) . " frame elements to map");

        // Step 3: Map frame elements to properties
        $this->info('Mapping frame elements to object properties...');
        $this->progressBar = $this->output->createProgressBar(count($frameElements));
        $this->progressBar->start();

        foreach ($frameElements as $fe) {
            $this->mapFrameElement($fe);
            $this->progressBar->advance();
        }

        $this->progressBar->finish();
        $this->newLine(2);

        // Step 4: Generate reports
        $this->generateReports();

        // Step 5: Display summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function parseDulOntology(): bool
    {
        $dulPath = app_path('Console/Commands/FN3/DUL.jsonld');

        if (!file_exists($dulPath)) {
            $this->error("DUL.jsonld not found at: {$dulPath}");
            return false;
        }

        $content = file_get_contents($dulPath);
        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            $this->error('Invalid JSON-LD structure');
            return false;
        }

        // The JSON-LD is a plain array, check if it has @graph or is a direct array
        $nodes = isset($data['@graph']) ? $data['@graph'] : $data;

        // Build index of all nodes by @id
        $nodeIndex = [];
        foreach ($nodes as $node) {
            if (isset($node['@id'])) {
                $nodeIndex[$node['@id']] = $node;
            }
        }

        // Extract classes and their restrictions
        foreach ($nodes as $node) {
            if (!$this->isOwlClass($node)) {
                continue;
            }

            $className = $this->extractLocalName($node['@id']);
            $this->classPropertyMap[$className] = [];

            // Get restrictions from subClassOf
            if (isset($node['http://www.w3.org/2000/01/rdf-schema#subClassOf'])) {
                $subClassOf = $node['http://www.w3.org/2000/01/rdf-schema#subClassOf'];

                foreach ($subClassOf as $parent) {
                    $parentId = is_array($parent) ? ($parent['@id'] ?? null) : null;

                    if ($parentId && isset($nodeIndex[$parentId])) {
                        $restriction = $nodeIndex[$parentId];

                        if ($this->isRestriction($restriction)) {
                            $propertyInfo = $this->extractPropertyFromRestriction($restriction, $nodeIndex);
                            if ($propertyInfo) {
                                $this->classPropertyMap[$className][] = $propertyInfo;
                            }
                        }
                    }
                }
            }
        }

        // Also add properties by domain/range declarations
        foreach ($nodes as $node) {
            if (!$this->isObjectProperty($node)) {
                continue;
            }

            $propertyName = $this->extractLocalName($node['@id']);
            $propertyUri = $node['@id'];

            // Get domain (which classes can have this property)
            $domains = [];
            if (isset($node['http://www.w3.org/2000/01/rdf-schema#domain'])) {
                foreach ($node['http://www.w3.org/2000/01/rdf-schema#domain'] as $domain) {
                    $domainUri = is_array($domain) ? ($domain['@id'] ?? null) : null;
                    if ($domainUri) {
                        $domains[] = $this->extractLocalName($domainUri);
                    }
                }
            }

            // Get range (what the property points to)
            $rangeUri = null;
            $rangeName = null;
            if (isset($node['http://www.w3.org/2000/01/rdf-schema#range'])) {
                $range = $node['http://www.w3.org/2000/01/rdf-schema#range'][0] ?? null;
                $rangeUri = is_array($range) ? ($range['@id'] ?? null) : null;
                $rangeName = $rangeUri ? $this->extractLocalName($rangeUri) : null;
            }

            // Add this property to all classes in its domain
            foreach ($domains as $className) {
                if (!isset($this->classPropertyMap[$className])) {
                    $this->classPropertyMap[$className] = [];
                }

                // Check if this property isn't already captured from restrictions
                $alreadyExists = false;
                foreach ($this->classPropertyMap[$className] as $existingProp) {
                    if ($existingProp['propertyUri'] === $propertyUri) {
                        $alreadyExists = true;
                        break;
                    }
                }

                if (!$alreadyExists) {
                    $this->classPropertyMap[$className][] = [
                        'propertyUri' => $propertyUri,
                        'propertyName' => $propertyName,
                        'rangeUri' => $rangeUri,
                        'rangeName' => $rangeName,
                    ];
                }
            }
        }

        return true;
    }

    private function isOwlClass(array $node): bool
    {
        if (!isset($node['@type'])) {
            return false;
        }

        $types = is_array($node['@type']) ? $node['@type'] : [$node['@type']];

        return in_array('http://www.w3.org/2002/07/owl#Class', $types);
    }

    private function isRestriction(array $node): bool
    {
        if (!isset($node['@type'])) {
            return false;
        }

        $types = is_array($node['@type']) ? $node['@type'] : [$node['@type']];

        return in_array('http://www.w3.org/2002/07/owl#Restriction', $types);
    }

    private function isObjectProperty(array $node): bool
    {
        if (!isset($node['@type'])) {
            return false;
        }

        $types = is_array($node['@type']) ? $node['@type'] : [$node['@type']];

        return in_array('http://www.w3.org/2002/07/owl#ObjectProperty', $types);
    }

    private function extractPropertyFromRestriction(array $restriction, array $nodeIndex): ?array
    {
        $propertyUri = null;
        $rangeUri = null;

        // Get onProperty
        if (isset($restriction['http://www.w3.org/2002/07/owl#onProperty'])) {
            $onProp = $restriction['http://www.w3.org/2002/07/owl#onProperty'][0] ?? null;
            $propertyUri = is_array($onProp) ? ($onProp['@id'] ?? null) : null;
        }

        if (!$propertyUri) {
            return null;
        }

        // Get range (someValuesFrom or allValuesFrom)
        if (isset($restriction['http://www.w3.org/2002/07/owl#someValuesFrom'])) {
            $range = $restriction['http://www.w3.org/2002/07/owl#someValuesFrom'][0] ?? null;
            $rangeUri = is_array($range) ? ($range['@id'] ?? null) : null;
        } elseif (isset($restriction['http://www.w3.org/2002/07/owl#allValuesFrom'])) {
            $range = $restriction['http://www.w3.org/2002/07/owl#allValuesFrom'][0] ?? null;
            $rangeUri = is_array($range) ? ($range['@id'] ?? null) : null;
        }

        return [
            'propertyUri' => $propertyUri,
            'propertyName' => $this->extractLocalName($propertyUri),
            'rangeUri' => $rangeUri,
            'rangeName' => $rangeUri ? $this->extractLocalName($rangeUri) : null,
        ];
    }

    private function extractLocalName(string $uri): string
    {
        // Extract the part after # or the last /
        if (str_contains($uri, '#')) {
            return substr($uri, strrpos($uri, '#') + 1);
        }

        return substr($uri, strrpos($uri, '/') + 1);
    }

    private function getFrameElements()
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

    private function mapFrameElement($frameElement): void
    {
        $frameName = $frameElement->frameName;
        $feName = $frameElement->name;

        // Normalize class name (remove spaces for matching)
        $normalizedFrameName = str_replace(' ', '', $frameName);

        // Look up properties for this class - try exact match first, then normalized
        $properties = null;
        if (isset($this->classPropertyMap[$frameName])) {
            $properties = $this->classPropertyMap[$frameName];
        } elseif (isset($this->classPropertyMap[$normalizedFrameName])) {
            $properties = $this->classPropertyMap[$normalizedFrameName];
        }

        if ($properties === null) {
            $this->unmapped[] = [
                'idFrameElement' => $frameElement->idFrameElement,
                'idEntity' => $frameElement->idEntity,
                'frameName' => $frameName,
                'feName' => $feName,
                'reason' => 'Class not found in ontology',
            ];
            return;
        }

        $match = $this->findBestMatch($feName, $properties, $frameName);

        if ($match) {
            $this->results[] = [
                'idFrameElement' => $frameElement->idFrameElement,
                'idEntity' => $frameElement->idEntity,
                'frameName' => $frameName,
                'feName' => $feName,
                'objectProperty' => $match['propertyName'],
                'propertyUri' => $match['propertyUri'],
                'propertyRange' => $match['rangeName'],
                'rangeUri' => $match['rangeUri'],
                'matchType' => $match['matchType'],
                'confidence' => $match['confidence'],
            ];
        } else {
            $this->unmapped[] = [
                'idFrameElement' => $frameElement->idFrameElement,
                'idEntity' => $frameElement->idEntity,
                'frameName' => $frameName,
                'feName' => $feName,
                'reason' => 'No matching property found',
                'availableProperties' => implode(', ', array_column($properties, 'propertyName')),
            ];
        }
    }

    private function findBestMatch(string $feName, array $properties, string $frameName): ?array
    {
        $bestMatch = null;
        $bestConfidence = 0;

        // Common FE name to property patterns mapping
        $commonPatterns = [
            'Constitution' => ['hasConstituent', 'constitutes'],
            'Participation' => ['hasParticipant', 'isParticipantIn', 'participatesIn'],
            'Location' => ['hasLocation', 'isLocationOf', 'atLocation'],
            'Parthood' => ['hasPart', 'isPartOf'],
            'Classification' => ['classifies', 'isClassifiedBy', 'hasClassification'],
            'Temporal_interval' => ['atTime', 'hasTimeInterval'],
            'Execution' => ['executesTask', 'isExecutedIn'],
            'Expression' => ['expresses', 'isExpressedBy'],
            'Definition' => ['defines', 'isDefinedIn'],
            'Satisfaction' => ['satisfies', 'isSatisfiedBy'],
            'Setting' => ['hasRegion', 'isRegionFor', 'isSettingFor'],
            'Overlap' => ['overlaps', 'overlapsWith'],
            'Sequence' => ['precedes', 'follows', 'directlyPrecedes'],
            'Region' => ['hasRegion', 'isRegionFor'],
            'Membership' => ['hasMember', 'isMemberOf'],
            'Introduction' => ['introduces', 'isIntroducedBy'],
            'Description' => ['describes', 'isDescribedBy'],
            'Composition' => ['hasComponent'],
            'Proxy_agency' => ['actsFor', 'actsThrough'],
            'Quale' => ['hasQuale', 'isQualeOf'],
            'Role_definition' => ['definesRole'],
            'Task_definition' => ['definesTask'],
            'Task_attribution' => ['isTaskOf'],
            'Parametric_specification' => ['hasParameter'],
        ];

        foreach ($properties as $property) {
            $confidence = 0;
            $matchType = '';

            // Check common patterns first
            if (isset($commonPatterns[$feName])) {
                foreach ($commonPatterns[$feName] as $pattern) {
                    if (strcasecmp($pattern, $property['propertyName']) === 0) {
                        $confidence = 95;
                        $matchType = 'common_pattern';
                        break;
                    }
                }
            }

            // Exact property name match (case-insensitive)
            if ($confidence === 0 && strcasecmp($feName, $property['propertyName']) === 0) {
                $confidence = 100;
                $matchType = 'exact_property_name';
            }
            // Property name contains FE name
            elseif ($confidence === 0 && stripos($property['propertyName'], $feName) !== false) {
                $confidence = 80;
                $matchType = 'property_contains_fe';
            }
            // Range name exact match
            elseif ($confidence === 0 && $property['rangeName'] && strcasecmp($feName, $property['rangeName']) === 0) {
                $confidence = 90;
                $matchType = 'exact_range_name';
            }
            // Range name contains FE name
            elseif ($confidence === 0 && $property['rangeName'] && stripos($property['rangeName'], $feName) !== false) {
                $confidence = 70;
                $matchType = 'range_contains_fe';
            }
            // FE name contains property name
            elseif ($confidence === 0 && stripos($feName, $property['propertyName']) !== false) {
                $confidence = 60;
                $matchType = 'fe_contains_property';
            }
            // Fuzzy match (similar words)
            elseif ($confidence === 0) {
                $similarity = 0;
                similar_text(strtolower($feName), strtolower($property['propertyName']), $similarity);
                if ($similarity > 60) {
                    $confidence = (int)($similarity * 0.5);
                    $matchType = 'fuzzy_property';
                }

                if ($property['rangeName']) {
                    similar_text(strtolower($feName), strtolower($property['rangeName']), $similarity);
                    if ($similarity > 60 && $similarity * 0.4 > $confidence) {
                        $confidence = (int)($similarity * 0.4);
                        $matchType = 'fuzzy_range';
                    }
                }
            }

            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $bestMatch = array_merge($property, [
                    'matchType' => $matchType,
                    'confidence' => $confidence,
                ]);
            }
        }

        // Only return matches with confidence >= 50
        return $bestConfidence >= 50 ? $bestMatch : null;
    }

    private function countMappings(): int
    {
        $count = 0;
        foreach ($this->classPropertyMap as $properties) {
            $count += count($properties);
        }
        return $count;
    }

    private function generateReports(): void
    {
        $format = $this->option('format');

        if (in_array($format, ['csv', 'both'])) {
            $this->generateCsvReport();
        }

        if (in_array($format, ['json', 'both'])) {
            $this->generateJsonReport();
        }
    }

    private function generateCsvReport(): void
    {
        $csvPath = 'frame_element_property_mapping.csv';

        $handle = fopen(storage_path("app/{$csvPath}"), 'w');

        // Header
        fputcsv($handle, [
            'idFrameElement',
            'idEntity',
            'frameName',
            'feName',
            'objectProperty',
            'propertyUri',
            'propertyRange',
            'rangeUri',
            'matchType',
            'confidence',
        ]);

        // Data
        foreach ($this->results as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        $this->info("CSV report saved to: storage/app/{$csvPath}");

        // Also save unmapped items
        if (count($this->unmapped) > 0) {
            $unmappedPath = 'frame_element_unmapped.csv';
            $handle = fopen(storage_path("app/{$unmappedPath}"), 'w');

            fputcsv($handle, ['idFrameElement', 'idEntity', 'frameName', 'feName', 'reason', 'availableProperties']);

            foreach ($this->unmapped as $row) {
                fputcsv($handle, [
                    $row['idFrameElement'],
                    $row['idEntity'],
                    $row['frameName'],
                    $row['feName'],
                    $row['reason'],
                    $row['availableProperties'] ?? '',
                ]);
            }

            fclose($handle);

            $this->warn("Unmapped items saved to: storage/app/{$unmappedPath}");
        }
    }

    private function generateJsonReport(): void
    {
        $jsonPath = 'frame_element_property_mapping.json';

        $data = [
            'timestamp' => now()->toIso8601String(),
            'summary' => [
                'total_frame_elements' => count($this->results) + count($this->unmapped),
                'mapped' => count($this->results),
                'unmapped' => count($this->unmapped),
                'classes_in_ontology' => count($this->classPropertyMap),
            ],
            'mappings' => $this->results,
            'unmapped' => $this->unmapped,
        ];

        Storage::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("JSON report saved to: storage/app/{$jsonPath}");
    }

    private function displaySummary(): void
    {
        $this->newLine();
        $this->info('=== Mapping Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Frame Elements', count($this->results) + count($this->unmapped)],
                ['Successfully Mapped', count($this->results)],
                ['Unmapped', count($this->unmapped)],
                ['Classes in Ontology', count($this->classPropertyMap)],
            ]
        );

        // Show sample mappings
        if (count($this->results) > 0) {
            $this->newLine();
            $this->info('=== Sample Mappings (first 10) ===');
            $sample = array_slice($this->results, 0, 10);
            $this->table(
                ['Frame', 'FE Name', 'Object Property', 'Range', 'Match Type', 'Confidence'],
                array_map(function ($row) {
                    return [
                        $row['frameName'],
                        $row['feName'],
                        $row['objectProperty'],
                        $row['propertyRange'] ?? 'N/A',
                        $row['matchType'],
                        $row['confidence'] . '%',
                    ];
                }, $sample)
            );
        }

        // Show confidence distribution
        if (count($this->results) > 0) {
            $this->newLine();
            $this->info('=== Confidence Distribution ===');
            $distribution = [
                'High (90-100%)' => 0,
                'Good (70-89%)' => 0,
                'Medium (50-69%)' => 0,
            ];

            foreach ($this->results as $result) {
                $conf = $result['confidence'];
                if ($conf >= 90) {
                    $distribution['High (90-100%)']++;
                } elseif ($conf >= 70) {
                    $distribution['Good (70-89%)']++;
                } else {
                    $distribution['Medium (50-69%)']++;
                }
            }

            $this->table(
                ['Confidence Range', 'Count', 'Percentage'],
                array_map(function ($range, $count) {
                    $total = count($this->results);
                    $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                    return [$range, $count, $pct . '%'];
                }, array_keys($distribution), $distribution)
            );
        }
    }
}

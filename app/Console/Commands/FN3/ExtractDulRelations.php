<?php

namespace App\Console\Commands\FN3;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExtractDulRelations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-dul-relations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract subsumption relationships from DUL ontology to CSV files';

    private const RDFS_SUBCLASS_OF = 'http://www.w3.org/2000/01/rdf-schema#subClassOf';
    private const RDFS_SUBPROPERTY_OF = 'http://www.w3.org/2000/01/rdf-schema#subPropertyOf';
    private const RDFS_LABEL = 'http://www.w3.org/2000/01/rdf-schema#label';
    private const OWL_CLASS = 'http://www.w3.org/2002/07/owl#Class';
    private const OWL_OBJECT_PROPERTY = 'http://www.w3.org/2002/07/owl#ObjectProperty';

    private array $labelMap = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jsonldPath = base_path('app/Console/Commands/FN3/DUL.jsonld');
        $outputDir = base_path('app/Console/Commands/FN3');

        if (! File::exists($jsonldPath)) {
            $this->error("DUL.jsonld file not found at: {$jsonldPath}");
            return self::FAILURE;
        }

        $this->info('Reading DUL ontology from JSON-LD file...');
        $jsonContent = File::get($jsonldPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse JSON-LD file: ' . json_last_error_msg());
            return self::FAILURE;
        }

        $this->info('Building label mappings...');
        $this->buildLabelMap($data);
        $this->info('Found ' . count($this->labelMap) . ' labeled entities');

        $this->info('Extracting class subsumption relationships...');
        $classRelations = $this->extractSubsumptionRelations($data, self::RDFS_SUBCLASS_OF, false, true);

        $this->info('Extracting property subsumption relationships...');
        $propertyRelations = $this->extractSubsumptionRelations($data, self::RDFS_SUBPROPERTY_OF, false, true);

        $this->info('Found ' . count($classRelations) . ' class subsumption relationships');
        $this->info('Found ' . count($propertyRelations) . ' property subsumption relationships');

        // Write class subsumption to CSV
        $classOutputPath = $outputDir . '/dul_class_subsumption.csv';
        $this->writeToCSV($classRelations, $classOutputPath, 'subclass', 'superclass');
        $this->info("Class subsumption relationships written to: {$classOutputPath}");

        // Write property subsumption to CSV
        $propertyOutputPath = $outputDir . '/dul_property_subsumption.csv';
        $this->writeToCSV($propertyRelations, $propertyOutputPath, 'subproperty', 'superproperty');
        $this->info("Property subsumption relationships written to: {$propertyOutputPath}");

        $this->info('Extraction completed successfully!');
        return self::SUCCESS;
    }

    /**
     * Extract subsumption relationships for a given property type.
     */
    private function extractSubsumptionRelations(array $data, string $relationProperty, bool $convertToSnakeCase = false, bool $useLabels = false): array
    {
        $relations = [];

        foreach ($data as $entity) {
            if (!isset($entity['@id']) || !isset($entity[$relationProperty])) {
                continue;
            }

            $subEntity = $useLabels
                ? $this->getLabel($entity['@id'])
                : $this->extractLocalName($entity['@id']);

            if (!$subEntity) {
                continue;
            }

            if ($convertToSnakeCase) {
                $subEntity = Str::snake($subEntity);
            }

            // Handle both single and multiple parent relationships
            $parents = $entity[$relationProperty];
            if (!is_array($parents)) {
                continue;
            }

            foreach ($parents as $parent) {
                // Skip blank nodes and complex restrictions
                if (!isset($parent['@id']) || str_starts_with($parent['@id'], '_:')) {
                    continue;
                }

                $superEntity = $useLabels
                    ? $this->getLabel($parent['@id'])
                    : $this->extractLocalName($parent['@id']);

                if (!$superEntity) {
                    continue;
                }

                if ($convertToSnakeCase) {
                    $superEntity = Str::snake($superEntity);
                }
                $relations[] = [$subEntity, $superEntity];
            }
        }

        return $relations;
    }

    /**
     * Build a mapping of URI to label.
     */
    private function buildLabelMap(array $data): void
    {
        foreach ($data as $entity) {
            if (!isset($entity['@id']) || !isset($entity[self::RDFS_LABEL])) {
                continue;
            }

            $uri = $entity['@id'];
            $labels = $entity[self::RDFS_LABEL];

            // Find English label first, fallback to first available label
            $label = null;
            foreach ($labels as $labelObj) {
                if (isset($labelObj['@value'])) {
                    // Prefer English label
                    if (isset($labelObj['@language']) && $labelObj['@language'] === 'en') {
                        $label = $labelObj['@value'];
                        break;
                    }
                    // Fallback to first label without language or any label
                    if ($label === null) {
                        $label = $labelObj['@value'];
                    }
                }
            }

            if ($label) {
                $this->labelMap[$uri] = $label;
            }
        }
    }

    /**
     * Get the label for a URI.
     */
    private function getLabel(string $uri): ?string
    {
        return $this->labelMap[$uri] ?? null;
    }

    /**
     * Extract the local name from a full URI.
     */
    private function extractLocalName(string $uri): string
    {
        // Extract the part after the last # or /
        if (str_contains($uri, '#')) {
            return substr($uri, strrpos($uri, '#') + 1);
        }

        if (str_contains($uri, '/')) {
            return substr($uri, strrpos($uri, '/') + 1);
        }

        return $uri;
    }

    /**
     * Write relationships to CSV file.
     */
    private function writeToCSV(array $relations, string $filepath, string $col1Header, string $col2Header): void
    {
        $fp = fopen($filepath, 'w');

        // Write header
        fputcsv($fp, [$col1Header, $col2Header]);

        // Write data
        foreach ($relations as $relation) {
            fputcsv($fp, $relation);
        }

        fclose($fp);
    }
}

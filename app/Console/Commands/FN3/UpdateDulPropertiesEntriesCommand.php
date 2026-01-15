<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;

class UpdateDulPropertiesEntriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:update-dul-properties-entries
                            {--json= : Path to JSON-LD file (default: DUL.jsonld in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update entry table with names and descriptions from DUL.jsonld for DUL properties (microframes)';

    private array $stats = [
        'total_properties' => 0,
        'properties_found' => 0,
        'entries_created' => 0,
        'entries_updated' => 0,
        'skipped_no_label' => 0,
        'skipped_no_entity' => 0,
        'errors' => 0,
    ];

    // Target languages: Portuguese (1), English (2), Spanish (3), French (4)
    private const TARGET_LANGUAGES = [1, 2, 3, 4];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Update DUL Property Entry Records from DUL.jsonld');
        $this->newLine();

        // Configuration
        $jsonPath = $this->option('json') ?? __DIR__ . '/DUL.jsonld';
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user') ?? 6;

        // Validate JSON file exists
        if (! file_exists($jsonPath)) {
            $this->error("❌ JSON-LD file not found: {$jsonPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  • JSON-LD File: {$jsonPath}");
        $this->line("  • User ID: {$userId}");
        $this->line('  • Target Languages: '.implode(', ', self::TARGET_LANGUAGES).' (pt, en, es, fr)');
        if ($isDryRun) {
            $this->warn('  • DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Read and parse JSON-LD
        $this->info('📊 Reading JSON-LD file...');
        $properties = $this->parseJsonLd($jsonPath);

        if (empty($properties)) {
            $this->error('❌ No DUL properties found in JSON-LD file');

            return 1;
        }

        $this->stats['total_properties'] = count($properties);
        $this->info("Found {$this->stats['total_properties']} DUL properties to process");
        $this->newLine();

        // Process properties
        $this->info('🔄 Processing entry records...');
        $this->newLine();

        $this->withProgressBar($properties, function ($propertyData) use ($isDryRun, $userId) {
            $this->processProperty($propertyData, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Display statistics
        $this->displayStatistics($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('✅ Dry run completed. Use without --dry-run to update entries.');
        } else {
            $this->newLine();
            $this->info('✅ Entry update completed successfully!');
        }

        return 0;
    }

    /**
     * Parse DUL.jsonld file and extract property information
     */
    private function parseJsonLd(string $jsonPath): array
    {
        $content = file_get_contents($jsonPath);
        $jsonData = json_decode($content, true);

        if (! $jsonData || ! is_array($jsonData)) {
            $this->error('❌ Invalid JSON-LD format');

            return [];
        }

        $properties = [];

        // JSON-LD can be an array directly or have a @graph property
        $items = isset($jsonData['@graph']) ? $jsonData['@graph'] : $jsonData;

        foreach ($items as $item) {
            // Only process OWL Properties (ObjectProperty, DatatypeProperty, etc.)
            if (! isset($item['@type'])) {
                continue;
            }

            $types = is_array($item['@type']) ? $item['@type'] : [$item['@type']];
            $isProperty = false;

            foreach ($types as $type) {
                if (str_contains($type, 'Property') && str_contains($type, 'owl')) {
                    $isProperty = true;
                    break;
                }
            }

            if (! $isProperty) {
                continue;
            }

            // Extract property name from @id
            if (! isset($item['@id']) || ! str_contains($item['@id'], '#')) {
                continue;
            }

            $propertyName = substr($item['@id'], strrpos($item['@id'], '#') + 1);

            // Skip non-DUL properties (e.g., Dublin Core)
            if (! str_contains($item['@id'], 'DUL.owl')) {
                continue;
            }

            // Extract English label
            $label = $this->extractEnglishLabel($item);

            // Extract description (comment)
            $description = $this->extractComment($item);

            // Convert camelCase to snake_case for matching with microframe table
            $snakeCaseName = $this->camelToSnake($propertyName);

            $properties[] = [
                'name' => $propertyName,
                'snakeName' => $snakeCaseName,
                'label' => $label,
                'description' => $description,
            ];
        }

        return $properties;
    }

    /**
     * Extract English label from rdfs:label array
     */
    private function extractEnglishLabel(array $item): ?string
    {
        if (! isset($item['http://www.w3.org/2000/01/rdf-schema#label'])) {
            return null;
        }

        $labels = $item['http://www.w3.org/2000/01/rdf-schema#label'];

        foreach ($labels as $label) {
            if (isset($label['@language']) && $label['@language'] === 'en' && isset($label['@value'])) {
                return $label['@value'];
            }
        }

        return null;
    }

    /**
     * Extract description from rdfs:comment array
     */
    private function extractComment(array $item): ?string
    {
        if (! isset($item['http://www.w3.org/2000/01/rdf-schema#comment'])) {
            return null;
        }

        $comments = $item['http://www.w3.org/2000/01/rdf-schema#comment'];

        // Usually there's just one comment entry
        if (! empty($comments[0]['@value'])) {
            return $comments[0]['@value'];
        }

        return null;
    }

    /**
     * Convert camelCase to snake_case
     */
    private function camelToSnake(string $input): string
    {
        $pattern = '/(?<!^)[A-Z]/';

        return strtolower(preg_replace_callback($pattern, function ($matches) {
            return '_'.strtolower($matches[0]);
        }, $input));
    }

    /**
     * Process a single property and update its entry records
     */
    private function processProperty(array $propertyData, bool $isDryRun, int $userId): void
    {
        try {
            // Find the microframe entity by name (snake_case)
            $entity = $this->findMicroframeEntity($propertyData['snakeName']);

            if (! $entity) {
                $this->stats['skipped_no_entity']++;

                return;
            }

            $this->stats['properties_found']++;

            // Use label if available, otherwise use the property name (camelCase)
            $name = $propertyData['label'] ?? $propertyData['name'];
            $description = $propertyData['description'] ?? '';
            $idEntity = $entity->idEntity;

            // Keep the existing entry identifier from the database
            $existingEntry = Criteria::table('entry')
                ->where('idEntity', '=', $idEntity)
                ->where('idLanguage', '=', 2) // English
                ->first();

            $entryIdentifier = $existingEntry ? $existingEntry->entry : $propertyData['snakeName'];

            // Process entries for each target language
            foreach (self::TARGET_LANGUAGES as $idLanguage) {
                if (! $isDryRun) {
                    $updated = $this->upsertEntry($entryIdentifier, $name, $description, $idEntity, $idLanguage);
                    if ($updated) {
                        $this->stats['entries_updated']++;
                    } else {
                        $this->stats['entries_created']++;
                    }
                } else {
                    // In dry run, check if entry exists
                    $exists = Criteria::table('entry')
                        ->where('idEntity', '=', $idEntity)
                        ->where('idLanguage', '=', $idLanguage)
                        ->first();

                    if ($exists) {
                        $this->stats['entries_updated']++;
                    } else {
                        $this->stats['entries_created']++;
                    }
                }
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    /**
     * Find microframe entity by defaultName in frame table
     */
    private function findMicroframeEntity(string $name): ?object
    {
        $result = Criteria::table('frame')
            ->where('defaultName', '=', $name)
            ->where('idNamespace', '=', 14) // Microframe namespace
            ->select('idEntity')
            ->first();

        return $result;
    }

    /**
     * Insert or update entry record
     * Returns true if updated, false if created
     */
    private function upsertEntry(
        string $entry,
        string $name,
        string $description,
        int $idEntity,
        int $idLanguage
    ): bool {
        // Check if entry exists for this entity and language
        $existing = Criteria::table('entry')
            ->where('idEntity', '=', $idEntity)
            ->where('idLanguage', '=', $idLanguage)
            ->first();

        $data = [
            'entry' => $entry,
            'name' => $name,
            'description' => $description,
            'nick' => $name,
            'idEntity' => $idEntity,
            'idLanguage' => $idLanguage,
        ];

        if ($existing) {
            // Update existing entry
            Criteria::table('entry')
                ->where('idEntry', '=', $existing->idEntry)
                ->update($data);

            return true;
        } else {
            // Insert new entry
            Criteria::table('entry')->insert($data);

            return false;
        }
    }

    /**
     * Display statistics table
     */
    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Update Statistics:');
        $this->newLine();

        $tableData = [
            ['Total DUL properties in JSON-LD', $this->stats['total_properties']],
            ['Properties matched in database', $this->stats['properties_found']],
            ['Entries created', $this->stats['entries_created'].($isDryRun ? ' (would be created)' : '')],
            ['Entries updated', $this->stats['entries_updated'].($isDryRun ? ' (would be updated)' : '')],
            ['Skipped (no English label)', $this->stats['skipped_no_label']],
            ['Skipped (property not in DB)', $this->stats['skipped_no_entity']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['errors']} properties failed to process due to errors");
        }
    }
}

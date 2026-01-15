<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;

class UpdateDulEntriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:update-dul-entries
                            {--json= : Path to JSON-LD file (default: DUL.jsonld in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update entry table with names and descriptions from DUL.jsonld for DUL classes';

    private array $stats = [
        'total_classes' => 0,
        'classes_found' => 0,
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
        $this->info('🚀 Update DUL Entry Records from DUL.jsonld');
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
        $classes = $this->parseJsonLd($jsonPath);

        if (empty($classes)) {
            $this->error('❌ No DUL classes found in JSON-LD file');

            return 1;
        }

        $this->stats['total_classes'] = count($classes);
        $this->info("Found {$this->stats['total_classes']} DUL classes to process");
        $this->newLine();

        // Process classes
        $this->info('🔄 Processing entry records...');
        $this->newLine();

        $this->withProgressBar($classes, function ($classData) use ($isDryRun, $userId) {
            $this->processClass($classData, $isDryRun, $userId);
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
     * Parse DUL.jsonld file and extract class information
     */
    private function parseJsonLd(string $jsonPath): array
    {
        $content = file_get_contents($jsonPath);
        $jsonData = json_decode($content, true);

        if (! $jsonData || ! is_array($jsonData)) {
            $this->error('❌ Invalid JSON-LD format');

            return [];
        }

        $classes = [];

        // JSON-LD can be an array directly or have a @graph property
        $items = isset($jsonData['@graph']) ? $jsonData['@graph'] : $jsonData;

        foreach ($items as $item) {
            // Only process OWL Classes
            if (! isset($item['@type']) || ! in_array('http://www.w3.org/2002/07/owl#Class', (array) $item['@type'])) {
                continue;
            }

            // Extract class name from @id
            if (! isset($item['@id']) || ! str_contains($item['@id'], '#')) {
                continue;
            }

            $className = substr($item['@id'], strrpos($item['@id'], '#') + 1);

            // Extract English label
            $label = $this->extractEnglishLabel($item);
            if (! $label) {
                continue; // Skip classes without English label
            }

            // Extract description (comment)
            $description = $this->extractComment($item);

            $classes[] = [
                'name' => $className,
                'label' => $label,
                'description' => $description,
            ];
        }

        return $classes;
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
     * Process a single class and update its entry records
     */
    private function processClass(array $classData, bool $isDryRun, int $userId): void
    {
        // Skip if no label
        if (empty($classData['label'])) {
            $this->stats['skipped_no_label']++;

            return;
        }

        try {
            // Find the class entity by nameEn
            $entity = $this->findClassEntity($classData['name']);

            if (! $entity) {
                $this->stats['skipped_no_entity']++;

                return;
            }

            $this->stats['classes_found']++;

            // Prepare entry data
            $entryIdentifier = 'dul_class_'.strtolower($classData['name']);
            $name = $classData['label'];
            $description = $classData['description'] ?? '';
            $idEntity = $entity->idEntity;

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
                    // In dry run, just count as created
                    $this->stats['entries_created']++;
                }
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    /**
     * Find class entity by defaultName in frame table
     */
    private function findClassEntity(string $name): ?object
    {
        $result = Criteria::table('frame')
            ->where('defaultName', '=', $name)
            ->where('idNamespace', '=', 13) // Class namespace
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
            ['Total DUL classes in JSON-LD', $this->stats['total_classes']],
            ['Classes matched in database', $this->stats['classes_found']],
            ['Entries created', $this->stats['entries_created'].($isDryRun ? ' (would be created)' : '')],
            ['Entries updated', $this->stats['entries_updated'].($isDryRun ? ' (would be updated)' : '')],
            ['Skipped (no English label)', $this->stats['skipped_no_label']],
            ['Skipped (class not in DB)', $this->stats['skipped_no_entity']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['errors']} classes failed to process due to errors");
        }
    }
}

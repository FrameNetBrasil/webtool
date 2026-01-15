<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDulClassRestrictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fn3:import-dul-class-restrictions
                            {--csv= : Path to CSV file (default: dul_class_restrictions.csv in same directory)}
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import DUL class restrictions from CSV into frameelement table using fe_create routine';

    private array $stats = [
        'total_rows' => 0,
        'imported' => 0,
        'skipped_class_not_found' => 0,
        'skipped_property_not_found' => 0,
        'skipped_no_related_class' => 0,
        'skipped_related_class_not_found' => 0,
        'entityrelations_created' => 0,
        'errors' => 0,
    ];

    private array $classCache = [];

    private array $classEntityCache = [];

    private array $microframeToEntryCache = [];

    private array $microframeEntityCache = [];

    private array $microframeDirectionCache = [];

    private array $missingProperties = [];

    private array $inverseProperties = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 DUL Class Restrictions Import to Frame Elements');
        $this->newLine();

        // Configuration
        $csvPath = $this->option('csv') ?? __DIR__ . '/dul_class_restrictions.csv';
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user') ?? 6;

        // Validate CSV file exists
        if (! file_exists($csvPath)) {
            $this->error("❌ CSV file not found: {$csvPath}");

            return 1;
        }

        $this->info('Configuration:');
        $this->line("  • CSV File: {$csvPath}");
        $this->line("  • User ID: {$userId}");
        if ($isDryRun) {
            $this->warn('  • DRY RUN MODE - No database changes will be made');
        }
        $this->newLine();

        // Load caches
        $this->info('📚 Loading class and property data...');
        $this->loadClassCache();
        $this->loadMicroframeCache();
        $this->line("  • Loaded {$this->countClasses()} classes");
        $this->line("  • Loaded {$this->countMicroframes()} microframes");
        $this->newLine();

        // Read and validate CSV
        $this->info('📊 Reading CSV file...');
        $rows = $this->readCsv($csvPath);

        if (empty($rows)) {
            $this->error('❌ No data rows found in CSV file');

            return 1;
        }

        $this->stats['total_rows'] = count($rows);
        $this->info("Found {$this->stats['total_rows']} rows to process");
        $this->newLine();

        // Process rows
        $this->info('🔄 Processing frame elements...');
        $this->newLine();

        $this->withProgressBar($rows, function ($row) use ($isDryRun, $userId) {
            $this->processRow($row, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Display statistics
        $this->displayStatistics($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('✅ Dry run completed. Use without --dry-run to import data.');
        } else {
            $this->newLine();
            $this->info('✅ Import completed successfully!');
        }

        return 0;
    }

    private function loadClassCache(): void
    {
        try {
            $classes = Criteria::table('class')
                ->select('idClass', 'nameEn', 'idEntity')
                ->all();

            foreach ($classes as $class) {
                $this->classCache[$class->nameEn] = $class->idClass;
                $this->classEntityCache[$class->nameEn] = $class->idEntity;
            }
        } catch (Exception $e) {
            $this->error('❌ Failed to load class cache: '.$e->getMessage());
            exit(1);
        }
    }

    private function loadMicroframeCache(): void
    {
        try {
            $microframes = Criteria::table('microframe as m')
                ->join('entry as e', 'm.idEntity', '=', 'e.idEntity')
                ->select('m.name', 'm.nameInverse', 'm.idEntity', 'e.name as entry_name')
                ->where('e.idLanguage', '=', 2)
                ->all();

            foreach ($microframes as $mf) {
                // Store entry name for both directions
                $this->microframeToEntryCache[$mf->name] = $mf->entry_name;

                // Store entity and direction for forward (name)
                $this->microframeEntityCache[$mf->name] = $mf->idEntity;
                $this->microframeDirectionCache[$mf->name] = 'forward';

                // Also cache inverse names if they exist
                if (! empty($mf->nameInverse)) {
                    $this->microframeToEntryCache[$mf->nameInverse] = $mf->entry_name;
                    $this->microframeEntityCache[$mf->nameInverse] = $mf->idEntity;
                    $this->microframeDirectionCache[$mf->nameInverse] = 'inverse';
                }
            }
        } catch (Exception $e) {
            $this->error('❌ Failed to load microframe cache: '.$e->getMessage());
            exit(1);
        }
    }

    private function countClasses(): int
    {
        return count($this->classCache);
    }

    private function countMicroframes(): int
    {
        return count(array_unique($this->microframeEntityCache));
    }

    private function readCsv(string $csvPath): array
    {
        $rows = [];
        $handle = fopen($csvPath, 'r');

        if (! $handle) {
            $this->error('❌ Failed to open CSV file');

            return [];
        }

        // Read header
        $header = fgetcsv($handle);

        if (! $header) {
            $this->error('❌ Failed to read CSV header');
            fclose($handle);

            return [];
        }

        // Trim header values to handle any whitespace
        $header = array_map('trim', $header);

        if (! in_array('class_name', $header) || ! in_array('property_name', $header)) {
            $this->error('❌ Invalid CSV format. Expected columns: class_name, property_name');
            fclose($handle);

            return [];
        }

        // Get column indices
        $classNameIdx = array_search('class_name', $header);
        $propertyNameIdx = array_search('property_name', $header);
        $relatedClassIdx = array_search('related_class', $header);

        // Read data rows
        while (($data = fgetcsv($handle)) !== false) {
            if (isset($data[$classNameIdx]) && isset($data[$propertyNameIdx])) {
                $className = trim($data[$classNameIdx]);
                $propertyName = trim($data[$propertyNameIdx]);
                $relatedClass = isset($data[$relatedClassIdx]) ? trim($data[$relatedClassIdx]) : '';

                if (! empty($className) && ! empty($propertyName)) {
                    $rows[] = [
                        'class_name' => $className,
                        'property_name' => $propertyName,
                        'related_class' => $relatedClass,
                    ];
                }
            }
        }

        fclose($handle);

        return $rows;
    }

    private function processRow(array $row, bool $isDryRun, int $userId): void
    {
        // 1. Lookup class ID
        $idClass = $this->classCache[$row['class_name']] ?? null;

        if (! $idClass) {
            $this->stats['skipped_class_not_found']++;

            return;
        }

        // 2. Get entry name for the property
        $entryName = $this->microframeToEntryCache[$row['property_name']] ?? null;

        if (! $entryName) {
            $this->stats['skipped_property_not_found']++;

            // Track missing properties for dry-run reporting
            if ($isDryRun) {
                $this->missingProperties[$row['property_name']] = true;
            }

            return;
        }

        // 3. Track inverse relations for dry-run
        $isInverse = ($this->microframeDirectionCache[$row['property_name']] ?? null) === 'inverse';
        if ($isDryRun && $isInverse) {
            $this->inverseProperties[$row['property_name']] = true;
        }

        // 4. Check related_class
        $hasRelatedClass = ! empty($row['related_class']);

        if (! $hasRelatedClass) {
            $this->stats['skipped_no_related_class']++;
        }

        // 5. If has related_class, validate it exists
        $idRelatedClassEntity = null;
        if ($hasRelatedClass) {
            $idRelatedClassEntity = $this->classEntityCache[$row['related_class']] ?? null;

            if (! $idRelatedClassEntity) {
                $this->stats['skipped_related_class_not_found']++;

                return;
            }
        }

        try {
            if (! $isDryRun) {
                // 6. Create Frame Element
                $idFrameElement = $this->createFrameElement($idClass, $entryName, $userId);

                // 7. Create EntityRelation if related_class exists
                if ($hasRelatedClass && $idFrameElement) {
                    $this->createEntityRelation(
                        $row['property_name'],
                        $idFrameElement,
                        $idRelatedClassEntity
                    );
                    $this->stats['entityrelations_created']++;
                }
            } else {
                // In dry-run, just count what would be created
                if ($hasRelatedClass) {
                    $this->stats['entityrelations_created']++;
                }
            }

            $this->stats['imported']++;

        } catch (Exception $e) {
            $this->stats['errors']++;
            // Silent error handling during progress bar
        }
    }

    private function createFrameElement(int $idClass, string $entryName, int $userId): ?int
    {
        // Call fe_create(NULL, <idClass>, <entry_name>, 'cty_property', 78, <userId>)
        $result = DB::select(
            'SELECT fe_create(?, ?, ?, ?, ?, ?) as idFrameElement',
            [null, $idClass, $entryName, 'cty_property', 78, $userId]
        );

        return $result[0]->idFrameElement ?? null;
    }

    private function createEntityRelation(string $propertyName, int $idFrameElement, int $idRelatedClassEntity): void
    {
        // Get microframe idEntity
        $idMicroframeEntity = $this->microframeEntityCache[$propertyName];

        // Get FE idEntity by querying the frameelement table
        $fe = Criteria::table('frameelement')
            ->select('idEntity')
            ->where('idFrameElement', '=', $idFrameElement)
            ->first();

        if (! $fe) {
            throw new Exception("Frame element not found: {$idFrameElement}");
        }

        $idFEEntity = $fe->idEntity;

        // Determine entity order based on direction
        $direction = $this->microframeDirectionCache[$propertyName];

        if ($direction === 'forward') {
            // property_name matches microframe.name (forward)
            $idEntity2 = $idFEEntity;
            $idEntity3 = $idRelatedClassEntity;
        } else {
            // property_name matches microframe.nameInverse (inverse)
            $idEntity2 = $idRelatedClassEntity;
            $idEntity3 = $idFEEntity;
        }

        // Create entityrelation record
        DB::table('entityrelation')->insert([
            'idRelationType' => 243,  // rel_microframe
            'idEntity1' => $idMicroframeEntity,
            'idEntity2' => $idEntity2,
            'idEntity3' => $idEntity3,
        ]);
    }

    private function displayStatistics(bool $isDryRun): void
    {
        $this->info('📈 Import Statistics:');
        $this->newLine();

        $tableData = [
            ['Total rows in CSV', $this->stats['total_rows']],
            ['Successfully imported', $this->stats['imported'].($isDryRun ? ' (would be imported)' : '')],
            ['EntityRelations created', $this->stats['entityrelations_created'].($isDryRun ? ' (would be created)' : '')],
            ['Skipped (no related_class)', $this->stats['skipped_no_related_class']],
            ['Skipped (related_class not found)', $this->stats['skipped_related_class_not_found']],
            ['Skipped (class not found)', $this->stats['skipped_class_not_found']],
            ['Skipped (property not found)', $this->stats['skipped_property_not_found']],
            ['Errors', $this->stats['errors']],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        if ($this->stats['skipped_class_not_found'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_class_not_found']} rows skipped due to class not found in database");
        }

        if ($this->stats['skipped_property_not_found'] > 0 || ! empty($this->inverseProperties)) {
            if ($this->stats['skipped_property_not_found'] > 0) {
                $this->newLine();
                $this->warn("⚠️  {$this->stats['skipped_property_not_found']} rows skipped due to property not found in microframe table");

                // Show missing properties in dry-run mode
                if ($isDryRun && ! empty($this->missingProperties)) {
                    $this->newLine();
                    $this->info('📋 Missing Properties (not found in microframe table):');
                    $this->newLine();

                    $missingPropertyNames = array_keys($this->missingProperties);
                    sort($missingPropertyNames);

                    // Display in columns for better readability
                    $chunks = array_chunk($missingPropertyNames, 3);
                    foreach ($chunks as $chunk) {
                        $this->line('  • '.implode(', ', $chunk));
                    }

                    $this->newLine();
                    $this->info('💡 Tip: Run "php artisan fn3:import-dul-properties" first to import missing properties.');
                }
            }

            // Show properties that exist as inverse relations
            if ($isDryRun && ! empty($this->inverseProperties)) {
                $this->newLine();
                $this->info('🔄 Properties Found as Inverse Relations (nameInverse field):');
                $this->newLine();

                $inversePropertyNames = array_keys($this->inverseProperties);
                sort($inversePropertyNames);

                // Display in columns for better readability
                $chunks = array_chunk($inversePropertyNames, 3);
                foreach ($chunks as $chunk) {
                    $this->line('  • '.implode(', ', $chunk));
                }

                $this->newLine();
                $this->info('✅ These properties already exist in the microframe table as inverse relations.');
            }
        }

        if ($this->stats['skipped_no_related_class'] > 0) {
            $this->newLine();
            $this->info("ℹ️  {$this->stats['skipped_no_related_class']} rows had no related_class (no entityrelation created for these)");
        }

        if ($this->stats['skipped_related_class_not_found'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['skipped_related_class_not_found']} rows skipped due to related_class not found in database");
        }

        if ($this->stats['errors'] > 0) {
            $this->newLine();
            $this->warn("⚠️  {$this->stats['errors']} frame elements failed to import due to errors");
        }
    }
}

<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateClusterFramesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cluster:create-frames
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create cluster frames with fixed frame elements (Event, State, Attribute, Entity, Relation)';

    /**
     * Frame element names for clusters.
     */
    private const FRAME_ELEMENTS = ['Event', 'State', 'Attribute', 'Entity', 'Relation'];

    /**
     * Statistics tracking.
     */
    private array $stats = [
        'total_clusters' => 0,
        'frames_created' => 0,
        'fe_created' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $userId = (int) $this->option('user');

        if ($isDryRun) {
            $this->info('🔍 Running in DRY-RUN mode - no changes will be made');
            $this->newLine();
        }

        // Step 1: Query cluster names from view_frame
        $this->info('Querying cluster names from view_frame...');
        $clusters = DB::select('
            SELECT name, GROUP_CONCAT(nsName) as namespaces
            FROM view_frame
            WHERE idLanguage = 2
            GROUP BY name
            ORDER BY 1
        ');

        $this->stats['total_clusters'] = count($clusters);
        $this->info("Found {$this->stats['total_clusters']} cluster names");
        $this->newLine();

        if ($this->stats['total_clusters'] === 0) {
            $this->warn('No clusters found to process.');

            return 0;
        }

        // Step 2: Process each cluster with progress bar
        $this->info('Creating cluster frames and frame elements...');
        $this->withProgressBar($clusters, function ($cluster) use ($isDryRun, $userId) {
            $this->processCluster($cluster, $isDryRun, $userId);
        });

        $this->newLine(2);

        // Step 3: Display statistics
        $this->displayStatistics($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('💡 Run without --dry-run to apply changes');
        }

        return 0;
    }

    /**
     * Process a single cluster: create frame and frame elements.
     */
    private function processCluster(object $cluster, bool $isDryRun, int $userId): void
    {
        try {
            if (! $isDryRun) {
                // Step 1: Create cluster frame using frame_create routine
                $frameData = json_encode([
                    'nameEn' => $cluster->name,
                    'idNamespace' => 15, // Cluster namespace
                    'idUser' => $userId,
                ]);

                $idFrame = Criteria::function('frame_create(?)', [$frameData]);

                if (! $idFrame) {
                    $this->stats['errors']++;

                    return;
                }

                $this->stats['frames_created']++;

                // Step 2: Create 5 frame elements for this cluster
                foreach (self::FRAME_ELEMENTS as $feName) {
                    Criteria::function('fe_create(?, ?, ?, ?, ?)', [
                        $idFrame,
                        $feName,
                        'cty_core',  // Core type
                        1,           // Color ID (as specified)
                        $userId,
                    ]);

                    $this->stats['fe_created']++;
                }
            } else {
                // In dry-run mode, just count what would be created
                $this->stats['frames_created']++;
                $this->stats['fe_created'] += count(self::FRAME_ELEMENTS);
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->newLine();
            $this->error("Error processing cluster '{$cluster->name}': {$e->getMessage()}");
        }
    }

    /**
     * Display statistics table.
     */
    private function displayStatistics(bool $isDryRun): void
    {
        $suffix = $isDryRun ? ' (would be)' : '';

        $this->table(['Metric', 'Count'], [
            ['Total clusters found', $this->stats['total_clusters']],
            ['Frames created'.$suffix, $this->stats['frames_created']],
            ['Frame elements created'.$suffix, $this->stats['fe_created']],
            ['Errors', $this->stats['errors']],
        ]);
    }
}

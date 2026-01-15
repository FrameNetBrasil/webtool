<?php

namespace App\Console\Commands\FN3;

use App\Database\Criteria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateClusterConstraintsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cluster:create-constraints
                            {--dry-run : Preview changes without writing to database}
                            {--user=6 : User ID for timeline tracking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create constraints between cluster frame elements and related frames based on namespace rules';

    /**
     * Namespace rules for each frame element type.
     * Maps FE name to allowed namespace IDs.
     */
    private const NAMESPACE_RULES = [
        'Event' => [2, 3, 4, 5, 7, 8],
        'Attribute' => [9],
        'State' => [6],
        'Relation' => [11],
        'Entity' => [10],
    ];

    /**
     * Statistics tracking.
     */
    private array $stats = [
        'total_clusters' => 0,
        'clusters_processed' => 0,
        'constraints_created' => 0,
        'by_fe_type' => [
            'Event' => 0,
            'State' => 0,
            'Attribute' => 0,
            'Entity' => 0,
            'Relation' => 0,
        ],
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

        // Step 1: Query all clusters (English only)
        $this->info('Querying clusters from view_cluster...');
        $clusters = DB::select('
            SELECT *
            FROM view_cluster
            WHERE idLanguage = 2
        ');

        $this->stats['total_clusters'] = count($clusters);
        $this->info("Found {$this->stats['total_clusters']} clusters");
        $this->newLine();

        if ($this->stats['total_clusters'] === 0) {
            $this->warn('No clusters found to process.');

            return 0;
        }

        // Step 2: Process each cluster with progress bar
        $this->info('Creating constraints for cluster frame elements...');
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
     * Process a single cluster: create constraints for its frame elements.
     */
    private function processCluster(object $cluster, bool $isDryRun, int $userId): void
    {
        try {
            $constraintsCreatedForCluster = false;

            // Loop through each FE type (Event, State, Attribute, Entity, Relation)
            foreach (self::NAMESPACE_RULES as $feName => $allowedNamespaces) {
                // Get the specific frame element for this cluster
                $fe = DB::selectOne('
                    SELECT *
                    FROM view_frameelement
                    WHERE idFrame = ?
                    AND name = ?
                    AND idLanguage = 2
                ', [$cluster->idFrame, $feName]);

                if (! $fe) {
                    // This cluster doesn't have this FE type, skip
                    continue;
                }

                // Query frames with matching name AND namespace in a single query
                $namespacesIn = implode(',', $allowedNamespaces);
                $matchingFrames = DB::select("
                    SELECT idFrame, name, idEntity, idNamespace
                    FROM view_frame
                    WHERE name = ?
                    AND idFrame != ?
                    AND idNamespace IN ($namespacesIn)
                    AND idLanguage = 2
                ", [$cluster->name, $cluster->idFrame]);

                // Create constraints for each matching frame
                foreach ($matchingFrames as $matchingFrame) {
                    if (! $isDryRun) {
                        // Create constraint entity
                        $idConstraint = Criteria::create('entity', ['type' => 'CON']);

                        // Create the constraint relation with proper userId
                        $relationData = json_encode([
                            'relationType' => 'rel_constraint_frame',
                            'idEntity1' => $idConstraint,
                            'idEntity2' => $fe->idEntity,
                            'idEntity3' => $matchingFrame->idEntity,
                            'idRelation' => null,
                            'idUser' => $userId,
                        ]);

                        DB::select('SELECT relation_create(?) as result', [$relationData]);
                    }

                    $this->stats['constraints_created']++;
                    $this->stats['by_fe_type'][$feName]++;
                    $constraintsCreatedForCluster = true;
                }
            }

            if ($constraintsCreatedForCluster) {
                $this->stats['clusters_processed']++;
            }
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $this->newLine();
            $this->error("Error processing cluster '{$cluster->name}' (ID: {$cluster->idFrame}): {$e->getMessage()}");
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
            ['Clusters processed (with constraints)', $this->stats['clusters_processed']],
            ['Total constraints created'.$suffix, $this->stats['constraints_created']],
            ['  - Event FE', $this->stats['by_fe_type']['Event']],
            ['  - State FE', $this->stats['by_fe_type']['State']],
            ['  - Attribute FE', $this->stats['by_fe_type']['Attribute']],
            ['  - Entity FE', $this->stats['by_fe_type']['Entity']],
            ['  - Relation FE', $this->stats['by_fe_type']['Relation']],
            ['Errors', $this->stats['errors']],
        ]);
    }
}

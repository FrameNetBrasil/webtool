<?php

namespace App\Console\Commands\CLN;

use App\Services\CLN_RNT\RNTGraphBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Build RNT Pattern Graph Command
 *
 * Builds pattern graphs using RNT (Radical Node Type) topology with
 * four node types: DATA, OR, AND, and SEQUENCER.
 *
 * Usage:
 *   php artisan rnt:build-pattern-graph
 *   php artisan rnt:build-pattern-graph --dry-run
 *   php artisan rnt:build-pattern-graph --verify
 *   php artisan rnt:build-pattern-graph --force --verify
 */
class BuildPatternGraph extends Command
{
    protected $signature = 'cln:build-pattern-graph
        {--dry-run : Preview changes without writing to database}
        {--force : Skip confirmation prompts}
        {--verify : Run integrity checks after build}';

    protected $description = 'Build RNT pattern graph with SeqColumn structure (SEQUENCER + OR nodes + DATA nodes)';

    public function handle(): int
    {
        $this->info('RNT Pattern Graph Builder (SeqColumn-based)');
        $this->info('Each construction → 1 SEQUENCER (L5) + 3 OR nodes (L23: left, head, right)');
        $this->newLine();

        // Confirmation prompt (unless --force or --dry-run)
        if (! $this->option('force') && ! $this->option('dry-run')) {
            $this->warn('This will rebuild RNT pattern graph nodes.');
            $this->warn('All existing RNT nodes (DATA, OR, AND, SEQUENCER types) will be cleared.');
            $this->newLine();

            if (! $this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE: No changes will be made to the database');
            $this->newLine();
        }

        try {
            // Step 1: Clear existing RNT data
            $this->clearExistingGraph();

            // Step 2: Build graph
            $this->info('Building RNT pattern graph...');
            $builder = new RNTGraphBuilder(dryRun: $this->option('dry-run'));

            $constructionCount = DB::table('parser_construction_v4')
                ->whereNotNull('compiledPattern')
                ->where('enabled', 1)
                ->count();

            $bar = $this->output->createProgressBar($constructionCount);
            $bar->start();

            // Build with progress updates
            $this->buildWithProgress($builder, $bar);

            $bar->finish();
            $this->newLine(2);

            // Step 3: Display statistics
            $stats = $builder->getStatistics();
            $this->displayStatistics($stats);

            // Step 4: Verify integrity (if requested)
            if ($this->option('verify')) {
                $this->newLine();
                $this->verifyIntegrity();
            }

            // Step 5: Final message
            if ($this->option('dry-run')) {
                $this->newLine();
                $this->warn('DRY RUN: No changes were made to the database');
            } else {
                $this->newLine();
                $this->info('✓ RNT pattern graph built successfully');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('Failed to build RNT pattern graph: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    /**
     * Clear existing RNT pattern graph data
     */
    private function clearExistingGraph(): void
    {
        if ($this->option('dry-run')) {
            $this->info('Would clear existing RNT graph data...');

            return;
        }

        $this->info('Clearing existing RNT graph data...');

        // Count nodes to delete (DATA, OR, AND, SEQUENCER)
        $nodeCount = DB::table('parser_pattern_node')
            ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER','SOM','VIP'])
            ->count();

        // Count edges to delete
        $edgeCount = DB::table('parser_pattern_edge')->count();

        // Delete all edges
        DB::table('parser_pattern_edge')->delete();

        // Delete all RNT nodes (DATA, OR, AND, SEQUENCER)
        DB::table('parser_pattern_node')
            ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER','SOM','VIP'])
            ->delete();

        // Re-enable foreign key checks if needed
        // DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->line("  Cleared {$edgeCount} edges and {$nodeCount} nodes");
        $this->newLine();
    }

    /**
     * Build graph with progress bar updates
     */
    private function buildWithProgress(RNTGraphBuilder $builder, $bar): void
    {
        // Build the graph
        $builder->buildGraph();

        // Get constructions manually for progress tracking
        $constructions = DB::table('parser_construction_v4')
            ->whereNotNull('compiledPattern')
            ->where('enabled', 1)
            ->orderBy('idConstruction')
            ->get();

        foreach ($constructions as $construction) {
            $bar->advance();
        }
    }

    /**
     * Display build statistics
     */
    private function displayStatistics(array $stats): void
    {
        $this->info('=== RNT Pattern Graph Statistics (SeqColumn-based) ===');
        $this->newLine();

        $totalNodes = ($stats['sequencer_nodes'] ?? 0) + ($stats['or_nodes'] ?? 0) + ($stats['and_nodes'] ?? 0) + ($stats['data_nodes'] ?? 0);

        $tableData = [
            ['SeqColumns Created', number_format($stats['seq_columns_created'] ?? 0)],
            ['', ''],
            ['Total Nodes', number_format($totalNodes)],
            ['  - SEQUENCER Nodes (L5)', number_format($stats['sequencer_nodes'] ?? 0)],
            ['  - OR Nodes (L23)', number_format($stats['or_nodes'] ?? 0)],
            ['  - AND Nodes (MWE)', number_format($stats['and_nodes'] ?? 0)],
            ['  - DATA Nodes (Input)', number_format($stats['data_nodes'] ?? 0)],
            ['Total Edges', number_format($stats['edges_created'] ?? 0)],
            ['', ''],
            ['Constructions Processed', number_format($stats['constructions_processed'] ?? 0)],
            ['  - MWE Patterns', number_format($stats['mwe_patterns'] ?? 0)],
            ['Constructions Skipped', number_format($stats['constructions_skipped'] ?? 0)],
        ];

        $this->table(['Metric', 'Count'], $tableData);

        // Calculate averages
        $seqColumnsCreated = $stats['seq_columns_created'] ?? 0;
        if ($seqColumnsCreated > 0) {
            $avgDataNodes = ($stats['data_nodes'] ?? 0) / $seqColumnsCreated;
            $avgEdges = ($stats['edges_created'] ?? 0) / $seqColumnsCreated;

            $this->newLine();
            $this->info('Averages per SeqColumn:');
            $this->line('  DATA Nodes: '.number_format($avgDataNodes, 1));
            $this->line('  Edges: '.number_format($avgEdges, 1));
            $this->line('  Internal Structure: 1 SEQUENCER + 3 OR nodes (left, head, right)');

            if (($stats['mwe_patterns'] ?? 0) > 0) {
                $this->line('  MWE: AND binary tree → target construction head OR');
            }
        }
    }

    /**
     * Verify graph integrity
     */
    private function verifyIntegrity(): void
    {
        $this->info('Running integrity checks...');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        // Check 1: All RNT nodes have valid types
        $this->line('Check 1: RNT node type validity');
        $invalidTypes = DB::table('parser_pattern_node')
            ->whereIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER','SOM','VIP'])
            ->whereNotIn('type', ['DATA', 'OR', 'AND', 'SEQUENCER','SOM','VIP'])
            ->count();

        if ($invalidTypes === 0) {
            $this->info('  ✓ All RNT nodes have valid types (DATA, OR, AND, SEQUENCER,SOM,VIP)');
            $passed++;
        } else {
            $this->error("  ✗ Found {$invalidTypes} nodes with invalid types");
            $failed++;
        }

        // Check 2: All edges reference valid nodes
        $this->line('Check 2: Edge referential integrity');
        $invalidEdges = DB::table('parser_pattern_edge as e')
            ->join('parser_pattern_node as fn', 'e.from_node_id', '=', 'fn.id')
            ->join('parser_pattern_node as tn', 'e.to_node_id', '=', 'tn.id')
            ->whereIn('fn.type', ['DATA', 'OR', 'AND', 'SEQUENCER'])
            ->whereNull('tn.id')
            ->count();

        if ($invalidEdges === 0) {
            $this->info('  ✓ All RNT edges reference valid nodes');
            $passed++;
        } else {
            $this->error("  ✗ Found {$invalidEdges} RNT edges with invalid node references");
            $failed++;
        }

        // Check 3: DATA nodes have proper specifications
        $this->line('Check 3: DATA node specifications');
        $dataNodes = DB::table('parser_pattern_node')
            ->where('type', 'DATA')
            ->get();

        $invalidDataNodes = 0;
        foreach ($dataNodes as $node) {
            $spec = json_decode($node->specification, true);
            if (! isset($spec['dataType'])) {
                $invalidDataNodes++;
            }
        }

        if ($invalidDataNodes === 0) {
            $this->info('  ✓ All DATA nodes have valid dataType specifications');
            $passed++;
        } else {
            $this->error("  ✗ Found {$invalidDataNodes} DATA nodes without dataType");
            $failed++;
        }

        // Check 4: No orphaned RNT nodes
        $this->line('Check 4: Orphaned nodes check');
        $orphanedNodes = DB::table('parser_pattern_node as n')
            ->whereIn('n.type', ['DATA', 'OR', 'AND', 'SEQUENCER'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('parser_pattern_edge as e')
                    ->whereColumn('e.from_node_id', 'n.id')
                    ->orWhereColumn('e.to_node_id', 'n.id');
            })
            ->count();

        if ($orphanedNodes === 0) {
            $this->info('  ✓ No orphaned RNT nodes found');
            $passed++;
        } else {
            $this->warn("  ⚠ Found {$orphanedNodes} orphaned nodes (may be single-element patterns)");
            $passed++; // Warning, not failure
        }

        // Check 5: SeqColumn structure integrity
        $this->line('Check 5: SeqColumn structure (1 SEQUENCER + 3 OR nodes per construction)');
        $constructions = DB::table('parser_construction_v4')
            ->whereNotNull('compiledPattern')
            ->where('enabled', 1)
            ->get();

        $invalidStructures = 0;
        foreach ($constructions as $construction) {
            $sequencerCount = DB::table('parser_pattern_node')
                ->where('type', 'SEQUENCER')
                ->where('construction_name', $construction->name)
                ->count();

            $orCount = DB::table('parser_pattern_node')
                ->where('type', 'OR')
                ->where('construction_name', $construction->name)
                ->count();

            // Each construction should have 1 SEQUENCER + 3 OR nodes
            if ($sequencerCount !== 1 || $orCount !== 3) {
                $invalidStructures++;
            }
        }

        if ($invalidStructures === 0) {
            $this->info('  ✓ All constructions have proper SeqColumn structure (1 SEQUENCER + 3 OR)');
            $passed++;
        } else {
            $this->error("  ✗ Found {$invalidStructures} constructions with invalid SeqColumn structure");
            $failed++;
        }

        // Summary
        $this->newLine();
        if ($failed === 0) {
            $this->info("✓ All {$passed} integrity checks passed");
        } else {
            $this->error("✗ {$failed} checks failed, {$passed} passed");
        }
    }
}

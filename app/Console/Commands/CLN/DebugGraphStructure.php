<?php

namespace App\Console\Commands\CLN;

use App\Models\CLN\PatternGraph;
use App\Models\CLN\RuntimeGraphBuilder;
use Illuminate\Console\Command;

class DebugGraphStructure extends Command
{
    protected $signature = 'cln:debug-graph';

    protected $description = 'Debug CLN graph structure';

    public function handle(): int
    {
        $this->info('Building graph...');
        $patternGraph = new PatternGraph;
        $builder = new RuntimeGraphBuilder;
        $runtimeGraph = $builder->build($patternGraph);

        $this->info('Graph structure:');
        $this->newLine();

        // Group by level
        $byLevel = [];
        foreach ($runtimeGraph->getAllColumns() as $column) {
            $level = $column->hierarchicalLevel;
            if (! isset($byLevel[$level])) {
                $byLevel[$level] = [];
            }
            $byLevel[$level][] = $column;
        }

        ksort($byLevel);

        foreach ($byLevel as $level => $columns) {
            $this->info("=== LEVEL {$level} ===");
            foreach ($columns as $column) {
                $feedbackCount = count($column->feedbackDown);
                $feedforwardCount = count($column->feedforwardUp);
                $this->line("  {$column->id}: '{$column->name}' ({$column->type->value})");
                $this->line("    Feedforward up: {$feedforwardCount}, Feedback down: {$feedbackCount}");

                if ($feedbackCount > 0) {
                    $this->line('    Predicts:');
                    foreach ($column->feedbackDown as $pathway) {
                        $this->line("      -> {$pathway->target->id} '{$pathway->target->name}'");
                    }
                }
            }
            $this->newLine();
        }

        return Command::SUCCESS;
    }
}

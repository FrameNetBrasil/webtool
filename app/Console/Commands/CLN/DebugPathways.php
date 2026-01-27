<?php

namespace App\Console\Commands\CLN;

use App\Models\CLN\PatternGraph;
use App\Models\CLN\RuntimeGraphBuilder;
use Illuminate\Console\Command;

class DebugPathways extends Command
{
    protected $signature = 'cln:debug-pathways {columnId}';

    protected $description = 'Debug pathways for a specific column';

    public function handle(): int
    {
        $columnId = $this->argument('columnId');

        $this->info('Building graph...');
        $patternGraph = new PatternGraph;
        $builder = new RuntimeGraphBuilder;
        $runtimeGraph = $builder->build($patternGraph);

        $column = $runtimeGraph->getColumnById($columnId);
        if (! $column) {
            $this->error("Column {$columnId} not found");

            return Command::FAILURE;
        }

        $this->info("Column: {$column->id} '{$column->name}' ({$column->type->value})");
        $this->newLine();

        $this->info('Feedforward UP ({'.count($column->feedforwardUp).'}):');
        foreach ($column->feedforwardUp as $pathway) {
            $blocked = $pathway->gatingInhibitor ? ' [GATED]' : '';
            $this->line("  {$pathway->source->id}.{$pathway->sourceLayer} -> {$pathway->target->id}.{$pathway->targetLayer} '{$pathway->target->name}'{$blocked}");
        }
        $this->newLine();

        $this->info('Feedback DOWN ('.count($column->feedbackDown).'):');
        foreach ($column->feedbackDown as $pathway) {
            $this->line("  {$pathway->source->id}.{$pathway->sourceLayer} -> {$pathway->target->id}.{$pathway->targetLayer} '{$pathway->target->name}'");
        }

        return Command::SUCCESS;
    }
}

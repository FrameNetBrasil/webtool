<?php

namespace App\Console\Commands\CLN;

use App\Models\CLN\PatternGraph;
use App\Models\CLN\RuntimeGraphBuilder;
use Illuminate\Console\Command;

class DebugANDNodes extends Command
{
    protected $signature = 'cln:debug-and';
    protected $description = 'Debug AND node configuration';

    public function handle(): int
    {
        $this->info('Building graph...');
        $patternGraph = new PatternGraph();
        $builder = new RuntimeGraphBuilder();
        $runtimeGraph = $builder->build($patternGraph);

        $this->info('AND Nodes:');
        $this->newLine();

        foreach ($runtimeGraph->getAllColumns() as $column) {
            if ($column->type !== 'AND') continue;

            $this->line("AND Node: {$column->id} '{$column->name}'");
            $this->line("  Left source: " . ($column->leftSource ? "{$column->leftSource->id} '{$column->leftSource->name}'" : "NULL"));
            $this->line("  Right source: " . ($column->rightSource ? "{$column->rightSource->id} '{$column->rightSource->name}'" : "NULL"));
            $this->line("  Has SOM: " . ($column->som ? "YES" : "NO"));
            if ($column->som) {
                $this->line("    SOM release column: " . ($column->som->releaseColumn ? $column->som->releaseColumn->id : "NULL"));
            }
            $this->line("  Has VIP: " . ($column->vip ? "YES" : "NO"));
            $this->newLine();
        }

        return Command::SUCCESS;
    }
}

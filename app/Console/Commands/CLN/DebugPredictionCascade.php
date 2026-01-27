<?php

namespace App\Console\Commands\CLN;

use App\Models\CLN\PatternGraph;
use App\Models\CLN\RuntimeGraphBuilder;
use App\Services\CLN\WordParseResult;
use Illuminate\Console\Command;

class DebugPredictionCascade extends Command
{
    protected $signature = 'cln:debug-cascade';
    protected $description = 'Debug prediction cascade from CLAUSE';

    public function handle(): int
    {
        $this->info('Building graph...');
        $patternGraph = new PatternGraph();
        $builder = new RuntimeGraphBuilder();
        $runtimeGraph = $builder->build($patternGraph);

        // Activate CLAUSE
        $clauseColumn = $runtimeGraph->getColumnById('9537');
        if ($clauseColumn === null) {
            $this->error('CLAUSE column not found');
            return Command::FAILURE;
        }

        $clauseColumn->L5->activation = 1.0;
        $this->info("Activated CLAUSE column L5=1.0");
        $this->newLine();

        // Manually trace prediction propagation
        $this->info('=== Prediction Cascade ===');

        $maxLevel = 10;
        for ($level = $maxLevel; $level >= 1; $level--) {
            $columnsAtLevel = $runtimeGraph->getColumnsAtLevel($level);

            foreach ($columnsAtLevel as $column) {
                if ($column->L5->activation < 0.3) {
                    continue;
                }

                $this->line("Level {$level}: {$column->id} '{$column->name}' (L5={$column->L5->activation})");

                foreach ($column->feedbackDown as $pathway) {
                    $predictionStrength = $column->L5->activation * $pathway->weight;
                    $target = $pathway->target;

                    $currentExpected = $target->L23->expectedActivation;
                    $target->L23->expectedActivation = max($currentExpected, $predictionStrength);

                    $this->line("  -> Predicts {$target->id} '{$target->name}' (level {$target->hierarchicalLevel}) with strength {$predictionStrength}");
                    $this->line("     Target L23 expected now: {$target->L23->expectedActivation}");
                }
            }
        }

        $this->newLine();
        $this->info('=== Final Expected Activations at Level 1 ===');
        foreach ($runtimeGraph->getColumnsAtLevel(1) as $column) {
            if ($column->L23->expectedActivation > 0) {
                $this->line("{$column->id} '{$column->name}': expected={$column->L23->expectedActivation}");
            }
        }

        return Command::SUCCESS;
    }
}

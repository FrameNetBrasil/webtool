<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\ActivationEngine;
use App\Services\SeqGraph\SequenceGraphBuilder;
use Illuminate\Console\Command;

/**
 * Test command demonstrating sequence graph activation.
 *
 * Simulates parsing "the cat chased the mouse" as a sequence of
 * DET NOUN VERB DET NOUN elements through REF and CLAUSE patterns.
 */
class TestSequenceGraphCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sequence graph activation with "the cat chased the mouse"';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sequence Graph Activation Test');
        $this->info('==============================');
        $this->newLine();

        // Build pattern graphs
        $refPattern = [
            'nodes' => [
                'start' => ['type' => 'start'],
                'det' => ['type' => 'element', 'elementType' => 'DET'],
                'noun' => ['type' => 'element', 'elementType' => 'NOUN'],
                'end' => ['type' => 'end'],
            ],
            'edges' => [
                ['from' => 'start', 'to' => 'det'],
                ['from' => 'det', 'to' => 'noun'],
                ['from' => 'noun', 'to' => 'end'],
            ],
        ];

        $clausePattern = [
            'nodes' => [
                'start' => ['type' => 'start'],
                'subj' => ['type' => 'element', 'elementType' => 'REF'],
                'verb' => ['type' => 'element', 'elementType' => 'VERB'],
                'obj' => ['type' => 'element', 'elementType' => 'REF'],
                'end' => ['type' => 'end'],
            ],
            'edges' => [
                ['from' => 'start', 'to' => 'subj'],
                ['from' => 'subj', 'to' => 'verb'],
                ['from' => 'verb', 'to' => 'obj'],
                ['from' => 'obj', 'to' => 'end'],
            ],
        ];

        // Build sequence graphs
        $builder = new SequenceGraphBuilder;
        $refGraph = $builder->build('REF', $refPattern);
        $clauseGraph = $builder->build('CLAUSE', $clausePattern);

        // Create engine and register graphs
        $engine = new ActivationEngine;
        $engine->registerGraph($refGraph);
        $engine->registerGraph($clauseGraph);
        $engine->initialize();

        $this->info('Initial State:');
        $this->printState($engine->getState());
        $this->newLine();

        // Input sequence: "the cat chased the mouse"
        $inputs = [
            ['type' => 'DET', 'value' => 'the'],
            ['type' => 'NOUN', 'value' => 'cat'],
            ['type' => 'VERB', 'value' => 'chased'],
            ['type' => 'DET', 'value' => 'the'],
            ['type' => 'NOUN', 'value' => 'mouse'],
        ];

        foreach ($inputs as $input) {
            $this->info("Input: {$input['type']} '{$input['value']}'");
            $result = $engine->processInput($input['type'], $input['value']);

            $this->line('  Fired nodes: '.$this->formatFiredNodes($result->firedNodes));
            $this->line('  Completed patterns: '.$this->formatCompletedPatterns($result->completedPatterns));
            $this->line('  New listeners: '.$this->formatNewListeners($result->newListeners));

            $this->newLine();
            $this->printState($engine->getState());
            $this->newLine();
        }

        $this->info('Test completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Print current state of all graphs.
     *
     * @param  array<string, mixed>  $state  State information
     */
    private function printState(array $state): void
    {
        $this->line("Time: {$state['time']}");

        foreach ($state['graphs'] as $patternName => $graphState) {
            $this->line("  Pattern: {$patternName}");

            if (count($graphState['activeListeners']) > 0) {
                $this->line('    Active listeners:');
                foreach ($graphState['activeListeners'] as $listener) {
                    $this->line("      - {$listener['id']} ({$listener['elementType']})");
                }
            } else {
                $this->line('    Active listeners: none');
            }

            if (count($graphState['nodes']) > 0) {
                $this->line('    Fired nodes:');
                foreach ($graphState['nodes'] as $node) {
                    $timestamps = implode(', ', $node['timestamps']);
                    $this->line("      - {$node['id']} ({$node['elementType']}): [{$timestamps}]");
                }
            }
        }
    }

    /**
     * Format fired nodes for display.
     *
     * @param  array<array{0: string, 1: string}>  $firedNodes  Fired nodes
     * @return string Formatted string
     */
    private function formatFiredNodes(array $firedNodes): string
    {
        if (count($firedNodes) === 0) {
            return 'none';
        }

        $formatted = [];
        foreach ($firedNodes as [$pattern, $nodeId]) {
            $formatted[] = "{$pattern}:{$nodeId}";
        }

        return implode(', ', $formatted);
    }

    /**
     * Format completed patterns for display.
     *
     * @param  array<string>  $completedPatterns  Completed patterns
     * @return string Formatted string
     */
    private function formatCompletedPatterns(array $completedPatterns): string
    {
        if (count($completedPatterns) === 0) {
            return 'none';
        }

        return implode(', ', $completedPatterns);
    }

    /**
     * Format new listeners for display.
     *
     * @param  array<array{0: string, 1: string}>  $newListeners  New listeners
     * @return string Formatted string
     */
    private function formatNewListeners(array $newListeners): string
    {
        if (count($newListeners) === 0) {
            return 'none';
        }

        $formatted = [];
        foreach ($newListeners as [$pattern, $nodeId]) {
            $formatted[] = "{$pattern}:{$nodeId}";
        }

        return implode(', ', $formatted);
    }
}

<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\ActivationEngine;
use App\Services\SeqGraph\PatternGraphLoader;
use App\Services\SeqGraph\SequenceGraphBuilder;
use Illuminate\Console\Command;

/**
 * Test command for parsing sentences using database patterns.
 *
 * Loads pattern graphs from the database and processes a sentence
 * through the sequence graph activation engine.
 */
class TestSentenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:test-sentence
                            {tokens?* : Tokens in format POS:word (e.g., DET:the NOUN:cat VERB:runs)}
                            {--patterns=* : Pattern names to load (default: common patterns)}
                            {--all : Load all patterns from database}
                            {--render : Render sequence graphs to DOT and PNG files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sentence parsing using database patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sequence Graph Sentence Parser (Database Patterns)');
        $this->info('==================================================');
        $this->newLine();

        // Load patterns from database
        $loader = new PatternGraphLoader;
        $builder = new SequenceGraphBuilder;

        // Enable rendering if requested
        if ($this->option('render')) {
            $builder->withRenderer();
        }

        $patterns = $this->determinePatterns($loader);

        if (empty($patterns)) {
            $this->error('No patterns loaded!');

            return Command::FAILURE;
        }

        $this->info('Loaded '.count($patterns).' patterns: '.implode(', ', array_keys($patterns)));

        if ($this->option('render')) {
            $this->info('Rendering enabled. Output: '.$builder->getRenderer()->getOutputDir());
        }

        $this->newLine();

        // Build sequence graphs
        $sequenceGraphs = [];
        foreach ($patterns as $patternName => $patternGraph) {
            $sequenceGraphs[$patternName] = $builder->build($patternName, $patternGraph);
        }

        // Initialize activation engine
        $engine = new ActivationEngine;
        foreach ($sequenceGraphs as $graph) {
            $engine->registerGraph($graph);
        }
        $engine->initialize();

        // Get tokens to process
        $tokens = $this->getTokens();

        if (empty($tokens)) {
            $this->warn('No tokens provided. Showing initial state only.');
            $this->newLine();
            $this->printState($engine->getState());

            return Command::SUCCESS;
        }

        $this->info('Processing '.count($tokens).' tokens:');
        $this->line('  '.implode(' ', array_map(fn ($t) => "{$t['type']}:{$t['value']}", $tokens)));
        $this->newLine();

        $this->info('Initial State:');
        $this->printState($engine->getState());
        $this->newLine();

        // Process each token
        foreach ($tokens as $index => $token) {
            $this->info('Step '.($index + 1).": {$token['type']} '{$token['value']}'");
            $result = $engine->processInput($token['type'], $token['value']);

            $this->line('  Fired nodes: '.$this->formatFiredNodes($result->firedNodes));
            $this->line('  Completed patterns: '.$this->formatCompletedPatterns($result->completedPatterns));
            $this->line('  New listeners: '.$this->formatNewListeners($result->newListeners));

            $this->newLine();
            $this->printState($engine->getState());
            $this->newLine();
        }

        $this->info('Parsing completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Determine which patterns to load.
     *
     * @param  PatternGraphLoader  $loader  Pattern graph loader
     * @return array<string, array<string, mixed>> Pattern graphs
     */
    private function determinePatterns(PatternGraphLoader $loader): array
    {
        if ($this->option('all')) {
            return $loader->loadAll();
        }

        $patternNames = $this->option('patterns');

        if (! empty($patternNames)) {
            return $loader->loadByNames($patternNames);
        }

        // Default: load common patterns for basic sentence parsing
        $defaultPatterns = ['NOUN', 'VERB', 'DET', 'ADJ', 'PRON', 'REF'];

        return $loader->loadByNames($defaultPatterns);
    }

    /**
     * Get tokens from command arguments or prompt user.
     *
     * @return array<array{type: string, value: string}> Tokens
     */
    private function getTokens(): array
    {
        $tokenArgs = $this->argument('tokens');

        if (! empty($tokenArgs)) {
            return $this->parseTokenArgs($tokenArgs);
        }

        // Prompt user for tokens
        $this->line('Enter tokens in format POS:word (e.g., DET:the NOUN:cat VERB:runs)');
        $this->line('Or press Enter to see initial state only');
        $input = $this->ask('Tokens');

        if (empty($input)) {
            return [];
        }

        $tokenArgs = explode(' ', $input);

        return $this->parseTokenArgs($tokenArgs);
    }

    /**
     * Parse token arguments.
     *
     * @param  array<string>  $tokenArgs  Token arguments in format POS:word
     * @return array<array{type: string, value: string}> Parsed tokens
     */
    private function parseTokenArgs(array $tokenArgs): array
    {
        $tokens = [];

        foreach ($tokenArgs as $tokenArg) {
            if (! str_contains($tokenArg, ':')) {
                $this->warn("Skipping invalid token format: {$tokenArg} (expected POS:word)");

                continue;
            }

            [$type, $value] = explode(':', $tokenArg, 2);
            $tokens[] = ['type' => $type, 'value' => $value];
        }

        return $tokens;
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
            // Skip patterns with no activity
            if (count($graphState['activeListeners']) === 0 && count($graphState['nodes']) === 0) {
                continue;
            }

            $this->line("  Pattern: {$patternName}");

            if (count($graphState['activeListeners']) > 0) {
                $this->line('    Active listeners:');
                foreach ($graphState['activeListeners'] as $listener) {
                    $this->line("      - {$listener['id']} ({$listener['elementType']})");
                }
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

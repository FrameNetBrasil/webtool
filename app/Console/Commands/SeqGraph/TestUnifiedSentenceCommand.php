<?php

namespace App\Console\Commands\SeqGraph;

use App\Services\SeqGraph\PatternGraphLoader;
use App\Services\SeqGraph\ResultGraphBuilder;
use App\Services\SeqGraph\ResultGraphRenderer;
use App\Services\SeqGraph\SequenceGraphRenderer;
use App\Services\SeqGraph\UnifiedActivationEngine;
use App\Services\SeqGraph\UnifiedSequenceGraphBuilder;
use Illuminate\Console\Command;

/**
 * Test command for parsing sentences using the unified sequence graph.
 *
 * Loads pattern graphs from the database, builds a unified graph combining
 * all patterns, and processes a sentence through the unified activation engine.
 */
class TestUnifiedSentenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seqgraph:test-unified
                            {tokens?* : Tokens in format POS:word (e.g., DET:the NOUN:cat VERB:runs)}
                            {--patterns=* : Pattern names to load (default: common patterns)}
                            {--all : Load all patterns from database}
                            {--render : Render unified graph to DOT and PNG files}
                            {--result : Generate and render result parse tree}
                            {--result-pattern= : Filter result tree to show only trees rooted at this pattern (e.g., CLAUSE)}
                            {--max-roots=1 : Maximum number of root trees to display in text output}
                            {--quiet-steps : Hide step-by-step output (useful with --result)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sentence parsing using unified sequence graph';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Unified Sequence Graph Sentence Parser');
        $this->info('======================================');
        $this->newLine();

        // Load patterns from database
        $loader = new PatternGraphLoader;
        $patterns = $this->determinePatterns($loader);

        if (empty($patterns)) {
            $this->error('No patterns loaded!');

            return Command::FAILURE;
        }

        $this->info('Loaded '.count($patterns).' patterns: '.implode(', ', array_keys($patterns)));
        $this->newLine();

        // Build unified graph
        $this->info('Building unified graph...');
        $builder = new UnifiedSequenceGraphBuilder;
        $graph = $builder->build($patterns);

        $this->info('  - Nodes: '.count($graph->nodes));
        $this->info('  - Edges: '.count($graph->edges));
        $this->info('  - Pattern nodes: '.count($graph->patternNodeIds));
        $this->newLine();

        // Render if requested
        if ($this->option('render')) {
            $renderer = new SequenceGraphRenderer;
            $result = $renderer->renderUnified($graph);
            $this->info('Rendered unified graph:');
            $this->line("  - DOT: {$result['dotPath']}");
            if ($result['imagePath']) {
                $this->line("  - PNG: {$result['imagePath']}");
            }
            $this->newLine();
        }

        // Initialize activation engine
        $engine = new UnifiedActivationEngine($graph);
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

        $quietSteps = $this->option('quiet-steps');

        if (! $quietSteps) {
            $this->info('Initial State:');
            $this->printState($engine->getState());
            $this->newLine();
        }

        // Process each token
        foreach ($tokens as $index => $token) {
            if (! $quietSteps) {
                $this->info('Step '.($index + 1).": {$token['type']} '{$token['value']}'");
            }

            $result = $engine->processInput($token['type'], $token['value']);

            if (! $quietSteps) {
                $this->line('  Fired nodes: '.$this->formatFiredNodes($result->firedNodes));
                $this->line('  Completed patterns: '.$this->formatCompletedPatterns($result->completedPatterns));
                $this->line('  New listeners: '.$this->formatNewListeners($result->newListeners));

                $this->newLine();
                $this->printState($engine->getState());
                $this->newLine();
            }
        }

        $this->info('Parsing completed successfully!');
        $this->newLine();

        // Generate result graph if requested
        if ($this->option('result')) {
            $this->generateResultGraph($engine, $tokens);
        }

        return Command::SUCCESS;
    }

    /**
     * Generate and render the result parse tree.
     *
     * @param  UnifiedActivationEngine  $engine  The activation engine
     * @param  array<array{type: string, value: string}>  $tokens  Input tokens
     */
    private function generateResultGraph(UnifiedActivationEngine $engine, array $tokens): void
    {
        $this->info('Generating Result Parse Tree');
        $this->info('============================');
        $this->newLine();

        // Get parse events
        $events = $engine->getParseEvents();
        $this->info('Recorded '.count($events).' parse events.');
        $this->newLine();

        // Build result tree
        $builder = new ResultGraphBuilder;
        $roots = $builder->build($events);

        if (empty($roots)) {
            $this->warn('No parse tree generated (no pattern completions recorded).');

            return;
        }

        // Show root patterns
        $rootPatterns = array_count_values(array_map(fn ($r) => $r->patternName, $roots));
        arsort($rootPatterns);
        $this->info('Root pattern distribution:');
        foreach ($rootPatterns as $pattern => $count) {
            $this->line("  - {$pattern}: {$count}");
        }
        $this->newLine();

        // Filter by pattern if specified
        $filterPattern = $this->option('result-pattern');
        if ($filterPattern) {
            $roots = array_filter($roots, fn ($r) => $r->patternName === $filterPattern);
            $roots = array_values($roots);
            $this->info('Filtered to '.count($roots)." root(s) matching pattern: {$filterPattern}");
        } else {
            $this->info('Found '.count($roots).' root node(s).');
        }

        if (empty($roots)) {
            $this->warn('No matching root nodes found.');

            return;
        }

        $this->newLine();

        // Render text tree
        $renderer = new ResultGraphRenderer;
        $maxRoots = (int) $this->option('max-roots');
        $textTree = $renderer->generateText($roots, $maxRoots);

        $this->info('Parse Tree:');
        $this->line($textTree);
        $this->newLine();

        // Render to DOT/PNG (only render filtered roots if filter was applied)
        $sentenceStr = implode('_', array_map(fn ($t) => $t['value'], $tokens));
        $filename = 'result_'.substr($sentenceStr, 0, 30);
        if ($filterPattern) {
            $filename .= "_{$filterPattern}";
        }

        $result = $renderer->render($roots, $filename);

        $this->info('Rendered result graph:');
        $this->line("  - DOT: {$result['dotPath']}");
        if ($result['imagePath']) {
            $this->line("  - PNG: {$result['imagePath']}");
        }
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
     * Print current state of the unified graph.
     *
     * @param  array<string, mixed>  $state  State information
     */
    private function printState(array $state): void
    {
        $this->line("Time: {$state['time']}");

        // Group active listeners by pattern
        $listenersByPattern = [];
        foreach ($state['activeListeners'] as $listener) {
            $pattern = $listener['pattern'] ?? 'GLOBAL';
            if (! isset($listenersByPattern[$pattern])) {
                $listenersByPattern[$pattern] = [];
            }
            $listenersByPattern[$pattern][] = $listener;
        }

        if (! empty($listenersByPattern)) {
            $this->line('Active listeners:');
            foreach ($listenersByPattern as $pattern => $listeners) {
                $this->line("  [{$pattern}]");
                foreach ($listeners as $listener) {
                    $this->line("    - {$listener['id']} ({$listener['elementType']})");
                }
            }
        }

        // Show fired nodes by pattern
        $hasFiredNodes = false;
        foreach ($state['patternStates'] as $patternName => $patternState) {
            if (! empty($patternState['firedNodes'])) {
                if (! $hasFiredNodes) {
                    $this->line('Fired nodes:');
                    $hasFiredNodes = true;
                }
                $this->line("  [{$patternName}]");
                foreach ($patternState['firedNodes'] as $node) {
                    $timestamps = implode(', ', $node['timestamps']);
                    $this->line("    - {$node['id']} ({$node['elementType']}): [{$timestamps}]");
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

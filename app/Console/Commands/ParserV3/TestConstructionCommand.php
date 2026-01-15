<?php

namespace App\Console\Commands\ParserV3;

use App\Enums\Parser\PhrasalCE;
use App\Models\Parser\PhrasalCENode;
use App\Services\Parser\ConstructionService;
use App\Services\Parser\PatternCompiler;
use Illuminate\Console\Command;

/**
 * Test Construction Pattern Matching
 *
 * Interactive command for testing BNF pattern compilation and matching.
 */
class TestConstructionCommand extends Command
{
    protected $signature = 'parser:test-construction
                            {pattern? : BNF pattern to test}
                            {--sentence= : Test sentence (if not provided, will prompt)}
                            {--show-graph : Display compiled graph}
                            {--show-dot : Display DOT format graph}
                            {--show-tokens : Show tokenization details}';

    protected $description = 'Test BNF construction pattern compilation and matching';

    public function handle(): int
    {
        $pattern = $this->argument('pattern');
        $sentence = $this->option('sentence');

        // Interactive mode if no pattern provided
        if (! $pattern) {
            $pattern = $this->ask('Enter BNF pattern');
        }

        if (! $sentence) {
            $sentence = $this->ask('Enter test sentence');
        }

        $this->info("Testing pattern: $pattern");
        $this->info("Against sentence: $sentence");
        $this->newLine();

        // Compile pattern
        $compiler = new PatternCompiler;

        try {
            $this->info('📝 Compiling pattern...');
            $graph = $compiler->compile($pattern);

            $this->info('✅ Pattern compiled successfully');
            $this->info('   Nodes: '.count($graph['nodes']));
            $this->info('   Edges: '.count($graph['edges']));
            $this->newLine();

            // Validate
            $validation = $compiler->validate($pattern);
            if (! $validation['valid']) {
                $this->warn('⚠️  Pattern has validation warnings:');
                foreach ($validation['errors'] as $error) {
                    $this->warn("   - $error");
                }
                $this->newLine();
            }

            // Show graph if requested
            if ($this->option('show-graph')) {
                $this->displayGraph($graph);
            }

            if ($this->option('show-dot')) {
                $this->info('📊 DOT format:');
                $this->line($compiler->toDot($graph));
                $this->newLine();
            }

            // Create mock tokens from sentence
            $tokens = $this->tokenizeSentence($sentence);
            $this->info('🔤 Tokenized: '.count($tokens).' tokens');
            if ($this->option('show-tokens')) {
                foreach ($tokens as $i => $token) {
                    $this->line("   [$i] {$token->word} ({$token->pos})");
                }
                $this->newLine();
            }

            // Test matching
            $service = new ConstructionService;
            $result = $service->testPattern($pattern, $tokens);

            if ($result['success'] && ! empty($result['matches'])) {
                $this->info("✅ Found {$result['matchCount']} match(es):");
                $this->newLine();

                foreach ($result['matches'] as $i => $match) {
                    $this->displayMatch($match, $i + 1);
                }
            } else {
                $this->warn('❌ No matches found');
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Display compiled graph structure
     */
    private function displayGraph(array $graph): void
    {
        $this->info('📊 Compiled Graph:');
        $this->newLine();

        $this->info('Nodes:');
        foreach ($graph['nodes'] as $nodeId => $node) {
            $label = $this->formatNodeLabel($node);
            $this->line("   $nodeId: $label");
        }

        $this->newLine();
        $this->info('Edges:');
        foreach ($graph['edges'] as $edge) {
            $bypass = isset($edge['bypass']) && $edge['bypass'] ? ' (bypass)' : '';
            $this->line("   {$edge['from']} → {$edge['to']}$bypass");
        }

        $this->newLine();
    }

    /**
     * Format node for display
     */
    private function formatNodeLabel(array $node): string
    {
        return match ($node['type']) {
            'START' => '<START>',
            'END' => '<END>',
            'LITERAL' => "'{$node['value']}'",
            'SLOT' => isset($node['constraint'])
                ? "{{$node['pos']}:{$node['constraint']}}"
                : "{{$node['pos']}}",
            'WILDCARD' => '{*}',
            'REP_CHECK' => '<CHECK>',
            default => $node['type'],
        };
    }

    /**
     * Display match result
     */
    private function displayMatch(array $match, int $num): void
    {
        $this->info("Match #$num:");
        $this->line("   Position: {$match['startPosition']} - {$match['endPosition']}");
        $this->line('   Matched tokens: '.implode(' ', $match['matchedTokens']));

        if (! empty($match['slots'])) {
            $this->line('   Slots:');
            foreach ($match['slots'] as $key => $value) {
                $this->line("      $key = $value");
            }
        }

        $this->newLine();
    }

    /**
     * Tokenize sentence into mock PhrasalCENodes
     *
     * This is a simplified tokenization for testing.
     * In production, use Trankit/UD parser.
     */
    private function tokenizeSentence(string $sentence): array
    {
        $words = explode(' ', trim($sentence));
        $tokens = [];

        foreach ($words as $i => $word) {
            $word = trim($word);
            if (empty($word)) {
                continue;
            }

            // Simple POS guessing (very basic!)
            $pos = $this->guessPOS($word);

            $tokens[] = new PhrasalCENode(
                word: $word,
                lemma: strtolower($word),
                pos: $pos,
                phrasalCE: PhrasalCE::HEAD, // Default
                features: ['lexical' => [], 'derived' => []],
                index: $i,
            );
        }

        return $tokens;
    }

    /**
     * Very basic POS guessing for testing
     */
    private function guessPOS(string $word): string
    {
        $word = strtolower($word);

        // Common Portuguese function words
        $posMap = [
            'o' => 'DET', 'a' => 'DET', 'os' => 'DET', 'as' => 'DET',
            'um' => 'DET', 'uma' => 'DET', 'uns' => 'DET', 'umas' => 'DET',
            'de' => 'ADP', 'da' => 'ADP', 'do' => 'ADP', 'em' => 'ADP', 'na' => 'ADP', 'no' => 'ADP',
            'para' => 'ADP', 'por' => 'ADP', 'com' => 'ADP',
            'e' => 'CCONJ', 'ou' => 'CCONJ', 'mas' => 'CCONJ',
            'que' => 'SCONJ', 'se' => 'SCONJ',
            'muito' => 'ADV', 'mais' => 'ADV', 'menos' => 'ADV',
            'mil' => 'NUM', 'cem' => 'NUM', 'cento' => 'NUM',
        ];

        // Check if word is a number
        if (is_numeric($word)) {
            return 'NUM';
        }

        return $posMap[$word] ?? 'NOUN'; // Default to NOUN
    }
}

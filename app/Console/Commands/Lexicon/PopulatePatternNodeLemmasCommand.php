<?php

namespace App\Console\Commands\Lexicon;

use App\Database\Criteria;
use Illuminate\Console\Command;

class PopulatePatternNodeLemmasCommand extends Command
{
    protected $signature = 'lexicon:populate-pattern-node-lemmas
                            {--dry-run : Simulate without database changes}';

    protected $description = 'Populate idLemma column in lexicon_pattern_node from idLexicon via view_lemma';

    private array $stats = [
        'nodes_processed' => 0,
        'nodes_updated' => 0,
        'nodes_no_lexicon' => 0,
        'nodes_no_lemma_found' => 0,
    ];

    public function handle(): int
    {
        $this->info('Populate Pattern Node Lemmas');
        $this->newLine();

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        // Get all pattern nodes
        $nodes = Criteria::table('lexicon_pattern_node')
            ->select('idLexiconPatternNode', 'idLexicon', 'idLemma')
            ->get();

        $this->info("Found {$this->formatNumber(count($nodes))} pattern nodes to process");
        $this->newLine();

        $this->withProgressBar($nodes, function ($node) use ($isDryRun) {
            $this->processNode($node, $isDryRun);
        });

        $this->newLine(2);
        $this->displaySummary($isDryRun);

        if ($isDryRun) {
            $this->newLine();
            $this->info('Dry run completed. Use without --dry-run to update nodes.');
        }

        return Command::SUCCESS;
    }

    private function processNode(object $node, bool $isDryRun): void
    {
        $this->stats['nodes_processed']++;

        // Skip if idLexicon is null
        if ($node->idLexicon === null) {
            $this->stats['nodes_no_lexicon']++;

            return;
        }

        // Look up idLemma from view_lemma using idLexicon
        $lemma = Criteria::table('view_lemma')
            ->where('idLexicon', '=', $node->idLexicon)
            ->select('idLemma')
            ->first();

        if (! $lemma) {
            $this->stats['nodes_no_lemma_found']++;

            return;
        }

        // Update the node with idLemma
        if (! $isDryRun) {
            Criteria::table('lexicon_pattern_node')
                ->where('idLexiconPatternNode', '=', $node->idLexiconPatternNode)
                ->update(['idLemma' => $lemma->idLemma]);
        }

        $this->stats['nodes_updated']++;
    }

    private function displaySummary(bool $isDryRun): void
    {
        $suffix = $isDryRun ? ' (would be)' : '';

        $this->info('Summary:');
        $this->newLine();

        $tableData = [
            ['Nodes processed', $this->formatNumber($this->stats['nodes_processed'])],
            ['Nodes updated'.$suffix, $this->formatNumber($this->stats['nodes_updated'])],
            ['Nodes without idLexicon', $this->formatNumber($this->stats['nodes_no_lexicon'])],
            ['Nodes with no lemma found', $this->formatNumber($this->stats['nodes_no_lemma_found'])],
        ];

        $this->table(['Metric', 'Count'], $tableData);
    }

    private function formatNumber(int $number): string
    {
        return number_format($number);
    }
}

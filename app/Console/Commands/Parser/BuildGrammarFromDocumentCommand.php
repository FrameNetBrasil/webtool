<?php

namespace App\Console\Commands\Parser;

use App\Database\Criteria;
use App\Repositories\Parser\GrammarGraph;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;

class BuildGrammarFromDocumentCommand extends Command
{
    protected $signature = 'parser:build-grammar-from-document
                            {idDocument : Document ID or range (e.g., 123 or 100-200)}
                            {--dry-run : Preview without applying changes}
                            {--limit= : Limit number of sentences to process}';

    protected $description = 'Build grammar graph from document sentences via UD parsing';

    private array $stats = [
        'total_sentences' => 0,
        'sentences_processed' => 0,
        'base_nodes_created' => 0,
        'base_links_created' => 0,
        'f_nodes_created' => 0,
        'links_created' => 0,
        'links_skipped' => 0,
        'unmapped_upos' => 0,
        'parse_errors' => 0,
    ];

    private const GRAMMAR_ID = 1; // Portuguese Basic Grammar

    private array $baseNodes = [];

    private array $fNodeCache = [];

    private bool $isDryRun = false;

    private TrankitService $trankit;

    public function handle(): int
    {
        $idDocumentArg = $this->argument('idDocument');
        $this->isDryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        // Parse document ID or range
        $documentIds = $this->parseDocumentArgument($idDocumentArg);

        if (empty($documentIds)) {
            $this->error('Invalid document ID or range format');

            return Command::FAILURE;
        }

        // Validate documents exist
        if (! $this->validateDocuments($documentIds)) {
            return Command::FAILURE;
        }

        $this->displayConfiguration($documentIds, $limit);

        // Initialize Trankit service
        $this->trankit = new TrankitService;
        $this->trankit->init(config('parser.trankit.url'));

        // Ensure base type nodes (E, R, A) exist
        $this->baseNodes = $this->ensureBaseTypeNodes();

        // Create base type transition links
        $this->createBaseTypeLinks();

        // Query sentences from all documents
        $sentences = $this->querySentences($documentIds, $limit);

        if (empty($sentences)) {
            $this->warn('No sentences found in document(s).');

            return Command::SUCCESS;
        }

        $this->stats['total_sentences'] = count($sentences);
        $this->info("Found {$this->stats['total_sentences']} sentences to process.");
        $this->newLine();

        // Process sentences
        $progressBar = $this->output->createProgressBar(count($sentences));
        $progressBar->start();

        foreach ($sentences as $sentence) {
            $this->processSentence($sentence);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    private function parseDocumentArgument(string $argument): array
    {
        // Check if it's a range (e.g., "100-200")
        if (str_contains($argument, '-')) {
            $parts = explode('-', $argument);
            if (count($parts) !== 2) {
                return [];
            }

            $start = (int) trim($parts[0]);
            $end = (int) trim($parts[1]);

            if ($start <= 0 || $end <= 0 || $start > $end) {
                return [];
            }

            return range($start, $end);
        }

        // Single document ID
        $id = (int) $argument;
        if ($id <= 0) {
            return [];
        }

        return [$id];
    }

    private function validateDocuments(array $documentIds): bool
    {
        // Check if documents exist using BETWEEN for efficiency
        if (count($documentIds) > 1) {
            $min = min($documentIds);
            $max = max($documentIds);

            $existingDocuments = Criteria::table('document')
                ->whereBetween('idDocument', [$min, $max])
                ->whereIn('idDocument', $documentIds)
                ->select('idDocument')
                ->all();

            $existingIds = array_map(fn ($doc) => $doc->idDocument, $existingDocuments);
            $missingIds = array_diff($documentIds, $existingIds);

            if (! empty($missingIds)) {
                $this->error('Document IDs not found: '.implode(', ', $missingIds));

                return false;
            }
        } else {
            // Single document
            $document = Criteria::table('document')
                ->where('idDocument', $documentIds[0])
                ->first();

            if (! $document) {
                $this->error("Document ID {$documentIds[0]} not found");

                return false;
            }
        }

        return true;
    }

    private function displayConfiguration(array $documentIds, ?string $limit): void
    {
        $this->info('Building Grammar Graph from Document Sentences');
        $this->line(str_repeat('-', 60));
        $this->line('Configuration:');
        $this->line('  - Grammar ID: '.self::GRAMMAR_ID.' (Portuguese Basic)');

        if (count($documentIds) === 1) {
            $this->line('  - Document ID: '.$documentIds[0]);
        } else {
            $this->line('  - Document IDs: '.min($documentIds).' - '.max($documentIds).' ('.count($documentIds).' documents)');
        }

        $this->line('  - Limit: '.($limit ?: 'No limit'));

        if ($this->isDryRun) {
            $this->warn('  - DRY RUN MODE - No changes will be applied');
        }

        $this->newLine();
    }

    private function querySentences(array $documentIds, ?string $limit): array
    {
        $query = Criteria::table('sentence as s')
            ->join('document_sentence as ds', 's.idSentence', '=', 'ds.idSentence')
            ->where('s.idLanguage', 1)
            ->select('s.idSentence', 's.text', 's.idLanguage', 'ds.idDocumentSentence', 'ds.idDocument')
            ->orderBy('ds.idDocument')
            ->orderBy('ds.idDocumentSentence');

        // Use whereIn or whereBetween based on number of documents
        if (count($documentIds) === 1) {
            $query->where('ds.idDocument', $documentIds[0]);
        } elseif (count($documentIds) === (max($documentIds) - min($documentIds) + 1)) {
            // Consecutive range - use BETWEEN for efficiency
            $query->whereBetween('ds.idDocument', [min($documentIds), max($documentIds)]);
        } else {
            // Non-consecutive IDs - use IN
            $query->whereIn('ds.idDocument', $documentIds);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        return $query->all();
    }

    private function ensureBaseTypeNodes(): array
    {
        $baseNodes = [];

        foreach (['E', 'R', 'A'] as $type) {
            $node = Criteria::table('parser_grammar_node')
                ->where('idGrammarGraph', self::GRAMMAR_ID)
                ->where('label', $type)
                ->where('type', $type)
                ->first();

            if (! $node) {
                if (! $this->isDryRun) {
                    $nodeId = GrammarGraph::createNode([
                        'idGrammarGraph' => self::GRAMMAR_ID,
                        'label' => $type,
                        'type' => $type,
                        'threshold' => 1,
                    ]);
                    $baseNodes[$type] = $nodeId;
                    $this->stats['base_nodes_created']++;
                } else {
                    $baseNodes[$type] = 0; // Placeholder for dry-run
                }
            } else {
                $baseNodes[$type] = $node->idGrammarNode;
            }
        }

        return $baseNodes;
    }

    private function createBaseTypeLinks(): void
    {
        // Create basic grammar links as specified:
        // F → E, E → R, R → E, A → R, R → A, R → R
        // Note: F nodes are created dynamically, so F→E links are created during sentence processing

        $transitions = [
            ['E', 'R', 0.9],  // Entity to Relational (subject -> verb)
            ['R', 'E', 0.9],  // Relational to Entity (verb -> object)
            ['A', 'R', 0.7],  // Attribute to Relational (adverb -> verb)
            ['R', 'A', 0.7],  // Relational to Attribute (verb -> adverb/adjective)
            ['R', 'R', 0.6],  // Relational to Relational (auxiliary verb -> main verb)
        ];

        foreach ($transitions as [$sourceType, $targetType, $weight]) {
            $this->createLinkIfNotExists(
                $this->baseNodes[$sourceType],
                $this->baseNodes[$targetType],
                'prediction',
                $weight,
                true // isBaseLink flag
            );
        }
    }

    private function processSentence(object $sentence): void
    {
        try {
            // Parse sentence with UD
            $result = $this->trankit->getUDTrankit($sentence->text, $sentence->idLanguage);
            $tokens = $result->udpipe;

            // Build node map for this sentence
            $nodeMap = [];

            foreach ($tokens as $token) {
                $lemma = mb_strtolower($token['lemma'], 'UTF-8');
                $upos = $token['pos'];

                // Map UPOS to semantic type
                $semanticType = $this->mapUPOSToSemanticType($upos);

                // Create or get node
                if ($semanticType == 'F') {
                    $nodeId = $this->createOrGetFNode($lemma);
                } else {
                    $nodeId = $this->baseNodes[$semanticType];
                }

                // Store for link creation
                $nodeMap[$token['id']] = [
                    'id' => $nodeId,
                    'type' => $semanticType,
                ];
            }

            // Create links from UD relations
            foreach ($tokens as $token) {
                if ($token['parent'] == 0) {
                    continue; // Skip root
                }

                $sourceNode = $nodeMap[$token['id']];
                $targetNode = $nodeMap[$token['parent']];

                // Only create F → E/R/A links (F → E is the basic link)
                if ($sourceNode['type'] == 'F' && $targetNode['type'] == 'E') {
                    $this->createLinkIfNotExists(
                        $sourceNode['id'],
                        $targetNode['id'],
                        'prediction',
                        1.0
                    );
                }
            }

            $this->stats['sentences_processed']++;
        } catch (\Exception $e) {
            logger()->error("Failed to process sentence {$sentence->idSentence}: ".$e->getMessage());
            $this->stats['parse_errors']++;
        }
    }

    private function createOrGetFNode(string $lemma): int
    {
        // Ensure lowercase for case-insensitive matching
        $lemma = mb_strtolower($lemma, 'UTF-8');

        // Check cache first
        if (isset($this->fNodeCache[$lemma])) {
            return $this->fNodeCache[$lemma];
        }

        // Check if exists in database
        $node = Criteria::table('parser_grammar_node')
            ->where('idGrammarGraph', self::GRAMMAR_ID)
            ->where('label', $lemma)
            ->where('type', 'F')
            ->first();

        if ($node) {
            $this->fNodeCache[$lemma] = $node->idGrammarNode;

            return $node->idGrammarNode;
        }

        // Create new F node
        if (! $this->isDryRun) {
            $nodeId = GrammarGraph::createNode([
                'idGrammarGraph' => self::GRAMMAR_ID,
                'label' => $lemma,
                'type' => 'F',
                'threshold' => 1,
            ]);
            $this->fNodeCache[$lemma] = $nodeId;
            $this->stats['f_nodes_created']++;

            return $nodeId;
        }

        return 0; // Placeholder for dry-run
    }

    private function createLinkIfNotExists(int $idSourceNode, int $idTargetNode, string $linkType, float $weight, bool $isBaseLink = false): bool
    {
        // Skip if dry-run and placeholder nodes
        if ($this->isDryRun && ($idSourceNode == 0 || $idTargetNode == 0)) {
            if ($isBaseLink) {
                $this->stats['base_links_created']++;
            } else {
                $this->stats['links_created']++;
            }

            return true;
        }

        // Check if exists
        $existing = Criteria::table('parser_grammar_link')
            ->where('idGrammarGraph', self::GRAMMAR_ID)
            ->where('idSourceNode', $idSourceNode)
            ->where('idTargetNode', $idTargetNode)
            ->first();

        if ($existing) {
            $this->stats['links_skipped']++;

            return false;
        }

        // Create link
        if (! $this->isDryRun) {
            GrammarGraph::createEdge([
                'idGrammarGraph' => self::GRAMMAR_ID,
                'idSourceNode' => $idSourceNode,
                'idTargetNode' => $idTargetNode,
                'linkType' => $linkType,
                'weight' => $weight,
            ]);
        }

        if ($isBaseLink) {
            $this->stats['base_links_created']++;
        } else {
            $this->stats['links_created']++;
        }

        return true;
    }

    private function mapUPOSToSemanticType(string $upos): string
    {
        $mappings = config('parser.wordTypeMappings');

        foreach ($mappings as $semanticType => $uposTags) {
            if (in_array($upos, $uposTags)) {
                return $semanticType;
            }
        }

        // Default fallback
        logger()->warning("Unmapped UPOS tag: {$upos}, defaulting to 'E'");
        $this->stats['unmapped_upos']++;

        return 'E';
    }

    private function displaySummary(): void
    {
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Sentences', $this->stats['total_sentences']],
                ['Sentences Processed', $this->stats['sentences_processed']],
                ['Base Nodes Created (E,R,A)', $this->stats['base_nodes_created']],
                ['Base Links Created (E→R→E, A→R→A, R→R)', $this->stats['base_links_created']],
                ['F Nodes Created', $this->stats['f_nodes_created']],
                ['Links Created (F→E)', $this->stats['links_created']],
                ['Links Skipped (existing)', $this->stats['links_skipped']],
                ['Unmapped UPOS', $this->stats['unmapped_upos']],
                ['Parse Errors', $this->stats['parse_errors']],
            ]
        );

        if ($this->isDryRun) {
            $this->newLine();
            $this->warn('⚠ DRY RUN - No changes were applied');
            $this->info('Run without --dry-run to apply these changes');
        }
    }
}

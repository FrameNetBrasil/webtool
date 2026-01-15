<?php

namespace App\Console\Commands\ParserV2;

use App\Data\Annotation\Corpus\AnnotationData;
use App\Data\Annotation\Corpus\SelectionData;
use App\Database\Criteria;
use App\Models\Parser\PhrasalCENode;
use App\Repositories\Parser\MWE;
use App\Services\Annotation\CorpusService;
use App\Services\AppService;
use App\Services\Parser\PhraseAssemblyService;
use App\Services\Trankit\TrankitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * Register CE labels (Phrasal and Clausal) as annotations
 *
 * Processes sentences from a document, runs the parser stages, and registers
 * both Stage 1 (Phrasal CEs - idLayerType=57) and Stage 2 (Clausal CEs - idLayerType=58)
 * labels as annotations.
 */
class RegisterCEAnnotationsCommand extends Command
{
    protected $signature = 'parser:register-ce-annotations
                            {idDocument : Document ID to process}
                            {--sentence= : Process only a specific sentence (idDocumentSentence)}
                            {--language=pt : Language code (pt, en)}
                            {--grammar= : Grammar graph ID for MWE detection}
                            {--dry-run : Show what would be done without making changes}
                            {--limit= : Limit number of sentences to process (ignored if --sentence is provided)}
                            {--layers=both : Which CE layers to register (phrasal, clausal, or both)}';

    protected $description = 'Register Phrasal and Clausal CE labels as annotations for document sentences';

    private TrankitService $trankit;

    private PhraseAssemblyService $assemblyService;

    private ?int $idGrammarGraph = null;

    private array $phrasalCEEntityMap = [];

    private array $clausalCEEntityMap = [];

    private array $stats = [
        'sentences_processed' => 0,
        'sentences_skipped' => 0,
        'phrasal_annotations_created' => 0,
        'clausal_annotations_created' => 0,
        'parse_errors' => 0,
        'mwes_detected' => 0,
    ];

    public function handle(): int
    {
        // Authenticate as user ID 6 for annotation operations
        Auth::loginUsingId(6);

        // Set current language (1 = Portuguese)
        AppService::setCurrentLanguage(1);

        $idDocument = (int) $this->argument('idDocument');
        $idDocumentSentence = $this->option('sentence') ? (int) $this->option('sentence') : null;
        $language = $this->option('language');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $layers = $this->option('layers') ?? 'both';

        // Validate layers option
        if (! in_array($layers, ['phrasal', 'clausal', 'both'])) {
            $this->error("Invalid layers option: {$layers}. Must be 'phrasal', 'clausal', or 'both'");

            return Command::FAILURE;
        }

        // Grammar graph for MWE detection
        $this->idGrammarGraph = $this->option('grammar') ? (int) $this->option('grammar') : null;

        // Validate document exists
        $document = Criteria::byId('document', 'idDocument', $idDocument);
        if (is_null($document)) {
            $this->error("Document not found: {$idDocument}");

            return Command::FAILURE;
        }

        $this->displayConfiguration($idDocument, $language, $dryRun, $limit, $layers, $idDocumentSentence);

        // Initialize services
        if (! $this->initServices()) {
            return Command::FAILURE;
        }

        // Load CE label entity maps
        $this->loadCEEntityMaps($layers);

        // Fetch sentences with annotation sets
        $sentences = $this->fetchDocumentSentences($idDocument, $limit, $idDocumentSentence);

        if (empty($sentences)) {
            if ($idDocumentSentence) {
                $this->warn("Sentence {$idDocumentSentence} not found or has no annotation set.");
            } else {
                $this->warn('No sentences with annotation sets found for this document.');
            }

            return Command::SUCCESS;
        }

        $this->info("Processing {$this->stats['sentences_processed']} sentences...");
        $this->newLine();

        // Process each sentence
        $progressBar = $this->output->createProgressBar(count($sentences));
        $progressBar->start();

        foreach ($sentences as $sentence) {
            $this->processSentence($sentence, $language, $layers, $dryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display statistics
        $this->displayStatistics();

        return Command::SUCCESS;
    }

    private function displayConfiguration(int $idDocument, string $language, bool $dryRun, ?int $limit, string $layers, ?int $idDocumentSentence = null): void
    {
        $this->info('Register CE Annotations Command');
        $this->line(str_repeat('─', 60));
        $this->line('Configuration:');
        $this->line("  • Document ID: {$idDocument}");

        if ($idDocumentSentence) {
            $this->line("  • Sentence ID: <fg=cyan>{$idDocumentSentence}</> (single sentence mode)");
        }

        $this->line("  • Language: {$language}");
        $this->line("  • CE Layers: {$layers}");
        $this->line('  • Dry run: '.($dryRun ? 'Yes' : 'No'));

        if (! $idDocumentSentence) {
            $this->line('  • Limit: '.($limit ?: 'No limit'));
        }

        if ($this->idGrammarGraph) {
            $this->line("  • Grammar Graph: ID {$this->idGrammarGraph}");
        } else {
            $this->line('  • Grammar Graph: <fg=yellow>None</> (use --grammar=ID for MWE detection)');
        }

        $this->newLine();
    }

    private function initServices(): bool
    {
        // Initialize Trankit
        $this->trankit = new TrankitService;
        $trankitUrl = config('parser.trankit.url');

        try {
            $this->trankit->init($trankitUrl);
            $this->info("Trankit service initialized at: {$trankitUrl}");
        } catch (\Exception $e) {
            $this->error("Failed to initialize Trankit: {$e->getMessage()}");

            return false;
        }

        // Initialize PhraseAssemblyService for Stage 2
        $this->assemblyService = app(PhraseAssemblyService::class);

        // Count MWEs in the grammar if provided
        if ($this->idGrammarGraph) {
            $simpleMWEs = MWE::listByFormat($this->idGrammarGraph, 'simple');
            $extendedMWEs = MWE::listByFormat($this->idGrammarGraph, 'extended');
            $variableMWEs = MWE::getFullyVariable($this->idGrammarGraph);

            $total = count($simpleMWEs) + count($extendedMWEs);
            $this->info("Grammar Graph loaded with {$total} MWEs:");
            $this->line('  • Simple (fixed words): '.count($simpleMWEs));
            $this->line('  • Extended (variable patterns): '.count($extendedMWEs));
            if (count($variableMWEs) > 0) {
                $this->line('    - Fully variable (no anchor): '.count($variableMWEs));
            }
        }

        return true;
    }

    private function loadCEEntityMaps(string $layers): void
    {
        // Load Phrasal CE label to idEntity mapping (idLayerType=57)
        if ($layers === 'phrasal' || $layers === 'both') {
            $phrasalLabels = Criteria::table('genericlabel')
                ->where('idLayerType', 57)
                ->where('idLanguage', 1)
                ->select('idEntity', 'name')
                ->get();

            foreach ($phrasalLabels as $label) {
                $this->phrasalCEEntityMap[$label->name] = $label->idEntity;
            }

            $this->info('Loaded '.count($this->phrasalCEEntityMap).' Phrasal CE labels: '.implode(', ', array_keys($this->phrasalCEEntityMap)));
        }

        // Load Clausal CE label to idEntity mapping (idLayerType=58)
        if ($layers === 'clausal' || $layers === 'both') {
            $clausalLabels = Criteria::table('genericlabel')
                ->where('idLayerType', 58)
                ->where('idLanguage', 1)
                ->select('idEntity', 'name')
                ->get();

            foreach ($clausalLabels as $label) {
                $this->clausalCEEntityMap[$label->name] = $label->idEntity;
            }

            $this->info('Loaded '.count($this->clausalCEEntityMap).' Clausal CE labels: '.implode(', ', array_keys($this->clausalCEEntityMap)));
        }
    }

    private function fetchDocumentSentences(int $idDocument, ?int $limit, ?int $idDocumentSentence = null): array
    {
        $query = Criteria::table('document_sentence as ds')
            ->join('sentence as s', 'ds.idSentence', '=', 's.idSentence')
            ->join('annotationset as a', 'ds.idDocumentSentence', '=', 'a.idDocumentSentence')
            ->where('ds.idDocument', $idDocument)
            ->where('a.status', '<>', 'DELETED')
            ->select(
                'ds.idDocumentSentence',
                'ds.idSentence',
                's.text',
                'a.idAnnotationSet'
            )
            ->orderBy('ds.idDocumentSentence');

        // If specific sentence requested, filter by it (takes precedence over limit)
        if ($idDocumentSentence) {
            $query->where('ds.idDocumentSentence', $idDocumentSentence);
        } elseif ($limit) {
            $query->limit($limit);
        }

        $sentences = $query->get()->toArray();
        $this->stats['sentences_processed'] = count($sentences);

        return $sentences;
    }

    private function processSentence(object $sentence, string $language, string $layers, bool $dryRun): void
    {
        try {
            // Get language ID
            $idLanguage = config('parser.languageMap')[$language] ?? 1;

            // NEW SINGLE-PASS TOKEN-BASED APPROACH
            // This simplifies the workflow while enabling dependency-aware MWE detection

            // STEP 1: Tokenize (preserving contractions)
            $tokens = $this->trankit->tokenizeSentence($sentence->text, false);

            if (empty($tokens)) {
                $this->stats['parse_errors']++;

                return;
            }

            // STEP 2: Parse with pre-tokenized input (preserving tokens in output)
            $result = $this->trankit->getUDTrankitTokensPreserved($tokens, $idLanguage);
            $udTokens = $result->udpipe ?? [];

            if (empty($udTokens)) {
                $this->stats['parse_errors']++;

                return;
            }

            // STEP 3: Build PhrasalCENodes WITH dependency info
            $phrasalNodes = [];
            foreach ($udTokens as $token) {
                $phrasalNodes[] = PhrasalCENode::fromUDToken($token);
            }

            // STEP 4: Detect MWEs using dependency-aware validation
            // CRITICAL: MWE detection happens AFTER parsing to use deprel/head for disambiguation
            $mweCandidates = [];
            $detectedMWEs = [];
            if ($this->idGrammarGraph) {
                [$mweCandidates, $detectedMWEs] = $this->detectMWEsWithDependencies($phrasalNodes);
                $this->stats['mwes_detected'] += count($detectedMWEs);
            }

            // STEP 5: Assemble validated MWEs into nodes
            if (! empty($detectedMWEs)) {
                $phrasalNodes = $this->assembleMWEs($phrasalNodes, $detectedMWEs, $language);
            }

            // STEP 6: Register annotations (unchanged)
            if ($layers === 'phrasal' || $layers === 'both') {
                $this->registerPhrasalAnnotations($sentence, $phrasalNodes, $dryRun);
            }

            if ($layers === 'clausal' || $layers === 'both') {
                $this->registerClausalAnnotations($sentence, $phrasalNodes, $language, $dryRun);
            }

        } catch (\Exception $e) {
            $this->stats['parse_errors']++;
            if ($this->output->isVerbose()) {
                $this->warn("Error processing sentence {$sentence->idDocumentSentence}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Map to store phrasal node index → annotation ID
     * This allows clausal CEs to reference their component phrasal CEs
     */
    private array $phrasalAnnotationMap = [];

    private function registerPhrasalAnnotations(object $sentence, array $nodes, bool $dryRun): void
    {
        // Remove previous phrasal annotations for this annotationset before registering new ones
        if (! $dryRun) {
            $this->removePreviousAnnotations($sentence->idAnnotationSet, 57);
        }

        // Reset the annotation map for this sentence
        $this->phrasalAnnotationMap = [];

        $sentenceText = $sentence->text;
        $currentPosition = 0;

        foreach ($nodes as $node) {
            // Get idEntity for the Phrasal CE label
            $ceLabel = $node->phrasalCE->value;
            $idEntity = $this->phrasalCEEntityMap[$ceLabel] ?? null;

            if (is_null($idEntity)) {
                if ($this->output->isVerbose()) {
                    $this->warn("CE label not found in database: {$ceLabel}");
                }

                continue;
            }

            // Calculate text span
            $wordText = $node->isMWE ? str_replace('^', ' ', $node->word) : $node->word;

            // Find the word in the sentence text
            $startChar = $this->findWordPosition($sentenceText, $wordText, $currentPosition);

            if ($startChar === false) {
                // Try finding individual words for MWE
                if ($node->isMWE) {
                    $words = explode('^', $node->word);
                    $startChar = $this->findWordPosition($sentenceText, $words[0], $currentPosition);
                    if ($startChar !== false) {
                        $lastWord = end($words);
                        $lastWordPos = $this->findWordPosition($sentenceText, $lastWord, $startChar);
                        if ($lastWordPos !== false) {
                            $endChar = $lastWordPos + mb_strlen($lastWord) - 1;
                            $currentPosition = $endChar + 1;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            } else {
                $endChar = $startChar + mb_strlen($wordText) - 1;
                $currentPosition = $endChar + 1;
            }

            // Store annotation data in map for later reference by clausal CEs
            // This is done regardless of dry-run mode so clausal CEs can reference phrasal spans
            $this->phrasalAnnotationMap[$node->index] = [
                'startChar' => $startChar,
                'endChar' => $endChar,
                'word' => $wordText,
                'idEntity' => $idEntity,
            ];

            if ($dryRun) {
                $this->line("  Would annotate: '{$wordText}' [{$startChar}-{$endChar}] as {$ceLabel}");

                continue;
            }

            // Create annotation using CorpusService::annotateObject
            try {
                $annotationData = new AnnotationData(
                    idAnnotationSet: $sentence->idAnnotationSet,
                    idEntity: $idEntity,
                    range: new SelectionData(
                        type: 'word',
                        start: (string) $startChar,
                        end: (string) $endChar
                    ),
                    selection: $wordText,
                    token: $wordText,
                    corpusAnnotationType: 'flex'
                );

                CorpusService::annotateObject($annotationData);
                $this->stats['phrasal_annotations_created']++;

            } catch (\Exception $e) {
                $this->warn("Failed to create annotation for '{$wordText}' (CE: {$ceLabel}): {$e->getMessage()}");
                if ($this->output->isVerbose()) {
                    $this->error($e->getTraceAsString());
                }
            }
        }
    }

    private function registerClausalAnnotations(object $sentence, array $phrasalNodes, string $language, bool $dryRun): void
    {
        // Remove previous clausal annotations for this annotationset before registering new ones
        if (! $dryRun) {
            $this->removePreviousAnnotations($sentence->idAnnotationSet, 58);
        }

        // Use PhraseAssemblyService to transform PhrasalCE → ClausalCE (Stage 2)
        $clausalNodes = $this->assemblyService->assemble($phrasalNodes, $language);

        if ($dryRun && $this->output->isVerbose()) {
            $this->info('  DEBUG: PhraseAssemblyService returned '.count($clausalNodes).' clausal nodes');
            $this->info('  DEBUG: Input had '.count($phrasalNodes).' phrasal nodes');
        }

        foreach ($clausalNodes as $clausalNode) {
            // Get idEntity for the Clausal CE label
            $ceLabel = $clausalNode->clausalCE->value;
            $idEntity = $this->clausalCEEntityMap[$ceLabel] ?? null;

            if (is_null($idEntity)) {
                if ($this->output->isVerbose()) {
                    $this->warn("Clausal CE label not found in database: {$ceLabel}");
                }

                continue;
            }

            // Get text span from component phrasal CE(s)
            // For simple nodes, this is just the single phrasal node's span
            // For compound nodes (like FPM "de condições"), this spans all component phrasal nodes
            $componentIndices = $this->getComponentIndices($clausalNode);

            if (empty($componentIndices)) {
                if ($this->output->isVerbose()) {
                    $this->warn("  Clausal node '{$clausalNode->getWord()}' has no component phrasal nodes");
                }

                continue;
            }

            // Calculate text span from component phrasal annotations
            $minStart = PHP_INT_MAX;
            $maxEnd = 0;
            $wordParts = [];

            foreach ($componentIndices as $index) {
                if (isset($this->phrasalAnnotationMap[$index])) {
                    $phrasalData = $this->phrasalAnnotationMap[$index];
                    $minStart = min($minStart, $phrasalData['startChar']);
                    $maxEnd = max($maxEnd, $phrasalData['endChar']);
                    $wordParts[] = $phrasalData['word'];
                }
            }

            if ($minStart === PHP_INT_MAX) {
                // No component phrasal CEs found in map, skip
                if ($this->output->isVerbose()) {
                    $this->warn("  No phrasal annotations found for clausal node '{$clausalNode->getWord()}'");
                }

                continue;
            }

            $startChar = $minStart;
            $endChar = $maxEnd;
            $wordText = implode(' ', $wordParts);

            if ($dryRun) {
                $this->line("  Would annotate (clausal): '{$wordText}' [{$startChar}-{$endChar}] as {$ceLabel}");

                continue;
            }

            // Create annotation using CorpusService::annotateObject
            try {
                $annotationData = new AnnotationData(
                    idAnnotationSet: $sentence->idAnnotationSet,
                    idEntity: $idEntity,
                    range: new SelectionData(
                        type: 'word',
                        start: (string) $startChar,
                        end: (string) $endChar
                    ),
                    selection: $wordText,
                    token: $wordText,
                    corpusAnnotationType: 'flex'
                );

                CorpusService::annotateObject($annotationData);
                $this->stats['clausal_annotations_created']++;

            } catch (\Exception $e) {
                $this->warn("Failed to create clausal annotation for '{$wordText}' (CE: {$ceLabel}): {$e->getMessage()}");
                if ($this->output->isVerbose()) {
                    $this->error($e->getTraceAsString());
                }
            }
        }
    }

    /**
     * Get component phrasal node indices from a clausal node
     *
     * For simple nodes: returns just the single node's index
     * For compound nodes (MWE-like): extracts indices from all component words
     *
     * @return array Array of phrasal node indices
     */
    private function getComponentIndices(\App\Models\Parser\ClausalCENode $clausalNode): array
    {
        $phrasalNode = $clausalNode->phrasalNode;

        // For MWE/compound nodes, we need to parse the word to find component indices
        if ($phrasalNode->isMWE || str_contains($phrasalNode->word, '^') || str_contains($phrasalNode->word, ' ')) {
            // Compound node - extract all component indices
            // The compound was created from multiple nodes, and we need to find them all
            // Since the compound uses the first node's index, we need to determine the range

            // For now, we'll use a simple approach: look for consecutive nodes in the map
            // starting from the compound's index
            $startIndex = $phrasalNode->index;
            $indices = [$startIndex];

            // Count how many words are in the compound
            // MWE uses ^, compound phrases from PhraseAssemblyService use space
            $wordCount = 0;
            if (str_contains($phrasalNode->word, '^')) {
                $wordCount = count(explode('^', $phrasalNode->word));
            } elseif (str_contains($phrasalNode->word, ' ')) {
                $wordCount = count(explode(' ', $phrasalNode->word));
            } else {
                $wordCount = 1;
            }

            // Add subsequent indices
            for ($i = 1; $i < $wordCount; $i++) {
                $indices[] = $startIndex + $i;
            }

            return $indices;
        }

        // Simple node - just return its index
        return [$phrasalNode->index];
    }

    /**
     * Find word position in sentence text, handling UTF-8 and case-insensitive matching
     */
    private function findWordPosition(string $text, string $word, int $offset): int|false
    {
        // Use mb_stripos for case-insensitive UTF-8 handling
        return mb_stripos($text, $word, $offset);
    }

    /**
     * Find a PhrasalCENode by its index
     *
     * @param  array  $nodes  Array of PhrasalCENode objects
     * @param  int  $index  The index to search for
     * @return \App\Models\Parser\PhrasalCENode|null The node or null if not found
     */
    private function findNodeByIndex(array $nodes, int $index): ?\App\Models\Parser\PhrasalCENode
    {
        foreach ($nodes as $node) {
            if ($node->index === $index) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Validate an MWE candidate using dependency relations for disambiguation.
     *
     * CRITICAL: This method uses dependency info to disambiguate ambiguous cases like:
     * - "gol contra" (compound noun MWE) vs "gol contra o adversário" (goal + PP)
     *
     * Disambiguation rules:
     * 1. If candidate word has deprel=case and head is OUTSIDE the MWE span -> NOT an MWE
     * 2. If candidate word has deprel=nmod/obl/obj/iobj linking to following noun -> NOT an MWE
     *
     * @param  array  $candidate  MWE candidate with startIndex and endIndex
     * @param  array  $nodes  Array of PhrasalCENode objects WITH dependency info
     * @return bool True if valid MWE, false if invalid
     */
    private function validateMWECandidate(array $candidate, array $nodes): bool
    {
        $candidateIndices = range($candidate['startIndex'], $candidate['endIndex']);

        foreach ($candidateIndices as $idx) {
            $node = $this->findNodeByIndex($nodes, $idx);
            if (! $node) {
                continue;
            }

            // Rule 1: Check if word is a case marker (ADP) linking outside the MWE
            if ($node->deprel === 'case' && ! in_array($node->head, $candidateIndices)) {
                // This ADP marks a phrase OUTSIDE the candidate -> NOT an MWE
                // Example: "contra o adversário" - "contra" has head="adversário" (outside "gol contra")
                return false;
            }

            // Rule 2: Check if word has a dependent that is outside the MWE span
            // (indicating it's governing a phrase, not part of a compound)
            foreach ($nodes as $otherNode) {
                if ($otherNode->head === $idx && ! in_array($otherNode->index, $candidateIndices)) {
                    // This word has dependents outside the MWE -> likely NOT an MWE
                    if (in_array($otherNode->deprel, ['nmod', 'obl', 'obj', 'iobj'])) {
                        return false;
                    }
                }
            }
        }

        return true; // Passes all validation rules
    }

    /**
     * Detect MWEs in parsed nodes using dependency relations for disambiguation.
     *
     * Two-phase detection:
     * Phase 1: Anchored patterns (fast lookup by firstWord/anchorWord)
     * Phase 2: Fully variable patterns (check all positions)
     *
     * @param  array  $nodes  Array of PhrasalCENode objects WITH dependency info (deprel, head)
     * @return array [candidates, detected] - detected MWEs have been validated
     */
    private function detectMWEsWithDependencies(array $nodes): array
    {
        $candidates = [];
        $detected = [];

        $nodesByPosition = array_values($nodes);

        // Phase 1: Anchored patterns (simple format uses firstWord, extended uses anchorWord)
        foreach ($nodesByPosition as $nodePosition => $node) {
            // Get simple-format MWEs starting with this word
            $simpleMWEs = MWE::getStartingWith($this->idGrammarGraph, strtolower($node->word));

            // Get extended-format MWEs anchored by this word
            $extendedMWEs = MWE::getByAnchorWord($this->idGrammarGraph, strtolower($node->word));

            // Process all anchored MWEs
            foreach (array_merge($simpleMWEs, $extendedMWEs) as $mwe) {
                $result = $this->tryMatchMWE($mwe, $nodesByPosition, $nodePosition, $nodes);
                if ($result !== null) {
                    if ($result['complete']) {
                        $detected[] = $result;
                    } else {
                        $candidates[] = $result;
                    }
                }
            }
        }

        // Phase 2: Fully variable patterns (no fixed word anchor)
        $variableMWEs = MWE::getFullyVariable($this->idGrammarGraph);
        foreach ($variableMWEs as $mwe) {
            foreach ($nodesByPosition as $nodePosition => $node) {
                $result = $this->tryMatchMWE($mwe, $nodesByPosition, $nodePosition, $nodes);
                if ($result !== null && $result['complete']) {
                    $detected[] = $result;
                }
            }
        }

        return [$candidates, $detected];
    }

    /**
     * Try to match an MWE pattern starting at a given position.
     *
     * Handles both simple (string array) and extended (type/value array) component formats.
     *
     * @param  object  $mwe  The MWE definition from database
     * @param  array  $nodesByPosition  Nodes indexed by position
     * @param  int  $anchorPosition  Position where anchor word was found
     * @param  array  $allNodes  All nodes for dependency validation
     * @return array|null Candidate array or null if no match possible
     */
    private function tryMatchMWE(object $mwe, array $nodesByPosition, int $anchorPosition, array $allNodes): ?array
    {
        $components = MWE::getParsedComponents($mwe);
        $threshold = count($components);

        // Calculate pattern start position based on anchor offset
        $anchorOffset = $mwe->anchorPosition ?? 0;
        $patternStartPosition = $anchorPosition - $anchorOffset;

        if ($patternStartPosition < 0) {
            return null; // Pattern would start before sentence
        }

        // Check if we have enough nodes for this pattern
        if ($patternStartPosition + $threshold > count($nodesByPosition)) {
            return null;
        }

        $startNode = $nodesByPosition[$patternStartPosition] ?? null;
        if ($startNode === null) {
            return null;
        }

        $candidate = [
            'idMWE' => $mwe->idMWE,
            'phrase' => $mwe->phrase,
            'components' => $components,
            'threshold' => $threshold,
            'startIndex' => $startNode->index,
            'activation' => 0,
            'matchedWords' => [],
        ];

        // Match each component
        $currentPosition = $patternStartPosition;
        foreach ($components as $i => $component) {
            if (! isset($nodesByPosition[$currentPosition])) {
                break;
            }

            $node = $nodesByPosition[$currentPosition];

            if (MWE::componentMatchesToken($component, $node)) {
                $candidate['activation']++;
                $candidate['matchedWords'][] = $node->word;
                $candidate['endIndex'] = $node->index;
                $currentPosition++;
            } else {
                break;
            }
        }

        if (! isset($candidate['endIndex'])) {
            $candidate['endIndex'] = $candidate['startIndex'];
        }

        // Validate with dependency relations
        if ($candidate['activation'] >= $threshold) {
            if ($this->validateMWECandidate($candidate, $allNodes)) {
                $candidate['complete'] = true;
            } else {
                $candidate['complete'] = false;
            }
        } else {
            $candidate['complete'] = false;
        }

        return $candidate;
    }

    /**
     * Detect MWEs in a sequence of nodes using prefix activation
     *
     * @return array [candidates, detected]
     */
    private function detectMWEs(array $nodes): array
    {
        $candidates = [];
        $detected = [];

        $nodesByPosition = array_values($nodes);

        foreach ($nodesByPosition as $nodePosition => $node) {
            $mwes = MWE::getStartingWith($this->idGrammarGraph, strtolower($node->word));

            foreach ($mwes as $mwe) {
                $components = MWE::getComponents($mwe);
                $threshold = count($components);

                $candidate = [
                    'idMWE' => $mwe->idMWE,
                    'phrase' => $mwe->phrase,
                    'components' => $components,
                    'threshold' => $threshold,
                    'startIndex' => $node->index,
                    'activation' => 1,
                    'matchedWords' => [$node->word],
                ];

                $currentNodePosition = $nodePosition;
                for ($i = 1; $i < $threshold; $i++) {
                    $nextPosition = $currentNodePosition + 1;

                    if (! isset($nodesByPosition[$nextPosition])) {
                        break;
                    }

                    $nextNode = $nodesByPosition[$nextPosition];

                    if (strtolower($nextNode->word) === strtolower($components[$i])) {
                        $candidate['activation']++;
                        $candidate['matchedWords'][] = $nextNode->word;
                        $candidate['endIndex'] = $nextNode->index;
                        $currentNodePosition = $nextPosition;
                    } else {
                        break;
                    }
                }

                if (! isset($candidate['endIndex'])) {
                    $candidate['endIndex'] = $node->index;
                }

                if ($candidate['activation'] >= $threshold) {
                    $candidate['complete'] = true;
                    $detected[] = $candidate;
                } else {
                    $candidate['complete'] = false;
                    $candidates[] = $candidate;
                }
            }
        }

        return [$candidates, $detected];
    }

    /**
     * Assemble validated MWEs into PhrasalCENodes
     *
     * Since we now have preserved tokens with full dependency info,
     * this method simply merges MWE component nodes.
     *
     * @param  array  $nodes  PhrasalCENodes with dependency info
     * @param  array  $detectedMWEs  Validated MWE candidates
     * @param  string  $language  Language code
     * @return array Modified nodes with MWEs assembled
     */
    private function assembleMWEs(array $nodes, array $detectedMWEs, string $language): array
    {
        $idLanguage = config('parser.languageMap')[$language] ?? 1;

        // Sort MWEs by start index (descending) to process from end to avoid index shifting
        usort($detectedMWEs, fn ($a, $b) => $b['startIndex'] <=> $a['startIndex']);

        foreach ($detectedMWEs as $mwe) {
            // Find component nodes
            $componentNodes = [];
            $startArrayIdx = null;
            $endArrayIdx = null;

            foreach ($nodes as $arrayIdx => $node) {
                if ($node->index >= $mwe['startIndex'] && $node->index <= $mwe['endIndex']) {
                    if ($startArrayIdx === null) {
                        $startArrayIdx = $arrayIdx;
                    }
                    $endArrayIdx = $arrayIdx;
                    $componentNodes[] = $node;
                }
            }

            if ($startArrayIdx !== null && ! empty($componentNodes)) {
                // Get POS from lexicon
                $mwePos = MWE::getPOS($mwe['phrase'], $idLanguage);

                // Create MWE node
                $mweNode = PhrasalCENode::fromMWEComponents(
                    $componentNodes,
                    count($componentNodes),
                    $mwePos
                );

                $mweNode->word = $mwe['phrase'];

                // Replace component nodes with single MWE node
                array_splice($nodes, $startArrayIdx, count($componentNodes), [$mweNode]);
            }
        }

        return $nodes;
    }

    /**
     * Remove previous annotations and their associated textspans for an annotationset
     *
     * @param  int  $idAnnotationSet  The annotation set ID
     * @param  int|null  $idLayerType  Optional layer type to filter (57=phrasal, 58=clausal)
     */
    private function removePreviousAnnotations(int $idAnnotationSet, ?int $idLayerType = null): void
    {
        // Get all textspans associated with this annotationset
        $query = Criteria::table('textspan')
            ->where('idAnnotationSet', $idAnnotationSet);

        // Filter by layer type if specified
        if ($idLayerType !== null) {
            $query->where('idLayerType', $idLayerType);
        }

        $textspans = $query->pluck('idTextSpan')->toArray();

        if (! empty($textspans)) {
            // Delete annotations associated with these textspans
            Criteria::table('annotation')
                ->whereIn('idTextSpan', $textspans)
                ->delete();

            // Delete the textspans themselves
            Criteria::table('textspan')
                ->whereIn('idTextSpan', $textspans)
                ->delete();
        }
    }

    private function displayStatistics(): void
    {
        $this->info('Statistics');
        $this->line(str_repeat('─', 60));

        $stats = [
            ['Sentences Processed', $this->stats['sentences_processed']],
            ['Sentences Skipped', $this->stats['sentences_skipped']],
            ['Phrasal Annotations Created', $this->stats['phrasal_annotations_created']],
            ['Clausal Annotations Created', $this->stats['clausal_annotations_created']],
            ['Parse Errors', $this->stats['parse_errors']],
        ];

        if ($this->idGrammarGraph) {
            $stats[] = ['MWEs Detected', $this->stats['mwes_detected']];
        }

        $this->table(['Metric', 'Value'], $stats);
    }
}

<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\PredictionEntry;
use App\Data\CLN\SequenceResult;
use App\Models\CLN_RNT\CLNColumn;

/**
 * Column Sequence Manager
 *
 * Manages a sequence of CLN columns for processing token sequences using
 * node-centric architecture with event-driven cross-column communication.
 *
 * Key responsibilities:
 * - Initialize column sequence for input length
 * - Create and link columns (bidirectional)
 * - Coordinate token processing across columns
 * - Emit events for cross-column pattern matching and prediction checking
 * - Collect and aggregate parsing results
 * - Extract confirmed constructions and parse trees
 *
 * Architecture:
 * - Uses NodeEventRegistry for O(1) cross-column communication
 * - Event-driven pattern matching (replaces O(N²) loops)
 * - Event-driven prediction confirmation (replaces O(N) backward search)
 */
class ColumnSequenceManager
{
    /**
     * Columns indexed by position
     */
    private array $columns = [];

    /**
     * Current processing position
     */
    private int $currentPosition = 0;

    /**
     * Compiled constructions loaded from database
     */
    private array $compiledConstructions = [];

    /**
     * Partial construction manager
     */
    private PartialConstructionManager $partialConstructionManager;

    /**
     * Ghost manager for null instantiation
     */
    private GhostManager $ghostManager;

    /**
     * Node factory
     */
    private NodeFactory $factory;

    /**
     * Shared event registry for cross-column communication
     */
    private ?NodeEventRegistry $eventRegistry = null;

    /**
     * Stack-based prediction registry indexed by construction name
     *
     * Format: ['constructionName' => [PredictionEntry, ...]]
     *
     * Stores predictions from L5 partial constructions. Each construction
     * name maps to a stack of PredictionEntry objects (LIFO), allowing
     * the most recent (innermost) predictions to be matched first.
     */
    private array $predictionRegistry = [];

    /**
     * Create a new Column Sequence Manager
     *
     * @param  PartialConstructionManager|null  $partialConstructionManager  Optional manager
     * @param  GhostManager|null  $ghostManager  Optional ghost manager
     * @param  NodeFactory|null  $factory  Optional factory
     * @param  NodeEventRegistry|null  $eventRegistry  Optional event registry
     */
    public function __construct(
        ?PartialConstructionManager $partialConstructionManager = null,
        ?GhostManager $ghostManager = null,
        ?NodeFactory $factory = null,
        ?NodeEventRegistry $eventRegistry = null
    ) {
        $this->partialConstructionManager = $partialConstructionManager ?? new PartialConstructionManager;
        $this->ghostManager = $ghostManager ?? new GhostManager;
        $this->factory = $factory ?? new NodeFactory;

        // Initialize event registry for node-centric architecture
        if (config('cln.node_centric_enabled', false)) {
            $this->eventRegistry = $eventRegistry ?? new NodeEventRegistry;
        }
    }

    // ========================================================================
    // Sequence Management
    // ========================================================================

    /**
     * Set constructions for pattern matching
     *
     * Distributes compiled constructions to all columns for pattern matching.
     *
     * @param  array  $constructions  Compiled constructions from CLNParser
     */
    public function setConstructions(array $constructions): void
    {
        $this->compiledConstructions = $constructions;

        // Distribute to all existing columns
        foreach ($this->columns as $column) {
            $column->setConstructions($constructions);
        }
    }

    /**
     * Initialize sequence for given length
     *
     * Creates columns and links them bidirectionally.
     *
     * @param  int  $maxLength  Maximum sequence length
     */
    public function initializeSequence(int $maxLength): void
    {
        $this->columns = [];
        $this->currentPosition = 0;

        // Create columns
        for ($i = 0; $i < $maxLength; $i++) {
            $column = new CLNColumn($i, $this->factory, $this->ghostManager, $this->eventRegistry);

            // Set manager reference for centralized prediction control
            $column->setSequenceManager($this);

            $this->columns[$i] = $column;
        }

        // Link columns
        $this->linkColumns();
    }

    /**
     * Link columns bidirectionally
     */
    private function linkColumns(): void
    {
        for ($i = 0; $i < count($this->columns); $i++) {
            if ($i > 0) {
                $this->columns[$i]->setPreviousColumn($this->columns[$i - 1]);
            }

            if ($i < count($this->columns) - 1) {
                $this->columns[$i]->setNextColumn($this->columns[$i + 1]);
            }
        }
    }

    /**
     * Get or create column at position
     *
     * @param  int  $position  Column position
     * @return CLNColumn Column at position
     */
    public function getOrCreateColumn(int $position): CLNColumn
    {
        if (! isset($this->columns[$position])) {
            $column = new CLNColumn($position, $this->factory, $this->ghostManager, $this->eventRegistry);

            // Pass constructions to new column
            $column->setConstructions($this->compiledConstructions);

            $this->columns[$position] = $column;

            // Link to neighbors if they exist
            if (isset($this->columns[$position - 1])) {
                $this->columns[$position]->setPreviousColumn($this->columns[$position - 1]);
                $this->columns[$position - 1]->setNextColumn($this->columns[$position]);
            }

            if (isset($this->columns[$position + 1])) {
                $this->columns[$position]->setNextColumn($this->columns[$position + 1]);
                $this->columns[$position + 1]->setPreviousColumn($this->columns[$position]);
            }
        }

        return $this->columns[$position];
    }

    /**
     * Get column at position
     *
     * @param  int  $position  Column position
     * @return CLNColumn|null Column or null if doesn't exist
     */
    public function getColumn(int $position): ?CLNColumn
    {
        return $this->columns[$position] ?? null;
    }

    /**
     * Get all columns
     *
     * @return array All columns indexed by position
     */
    public function getAllColumns(): array
    {
        return $this->columns;
    }

    // ========================================================================
    // Processing
    // ========================================================================

    /**
     * Process single token at position
     *
     * Processing flow order:
     * 1. Activate L23 from input token (creates word, POS, feature nodes)
     * 2. Check for predicted nodes in previous columns via event emission
     * 3. Update partial constructions from previous columns via event emission
     * 4. Process column normally (L23 → L5 propagation, pattern matching)
     *
     * @param  int  $position  Column position
     * @param  object  $token  Trankit token
     * @return \App\Data\CLN\ColumnActivationResult Processing result
     */
    public function processToken(int $position, object $token): \App\Data\CLN\ColumnActivationResult
    {
        $column = $this->getOrCreateColumn($position);

        // Process column through standard processing flow
        $result = $column->processInput($token, $position);

        $this->currentPosition = $position;

        return $result;
    }

    /**
     * Update partial constructions from previous columns
     *
     * Node-centric architecture: Emit ACTIVATED event and let subscribed partial
     * constructions check themselves. This eliminates the O(N²) loop over all
     * previous columns, replacing it with O(1) event emission.
     *
     * Processing flow:
     * 1. Emit ACTIVATED event at current position with L23 nodes
     * 2. Subscribed partial constructions (from previous columns) receive event
     * 3. Partials call tryAdvance() to check if current token matches next expected element
     * 4. When partials complete, they emit CONSTRUCTION_CONFIRMED event
     * 5. CONSTRUCTION_CONFIRMED triggers L23 feedback creation (handled by L5Layer)
     *
     * @param  int  $position  Current token position
     * @param  CLNColumn  $currentColumn  Current column with activated L23
     */
    private function updateCrossColumnPartialConstructions(int $position, CLNColumn $currentColumn): void
    {
        if (! $this->eventRegistry) {
            return;
        }

        $l23Nodes = $currentColumn->getL23()->getAllNodes();

        // Emit ACTIVATED event at this position
        // Partial constructions from previous columns are already subscribed via
        // L5Layer::subscribePredictionsToPosition() when they were created
        $this->eventRegistry->emitAtPosition(
            $position,
            \App\Models\CLN_RNT\NodeEvent::ACTIVATED,
            [
                'l23_nodes' => $l23Nodes,
                'position' => $position,
            ]
        );

        // Construction confirmations and L23 feedback creation happen via node events
    }

    /**
     * Check all previous columns for predicted nodes that match current token
     *
     * Node-centric architecture: Emit ACTIVATED event with token and let subscribed
     * predicted nodes check themselves. This eliminates the O(N) backward search,
     * replacing it with O(1) event emission.
     *
     * Processing flow:
     * 1. Emit ACTIVATED event at current position with token data
     * 2. Subscribed predicted nodes (from previous columns) receive event
     * 3. Predicted nodes call checkMatch() to see if token matches prediction
     * 4. On match, predicted nodes emit PREDICTION_CONFIRMED event
     * 5. PREDICTION_CONFIRMED triggers backward confirmation link creation
     *
     * @param  int  $position  Current token position
     * @param  object  $token  Current token
     * @return array Empty array (confirmations happen via events)
     */
    private function checkAllPreviousColumnsForPredictions(int $position, object $token): array
    {
        if (! $this->eventRegistry) {
            return [];
        }

        // Emit ACTIVATED event with token data
        // Predicted nodes from previous columns are already subscribed via
        // L23Layer::subscribePredictedNode() when they were created
        $this->eventRegistry->emitAtPosition(
            $position,
            \App\Models\CLN_RNT\NodeEvent::ACTIVATED,
            [
                'token' => $token,
                'position' => $position,
            ]
        );

        // Predicted nodes confirm themselves and emit events
        // Confirmation links are established in the event handlers
        return [];
    }

    /**
     * Process entire token sequence
     *
     * Main entry point for parsing a sentence.
     *
     * @param  array  $tokens  Array of Trankit tokens
     * @return SequenceResult Complete sequence processing result
     */
    public function processSequence(array $tokens): SequenceResult
    {
        // Initialize sequence
        $this->initializeSequence(count($tokens));

        $columnResults = [];

        // Process each token
        foreach ($tokens as $position => $token) {
            $result = $this->processToken($position, $token);
            $columnResults[$position] = $result;
        }

        // Collect confirmed constructions
        $confirmedConstructions = $this->getAllActiveConstructions();

        // Get construction spans
        $constructionSpans = $this->getConstructionSpans();

        // Build parse tree (simplified for now)
        $parseTree = $this->getParseTree();

        // Calculate statistics
        $statistics = $this->calculateStatistics($columnResults);

        return new SequenceResult(
            tokens: $tokens,
            columnResults: $columnResults,
            confirmedConstructions: $confirmedConstructions,
            partialConstructions: $this->getAllPartialConstructions(),
            parseTree: $parseTree,
            totalConfidence: $statistics['total_confidence'],
            statistics: $statistics
        );
    }

    // ========================================================================
    // Introspection
    // ========================================================================

    /**
     * Get all active constructions (confirmed)
     *
     * @return array Array of construction nodes
     */
    public function getAllActiveConstructions(): array
    {
        $constructions = [];

        foreach ($this->columns as $column) {
            $l5 = $column->getL5();
            $columnConstructions = $l5->getNodesByType('construction');

            foreach ($columnConstructions as $construction) {
                $constructions[] = [
                    'id' => $construction->id,
                    'construction_id' => $construction->metadata['construction_id'] ?? null,
                    'name' => $construction->metadata['name'] ?? null,
                    'position' => $column->position,
                    'pattern' => $construction->metadata['pattern'] ?? [],
                    'matched' => $construction->metadata['matched'] ?? [],
                ];
            }
        }

        return $constructions;
    }

    /**
     * Get all partial constructions
     *
     * @return array Array of partial construction nodes
     */
    private function getAllPartialConstructions(): array
    {
        $partials = [];

        foreach ($this->columns as $column) {
            $l5 = $column->getL5();
            $columnPartials = $l5->getPartialConstructions();

            foreach ($columnPartials as $partial) {
                $partials[] = [
                    'id' => $partial->id,
                    'construction_id' => $partial->metadata['construction_id'] ?? null,
                    'name' => $partial->metadata['name'] ?? null,
                    'position' => $column->position,
                    'pattern' => $partial->metadata['pattern'] ?? [],
                    'matched' => $partial->metadata['matched'] ?? [],
                    'activation' => $this->partialConstructionManager->getPartialConstructionActivation($partial, $l5),
                ];
            }
        }

        return $partials;
    }

    /**
     * Get construction spans
     *
     * Returns position ranges for each construction.
     *
     * @return array Array of construction spans
     */
    public function getConstructionSpans(): array
    {
        $spans = [];

        foreach ($this->getAllActiveConstructions() as $construction) {
            $anchorPos = $construction['position'];
            $pattern = $construction['pattern'];
            $span = count($pattern);

            $spans[] = [
                'construction_id' => $construction['construction_id'],
                'name' => $construction['name'],
                'start' => $anchorPos,
                'end' => $anchorPos + $span - 1,
                'length' => $span,
            ];
        }

        return $spans;
    }

    /**
     * Get parse tree
     *
     * Builds hierarchical representation of constructions.
     * Simplified for now - just returns flat list.
     *
     * @return array Parse tree structure
     */
    public function getParseTree(): array
    {
        $constructions = $this->getAllActiveConstructions();

        // TODO: Build hierarchical tree based on construction embedding
        // For now, return flat list with position information

        return [
            'type' => 'sequence',
            'constructions' => $constructions,
        ];
    }

    /**
     * Calculate sequence statistics
     *
     * @param  array  $columnResults  Results from each column
     * @return array Statistics
     */
    private function calculateStatistics(array $columnResults): array
    {
        $totalActivation = 0.0;
        $predictionMatches = 0;
        $partialConstructionsActivated = 0;
        $constructionsConfirmed = 0;

        foreach ($columnResults as $result) {
            $totalActivation += $result->totalActivation;

            if ($result->hasPredictionMatch) {
                $predictionMatches++;
            }

            $partialConstructionsActivated += count($result->activatedPartialConstructions);
            $constructionsConfirmed += count($result->confirmedConstructions);
        }

        $tokenCount = count($columnResults);

        return [
            'token_count' => $tokenCount,
            'total_activation' => $totalActivation,
            'average_activation' => $tokenCount > 0 ? $totalActivation / $tokenCount : 0,
            'prediction_matches' => $predictionMatches,
            'prediction_accuracy' => $tokenCount > 0 ? $predictionMatches / $tokenCount : 0,
            'partial_constructions_activated' => $partialConstructionsActivated,
            'constructions_confirmed' => $constructionsConfirmed,
            'total_confidence' => $tokenCount > 0 ? $predictionMatches / $tokenCount : 0,
        ];
    }

    // ========================================================================
    // Centralized Prediction Management
    // ========================================================================

    /**
     * Register a prediction from L5 partial construction
     *
     * Stores prediction in stack-based registry indexed by construction name.
     * Most recent predictions are at the top of the stack (LIFO).
     *
     * @param  string  $constructionName  Construction name (e.g., "HEAD", "ARG")
     * @param  int  $sourceColumn  Column position where L5 partial lives
     * @param  string  $type  Prediction type: 'word', 'pos', 'feature', 'construction'
     * @param  string  $value  Expected value
     * @param  float  $strength  Prediction strength (0-1)
     * @param  string  $sourcePartialId  L5 partial construction node ID
     * @param  int  $constructionId  Database construction ID
     * @param  array  $metadata  Additional metadata
     */
    public function registerPrediction(
        string $constructionName,
        int $sourceColumn,
        string $type,
        string $value,
        float $strength,
        string $sourcePartialId,
        int $constructionId,
        array $metadata = []
    ): void {
        $entry = new PredictionEntry(
            constructionName: $constructionName,
            sourceColumn: $sourceColumn,
            type: $type,
            value: $value,
            strength: $strength,
            sourcePartialId: $sourcePartialId,
            constructionId: $constructionId,
            metadata: $metadata
        );

        // Initialize stack if not exists
        if (! isset($this->predictionRegistry[$constructionName])) {
            $this->predictionRegistry[$constructionName] = [];
        }

        // Push to stack (LIFO - most recent first)
        array_push($this->predictionRegistry[$constructionName], $entry);
    }

    /**
     * Check for and pop waiting prediction by construction name
     *
     * Searches the prediction registry for a prediction matching the given
     * construction name. If found, pops it from the stack (LIFO) and returns it.
     *
     * @param  string  $constructionName  Construction name to match
     * @return PredictionEntry|null Prediction entry if found, null otherwise
     */
    public function checkForPrediction(string $constructionName): ?PredictionEntry
    {
        $stack = $this->predictionRegistry[$constructionName] ?? [];

        if (empty($stack)) {
            return null;
        }

        // Pop from top of stack (most recent prediction)
        $entry = array_pop($stack);

        // Update registry
        if (empty($stack)) {
            // Clean up empty stacks
            unset($this->predictionRegistry[$constructionName]);
        } else {
            $this->predictionRegistry[$constructionName] = $stack;
        }

        return $entry;
    }

    /**
     * Cleanup old predictions based on TTL
     *
     * Removes predictions that have exceeded their time-to-live.
     * Called periodically during token processing.
     *
     * @return int Number of predictions cleaned up
     */
    public function cleanupOldPredictions(): int
    {
        $ttl = config('cln.predictions.ttl', 60.0);
        $cleanedCount = 0;

        foreach ($this->predictionRegistry as $constructionName => $stack) {
            // Filter out expired entries
            $filteredStack = array_filter(
                $stack,
                fn ($entry) => ! $entry->isExpired($ttl)
            );

            $removedCount = count($stack) - count($filteredStack);
            $cleanedCount += $removedCount;

            if (empty($filteredStack)) {
                // Remove empty stacks
                unset($this->predictionRegistry[$constructionName]);
            } else {
                // Re-index array after filtering
                $this->predictionRegistry[$constructionName] = array_values($filteredStack);
            }
        }

        return $cleanedCount;
    }

    /**
     * Get prediction statistics for debugging
     *
     * @return array Statistics about current predictions
     */
    public function getPredictionStatistics(): array
    {
        $totalPredictions = 0;
        $constructionCounts = [];

        foreach ($this->predictionRegistry as $constructionName => $stack) {
            $count = count($stack);
            $totalPredictions += $count;
            $constructionCounts[$constructionName] = $count;
        }

        return [
            'total_predictions' => $totalPredictions,
            'construction_types' => count($this->predictionRegistry),
            'construction_counts' => $constructionCounts,
        ];
    }

    /**
     * Reset sequence
     *
     * Clears all columns and state.
     */
    public function reset(): void
    {
        foreach ($this->columns as $column) {
            $column->reset();
        }

        $this->columns = [];
        $this->currentPosition = 0;
        $this->predictionRegistry = [];
    }
}

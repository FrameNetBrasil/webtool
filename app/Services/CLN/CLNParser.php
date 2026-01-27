<?php

namespace App\Services\CLN;

use App\Models\CLN\Binding;
use App\Models\CLN\FunctionalColumn;
use App\Models\CLN\ParsingState;
use App\Models\CLN\PatternGraph;
use App\Models\CLN\PatternNodeType;
use App\Models\CLN\PendingConstruction;
use App\Models\CLN\RuntimeGraph;
use App\Models\CLN\RuntimeGraphBuilder;
use App\Models\CLN\SOMMode;

/**
 * CLN v4 Main Parser
 */
class CLNParser
{
    private InputParserService $inputParser;

    private ActivationDynamics $activationDynamics;

    private RuntimeGraph $graph;

    private LexiconInterface $lexicon;

    private ParsingState $state;

    /**
     * Activation history for parse graph generation
     * [timestep => [columnId => activation]]
     */
    private array $activationHistory = [];

    /**
     * Parser configuration
     */
    private array $config;

    public function __construct(
        ?InputParserService $inputParser = null,
        array $config = [],
    ) {
        $this->inputParser = $inputParser ?? new InputParserService;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->graph = new RuntimeGraph;
        //        $this->activationDynamics = new ActivationDynamics($this->graph);
        $this->state = new ParsingState;
    }

    /**
     * Parse a sentence into constructions
     *
     * Main entry point for the CLN v3 parser.
     *
     * @param  string|array  $input  Sentence string or array of words
     * @return array Parse result with constructions and metadata
     */
    public function parse(string|array $input): array
    {
        // Convert string to words if needed
        $sentence = is_array($input) ? implode(' ', $input) : $input;

        // Parse sentence to get word data
        $wordData = $this->inputParser->parseForL1Nodes($sentence);

        if (empty($wordData)) {
            return $this->emptyResult($sentence, 'No words parsed');
        }

        // Step 1: Define the pattern graph (your grammar)

        $patternGraph = new PatternGraph;

        // Step 2: Build the runtime graph
        $builder = new RuntimeGraphBuilder;
        $this->graph = $runtimeGraph = $builder->build($patternGraph);
        $this->graph->output_dir = $this->config['output_dir'];

        // Reset everything for new parse
        $this->graph->resetForNewParse();
        $this->state->reset();
        $this->activationHistory = [];

        // Prime initial predictions
        $this->primeInitialPredictions();

        $results = [];

        foreach ($wordData as $i => $word) {
            if ($word['pos'] == 'PUNCT') {
                continue;
            }
            $result = $this->processWord($word);
            $results[] = $result;
            $this->generateGraph($wordData, $i);
            $this->state->advanceTime();
        }

        // Generate parse graph showing only activated constructions (bottom-up)
        $this->generateParseGraph($wordData);

        // Display results
        foreach ($results as $wordResult) {
            echo "--- Time {$wordResult->time}: '{$wordResult->word}' ---\n";

            foreach ($wordResult->events as $event) {
                echo "  • {$event}\n";
            }

            echo "\n";
        }

        // Show final bindings
        echo "=== Final Bindings ===\n";
        $state = $this->getState();
        foreach ($state->completedBindings as $binding) {
            echo "  '{$binding->filler->id}' -> {$binding->role} of '{$binding->slot->id}' ";
            echo '(strength: '.round($binding->strength, 2).", time: {$binding->boundAtTime})\n";
        }

        // Show active columns at end
        echo "\n=== Active Columns at End ===\n";
        foreach ($state->activeColumns as $col) {
            echo "  '{$col->id}' L5={$col->L5->activation}\n";
        }

        return $results;

        // Step 3: Inspect what was built
        //        echo "Columns created:\n";
        //        foreach ($runtimeGraph->getAllColumns() as $col) {
        //            echo "  - {$col->id} (level {$col->hierarchicalLevel}, type {$col->type})\n";
        //            echo "    Feedforward up: " . count($col->feedforwardUp) . " pathways\n";
        //            echo "    Feedback down: " . count($col->feedbackDown) . " pathways\n";
        //            if ($col->som !== null) {
        //                echo "    Has SOM inhibitor (releases on: {$col->som->releaseColumn->id})\n";
        //            }
        //        }
        //
        //        echo "\nPathways created: " . count($runtimeGraph->getAllPathways()) . "\n";
        //        foreach ($runtimeGraph->getAllPathways() as $path) {
        //            $gated = $path->gatingInhibitor !== null ? " [GATED]" : "";
        //            echo "  - {$path->source->id}.{$path->sourceLayer} -> {$path->target->id}.{$path->targetLayer} ({$path->direction->value}){$gated}\n";
        //        }

    }

    /**
     * Prime the network with initial top-down predictions.
     * The sentence-level expects an NP (subject), which expects DET/NOUN, etc.
     */
    private function primeInitialPredictions(): void
    {
        // Find the highest-level column (CLAUSE) to prime
        // This should be a column with feedback pathways that can generate predictions
        $clauseColumn = null;

        // Strategy 1: Look for a column named 'CLAUSE' or 'S'
        foreach ($this->graph->getAllColumns() as $column) {
            if (strtoupper($column->name) === 'CLAUSE' || strtoupper($column->name) === 'S') {
                $clauseColumn = $column;
                break;
            }
        }

        // Strategy 2: If not found, use the highest hierarchical level column with feedback pathways
        if ($clauseColumn === null) {
            $maxLevel = 0;
            foreach ($this->graph->getAllColumns() as $column) {
                if (count($column->feedbackDown) > 0 && $column->hierarchicalLevel > $maxLevel) {
                    $maxLevel = $column->hierarchicalLevel;
                    $clauseColumn = $column;
                }
            }
        }

        if ($clauseColumn !== null) {
            // Temporarily activate CLAUSE to generate predictions
            $clauseColumn->L5->activation = 1.0;

            // Multi-level prediction cascade during priming
            // We allow predictions to propagate through the hierarchy by treating
            // expected columns as if they have weak baseline activity for prediction purposes only
            $maxIterations = 10;
            for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
                $dummyResult = new WordParseResult;

                // Generate predictions
                $this->propagateFeedbackForPriming($dummyResult);

                // Check if any new expectations were created
                // If not, cascade is complete
                $hasNewExpectations = false;
                foreach ($this->graph->getAllColumns() as $column) {
                    if ($column->L23->expectedActivation > 0.1) {
                        $hasNewExpectations = true;
                        break;
                    }
                }

                if (! $hasNewExpectations && $iteration > 0) {
                    break;
                }
            }

            // CRITICAL: Reset all L5 activations after priming
            // The expectations (L23) remain, but L5 must come from actual input only
            foreach ($this->graph->getAllColumns() as $column) {
                $column->L5->activation = 0.0;
                $column->L5->temporalHistory = [];
            }
        }
    }

    /**
     * Process a single word - the core gamma cycle.
     */
    public function processWord(array $word): WordParseResult
    {
        $result = new WordParseResult;
        $result->word = $word['word'];
        $result->time = $this->state->currentTime;

        // =========================================================
        // STEP 1: Prepare for New Input
        // - Clear transient layers (L4, L23) from previous cycle
        // - Apply decay to sustained layer (L5)
        // =========================================================
        $this->prepareForNewInput();

        // =========================================================
        // STEP 2: Lexical Lookup and Initial Activation
        // - Now L4 is clear and ready to receive new input
        // =========================================================
        $activatedColumns = $this->activateLexicalColumns($word, $result);

        // Step 3: Identify which are POS columns (for boundary detection)
        $activatedPOSColumns = $this->filterPOSColumns($activatedColumns);

        // Step 4: Propagate within columns (L4 -> L5)
        $this->propagateWithinColumns($activatedColumns, $result);

        // Step 5: Compute prediction errors
        $this->computePredictionErrors($activatedColumns, $result);

        // Step 6: Record temporal history
        $this->recordTemporalHistory($activatedColumns);

        // Step 7: Clear predictions (they've been used, now prepare for new ones)
        $this->clearPredictionsForNextCycle();

        // Step 8: Check for theta boundaries (both AND and OR)
        $this->checkThetaBoundaries($activatedPOSColumns, $result);

        // Step 9: Activate SOMs for OR nodes receiving input
        $this->activateORBlocking($activatedColumns, $result);

        // Step 10: Multi-level feedforward propagation with construction activation
        // We need to iterate because constructions activate in layers
        $maxIterations = 5;  // Enough for deep hierarchies
        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            // Propagate L4 → L5 for construction nodes that received input
            $this->propagateL4toL5ForConstructions($result);

            // Propagate feedforward from newly activated constructions
            $this->propagateFeedforward($result);

            // Activate SOMs for OR construction nodes that just received L4 input
            // This must happen AFTER propagateFeedforward adds L4 activation to constructions
            $this->activateORBlocking($activatedColumns, $result);

            // Check if any new L4 activations occurred on OR nodes
            // (AND nodes don't propagate L4→L5, so we don't check them)
            $hasNewL4 = false;
            foreach ($this->graph->getAllColumns() as $column) {
                if ($column->type === PatternNodeType::OR) {
                    if ($column->L4->activation >= $this->state->activationThreshold) {
                        $hasNewL4 = true;
                        break;
                    }
                }
            }

            if (! $hasNewL4 && $iteration > 0) {
                break;
            }
        }

        // Step 11: Record temporal history AGAIN after construction activation
        // This ensures construction node L5 activations are captured
        $this->recordTemporalHistory($activatedColumns);

        // Step 12: NOW check for AND completions and new pending constructions
        // This happens AFTER construction nodes have activated
        $this->checkANDCompletions($activatedColumns, $result);
        $this->checkNewPendingConstructions($activatedColumns, $result);

        // Step 13: Record temporal history ONE MORE TIME after AND completions
        // This ensures AND node L5 activations (SUBJECT, OBJECT) are captured
        $this->recordTemporalHistory($activatedColumns);

        // Step 14: Generate predictions (feedback)
        $this->propagateFeedback($result);

        // Step 15: Update active columns list
        $this->updateActiveColumns();

        return $result;
    }

    /**
     * Step 1: Prepare the network for new input.
     * Clear transient layers and decay sustained activations.
     */
    private function prepareForNewInput(): void
    {
        foreach ($this->graph->getAllColumns() as $column) {
            // Clear TRANSIENT layers (these are fresh each cycle)
            $column->L4->activation = 0.0;           // Input layer - cleared before new input
            $column->L23->activation = 0.0;          // Error layer - recomputed each cycle
            // $column->L23->expectedActivation = 0.0;  // Predictions - regenerated each cycle

            // DECAY sustained layer (L5 persists but fades)
            $column->L5->activation *= (1.0 - $this->state->decayRate);
        }
    }

    /**
     * Step 2: Find and activate columns for the incoming word.
     * Now L4 is already cleared, so we just set the new activations.
     */
    private function activateLexicalColumns(array $word, WordParseResult $result): array
    {
        $activated = [];

        // Check for literal match first
        $literalColumn = $this->graph->getColumnByLiteral($word['word']);
        if ($literalColumn !== null) {
            $literalColumn->L4->activation = 1.0;
            $activated[] = $literalColumn;
            $result->events[] = "Activated LITERAL column '{$literalColumn->id}' for word '{$word['word']}'";
        }

        // Look up POS tags and activate those columns
        // $posTags = $this->lexicon->lookup($word['word']);
        $posTags = [$word['pos']];
        foreach ($posTags as $pos) {
            $posColumn = $this->graph->getColumnByPOS($pos);
            if ($posColumn !== null) {
                $posColumn->L4->activation = 1.0;
                $activated[] = $posColumn;
                $result->events[] = "Activated POS column '{$pos}/{$posColumn->name}' for word '{$word['word']}'";
            }
        }

        $result->activatedColumns = $activated;

        return $activated;
    }

    /**
     * Step 3: Filter to get only POS-type columns from activated columns.
     */
    private function filterPOSColumns(array $columns): array
    {
        return array_filter($columns, function ($col) {
            return $col->type === PatternNodeType::POS;
        });
    }

    /**
     * Step 4: Propagate activation from L4 to L5 within each activated column.
     * The decayed L5 value is AUGMENTED by the new L4 input.
     */
    private function propagateWithinColumns(array $columns, WordParseResult $result): void
    {
        foreach ($columns as $column) {
            // New input (L4) adds to the existing (decayed) representation (L5)
            $previousL5 = $column->L5->activation;  // Already decayed in Step 1
            $newInput = $column->L4->activation;

            // Combine: the new input refreshes/strengthens the representation
            $column->L5->activation = min(1.0, $previousL5 + $newInput);

            $result->events[] = "Column '{$column->id}/{$column->name}' L5: {$previousL5} + {$newInput} = {$column->L5->activation}";
        }
    }

    /**
     * Step 5: Compute prediction errors based on what was expected.
     */
    private function computePredictionErrors(array $columns, WordParseResult $result): void
    {
        foreach ($columns as $column) {
            $actual = $column->L5->activation;
            $expected = $column->L23->expectedActivation;

            // Prediction error: positive means surprise, negative means omission
            $error = $actual - $expected;

            // L23 activation represents the magnitude of surprise
            // Only positive errors propagate (unexpected occurrences)
            $column->L23->activation = max(0.0, $error);

            if ($error > 0.1) {
                $result->events[] = "Prediction error at '{$column->id}/{$column->name}': expected {$expected}, got {$actual}, error={$error}";
            }
        }
    }

    /**
     * Step 6 and Step 11 and Step 13 : Record current L5 activation in temporal history.
     */
    private function recordTemporalHistory(array $activatedColumns): void
    {
        $currentTime = $this->state->currentTime;
        $windowSize = $this->state->windowSize;

        // Initialize activation history for this timestep
        $this->activationHistory[$currentTime] = [];

        // Record history for all columns (not just activated ones)
        // because we need to track decay too
        foreach ($this->graph->getAllColumns() as $column) {
            // Add current activation to history
            $column->L5->temporalHistory[$currentTime] = $column->L5->activation;

            // Also record for parse graph generation (only significant activations)
            if ($column->L5->activation >= 0.3) {
                $this->activationHistory[$currentTime][$column->id] = $column->L5->activation;
            }

            // Prune old history beyond window
            foreach ($column->L5->temporalHistory as $time => $act) {
                if ($time < $currentTime - $windowSize) {
                    unset($column->L5->temporalHistory[$time]);
                }
            }
        }
    }

    /**
     * Step 7: Clear predictions AFTER error computation, BEFORE generating new ones.
     */
    private function clearPredictionsForNextCycle(): void
    {
        foreach ($this->graph->getAllColumns() as $column) {
            $column->L23->expectedActivation = 0.0;
        }
    }

    /**
     * Step 8: Check for theta boundaries - both AND completions and OR releases.
     * This is where SOMs get released based on what arrived.
     */
    private function checkThetaBoundaries(array $activatedPOSColumns, WordParseResult $result): void
    {
        // Check all columns that have active SOMs
        foreach ($this->graph->getAllColumns() as $column) {
            if ($column->som === null || ! $column->som->isActive()) {
                continue;
            }

            $som = $column->som;

            // Check timeout first
            //            if ($som->checkTimeout($this->state->currentTime)) {
            //                $som->release();
            //                $result->events[] = "THETA BOUNDARY (timeout): '{$column->id}' released after max duration";
            //
            //                continue;
            //            }

            // Check release condition based on mode
            if ($som->shouldRelease($activatedPOSColumns)) {
                $som->release();

                if ($som->mode === SOMMode::AND) {
                    $result->events[] = "THETA BOUNDARY (AND complete): '{$column->id}/{$column->name}' - right element arrived";
                } else {
                    $result->events[] = "THETA BOUNDARY (OR complete): '{$column->id}/{$column->name}' - unexpected POS arrived";
                }
            }
        }
    }

    /**
     * Step 9: Activate SOM blocking for OR nodes that just received input.
     */
    private function activateORBlocking(array $activatedColumns, WordParseResult $result): void
    {
        foreach ($this->graph->getAllColumns() as $column) {
            // Only OR nodes
            if ($column->type !== PatternNodeType::OR) {
                continue;
            }

            // Only if it has a SOM
            if ($column->som === null) {
                continue;
            }

            // Only if not already active
            if ($column->som->isActive()) {
                continue;
            }

            // Check if this OR received input this cycle
            if ($column->L4->activation > 0.1) {
                $column->som->activate($this->state->currentTime);
                $result->events[] = "SOM activated for OR node '{$column->id}/{$column->name}' - accumulating alternatives";
            }
        }
    }

    /**
     * Step 10: Multi-level feedforward propagation with construction activation
     * Methods used in the interaction
     */

    /**
     * Step 10a: Propagate L4 to L5 for OR construction nodes that received input.
     * Word-level columns already had this done in propagateWithinColumns.
     *
     * IMPORTANT: AND nodes are excluded! They should only activate L5 through
     * completeANDConstruction when both left and right elements are bound.
     */
    private function propagateL4toL5ForConstructions(WordParseResult $result): void
    {
        foreach ($this->graph->getAllColumns() as $column) {
            // Only process OR nodes (not AND!)
            // AND nodes activate through binding completion only
            if ($column->type !== PatternNodeType::OR) {
                continue;
            }

            // If L4 has significant activation, propagate to L5
            if ($column->L4->activation >= $this->state->activationThreshold) {
                $previousL5 = $column->L5->activation;
                $newInput = $column->L4->activation;

                // Add new input to existing (decayed) representation
                $column->L5->activation = min(1.0, $previousL5 + $newInput);

                if ($column->L5->activation > $previousL5 + 0.1) {
                    $result->events[] = "Construction '{$column->id}/{$column->name}' L5: {$previousL5} + {$newInput} = {$column->L5->activation}";
                }
            }
        }
    }

    /**
     * Step 10b: Propagate activations through open feedforward pathways.
     */
    private function propagateFeedforward(WordParseResult $result): void
    {
        // Process columns level by level, from lower to higher
        $maxLevel = $this->getMaxLevel();

        for ($level = 1; $level <= $maxLevel; $level++) {
            $columnsAtLevel = $this->graph->getColumnsAtLevel($level);

            foreach ($columnsAtLevel as $column) {
                // Only propagate if this column has significant activation
                if ($column->L5->activation < $this->state->activationThreshold) {
                    continue;
                }
                if ($column->id == 9521) {
                    echo 1;
                }

                foreach ($column->feedforwardUp as $pathway) {
                    // Check if pathway is blocked by inhibition
                    if ($pathway->isBlocked()) {
                        $result->events[] = "Pathway from '{$column->id}/{$column->name}' to '{$pathway->target->id}/{$pathway->target->name}' BLOCKED";

                        continue;
                    }

                    // Propagate activation (error signal in L23, or direct in L4)
                    $signal = $pathway->sourceLayer === 'L23'
                        ? $column->L23->activation
                        : $column->L5->activation;

                    $targetLayer = $pathway->targetLayer;
                    $currentTarget = $pathway->target->$targetLayer->activation;
                    $newActivation = min(1.0, $currentTarget + $signal * $pathway->weight);
                    $pathway->target->$targetLayer->activation = $newActivation;

                    // Log significant activations of construction nodes
                    if ($pathway->target->type === PatternNodeType::OR || $pathway->target->type === PatternNodeType::AND) {
                        if ($newActivation > $currentTarget + 0.1) {
                            $result->events[] = "Feedforward: '{$column->id}/{$column->name}' activates '{$pathway->target->id}/{$pathway->target->name}' L{$targetLayer} -> {$newActivation}";
                        }
                    }
                }
            }
        }
    }

    /**
     * Step 11 : Record current L5 activation in temporal history AGAIN.
     */

    /**
     * Step 12: Check if any pending AND constructions can now complete.
     * This is where binding happens through temporal overlap.
     */
    private function checkANDCompletions(array $activatedColumns, WordParseResult $result): void
    {
        // Check ALL columns with significant activation (not just word-level)
        // because AND nodes need construction-level elements (e.g., ARG, PRED)
        $allActiveColumns = [];
        foreach ($this->graph->getAllColumns() as $column) {
            if ($column->L5->activation >= $this->state->activationThreshold) {
                $allActiveColumns[] = $column;
            }
        }

        // For each activated column, check if it's the "right" element
        // of any pending construction
        foreach ($allActiveColumns as $rightCandidate) {
            $remainingPending = [];

            foreach ($this->state->pendingConstructions as $pending) {
                $andColumn = $pending->andColumn;

                // Does this activated column match the expected right element?
                if ($andColumn->rightSource !== null &&
                    $andColumn->rightSource->id === $rightCandidate->id) {

                    // RIGHT ELEMENT ARRIVED - Complete the construction!
                    $this->completeANDConstruction($pending, $rightCandidate, $result);

                } else {
                    // Check for timeout
                    $elapsed = $this->state->currentTime - $pending->startedAtTime;
                    if ($elapsed >= ($andColumn->som->maxDuration ?? 4)) {
                        $result->events[] = "Construction '{$andColumn->id}/{$andColumn->name}' timed out after {$elapsed} cycles";
                        // Could handle timeout differently - for now, just drop it
                    } else {
                        $remainingPending[] = $pending;
                    }
                }
            }

            $this->state->pendingConstructions = $remainingPending;
        }
    }

    /**
     * Step 12a
     * Complete an AND construction by binding left and right elements.
     */
    private function completeANDConstruction(
        PendingConstruction $pending,
        FunctionalColumn $rightColumn,
        WordParseResult $result
    ): void {
        $andColumn = $pending->andColumn;
        $leftColumn = $pending->leftFiller;

        // Release the SOM inhibitor (theta boundary!)
        if ($andColumn->som !== null) {
            $andColumn->som->release();
            $result->events[] = "THETA BOUNDARY: SOM released for '{$andColumn->id}/{$andColumn->name}'";
        }

        // Compute binding strength through temporal overlap
        // $leftWindow = $pending->leftActivationWindow;
        // $rightWindow = $rightColumn->L5->temporalHistory;

        // Use LIVE windows for both
        $leftWindow = $pending->leftFiller->L5->temporalHistory;
        $rightWindow = $rightColumn->L5->temporalHistory;

        $bindingStrength = $this->computeTemporalOverlap($leftWindow, $rightWindow);

        $result->events[] = "Binding strength for '{$andColumn->id}/{$andColumn->name}': {$bindingStrength}";

        if ($bindingStrength >= $this->state->bindingThreshold) {
            // Create bindings for both left and right
            $leftBinding = new Binding(
                filler: $leftColumn,
                slot: $andColumn,
                role: 'left',
                strength: $bindingStrength,
                boundAtTime: $this->state->currentTime
            );

            $rightBinding = new Binding(
                filler: $rightColumn,
                slot: $andColumn,
                role: 'right',
                strength: $bindingStrength,
                boundAtTime: $this->state->currentTime
            );

            $this->state->completedBindings[] = $leftBinding;
            $this->state->completedBindings[] = $rightBinding;
            $result->newBindings[] = $leftBinding;
            $result->newBindings[] = $rightBinding;

            $result->events[] = "BINDING: '{$leftColumn->id}/{$leftColumn->name}' bound as LEFT of '{$andColumn->id}'";
            $result->events[] = "BINDING: '{$rightColumn->id}/{$rightColumn->name}' bound as RIGHT of '{$andColumn->id}'";

            // Activate the AND column now that it's complete
            $andColumn->L4->activation = 1.0;
            $andColumn->L5->activation = min(1.0,
                ($leftColumn->L5->activation + $rightColumn->L5->activation) / 2.0
            );

            // Record in AND column's temporal history
            $andColumn->L5->temporalHistory[$this->state->currentTime] = $andColumn->L5->activation;

            $result->events[] = "AND column '{$andColumn->id}/{$andColumn->name}' activated with strength {$andColumn->L5->activation}";
        }
    }

    /**
     * Step 12b: Check if activated columns start any new AND constructions.
     */
    private function checkNewPendingConstructions(array $activatedColumns, WordParseResult $result): void
    {
        // Check ALL columns with significant activation (not just word-level)
        $allActiveColumns = [];
        foreach ($this->graph->getAllColumns() as $column) {
            if ($column->L5->activation >= $this->state->activationThreshold) {
                $allActiveColumns[] = $column;
            }
        }

        foreach ($allActiveColumns as $column) {
            // Look for AND nodes where this column is the LEFT element
            $this->findANDsWaitingForLeft($column, $result);
        }
    }

    /**
     * Step 13 : Record current L5 activation in temporal history AGAIN.
     */

    /**
     * Step 14: Propagate predictions through feedback pathways.
     */
    private function propagateFeedback(WordParseResult $result): void
    {
        // Process columns level by level, from higher to lower
        $maxLevel = $this->getMaxLevel();

        for ($level = $maxLevel; $level >= 1; $level--) {
            $columnsAtLevel = $this->graph->getColumnsAtLevel($level);

            foreach ($columnsAtLevel as $column) {
                // Only generate predictions if this column has significant activation
                if ($column->L5->activation < $this->state->activationThreshold) {
                    continue;
                }

                foreach ($column->feedbackDown as $pathway) {
                    // Predictions flow from L5 to target's L23 (expected activation)
                    $predictionStrength = $column->L5->activation * $pathway->weight;

                    // Set expected activation on the target
                    $target = $pathway->target;
                    $currentExpected = $target->L23->expectedActivation;
                    $target->L23->expectedActivation = min(1.0,
                        max($currentExpected, $predictionStrength)
                    );

                    if ($predictionStrength > 0.1) {
                        $result->events[] = "Prediction: '{$column->id}/{$column->name}' predicts '{$target->id}' with strength {$predictionStrength}";
                    }
                }
            }
        }
    }

    /**
     * Step 15: Update the list of currently active columns.
     */
    private function updateActiveColumns(): void
    {
        $this->state->activeColumns = [];

        foreach ($this->graph->getAllColumns() as $column) {
            if ($column->L5->activation >= $this->state->activationThreshold) {
                $this->state->activeColumns[] = $column;
            }
        }
    }

    /**
     * Compute temporal overlap between two activation windows.
     * This models the conjunction neuron firing.
     */
    private function computeTemporalOverlap(array $window1, array $window2): float
    {
        $overlap = 0.0;
        $normalization = 0.0;
        $integrationWindow = 2;  // +/- timesteps
        $decayFactor = 0.5;

        foreach ($window1 as $t1 => $act1) {
            for ($t2 = $t1 - $integrationWindow; $t2 <= $t1 + $integrationWindow; $t2++) {
                if (! isset($window2[$t2])) {
                    continue;
                }

                $act2 = $window2[$t2];
                $distance = abs($t1 - $t2);
                $temporalWeight = exp(-$distance * $decayFactor);
                $contribution = min($act1, $act2) * $temporalWeight;
                $overlap += $contribution;
            }
            $normalization += $act1;
        }

        foreach ($window2 as $act2) {
            $normalization += $act2;
        }

        return $normalization > 0 ? (2.0 * $overlap) / $normalization : 0.0;
    }

    /**
     * Find AND nodes where the given column can serve as the left element.
     */
    private function findANDsWaitingForLeft(FunctionalColumn $leftCandidate, WordParseResult $result): void
    {
        foreach ($this->graph->getAllColumns() as $andColumn) {
            // Only consider AND nodes
            if ($andColumn->type !== PatternNodeType::AND) {
                continue;
            }

            // Check if leftCandidate matches the left source
            if ($andColumn->leftSource !== null &&
                $andColumn->leftSource->id === $leftCandidate->id) {

                // Check we don't already have this pending
                $alreadyPending = false;
                foreach ($this->state->pendingConstructions as $pending) {
                    if ($pending->andColumn->id === $andColumn->id) {
                        $alreadyPending = true;
                        break;
                    }
                }

                if (! $alreadyPending) {
                    // Start a new pending construction
                    $pending = new PendingConstruction(
                        andColumn: $andColumn,
                        leftFiller: $leftCandidate,
                        startedAtTime: $this->state->currentTime,
                        leftActivationWindow: $leftCandidate->L5->temporalHistory
                    );

                    $this->state->pendingConstructions[] = $pending;
                    $result->newPendingConstructions[] = $pending;

                    // Activate the SOM to block premature completion
                    if ($andColumn->som !== null) {
                        $andColumn->som->activate($this->state->currentTime);
                    }

                    $result->events[] = "Started pending construction '{$andColumn->id}/{$andColumn->name}' with left='{$leftCandidate->id}'";
                }
            }
        }
    }

    /**
     * Propagate predictions during priming - allows cascade through expected columns.
     * Unlike normal propagation, this treats strongly expected columns as having
     * weak baseline activity for prediction generation purposes only.
     */
    private function propagateFeedbackForPriming(WordParseResult $result): void
    {
        $maxLevel = $this->getMaxLevel();

        for ($level = $maxLevel; $level >= 1; $level--) {
            $columnsAtLevel = $this->graph->getColumnsAtLevel($level);

            foreach ($columnsAtLevel as $column) {
                // During priming, allow prediction from:
                // 1. Columns with actual L5 activation (CLAUSE)
                // 2. Columns with strong expectations (predicted children)
                $canPredict = false;
                $predictionSource = 0.0;

                if ($column->L5->activation >= $this->state->activationThreshold) {
                    $canPredict = true;
                    $predictionSource = $column->L5->activation;
                } elseif ($column->L23->expectedActivation >= 0.5) {
                    // Treat strong expectation as weak baseline for cascade only
                    $canPredict = true;
                    $predictionSource = $column->L23->expectedActivation * 0.5;
                }

                if (! $canPredict) {
                    continue;
                }

                foreach ($column->feedbackDown as $pathway) {
                    $predictionStrength = $predictionSource * $pathway->weight;

                    $target = $pathway->target;
                    $currentExpected = $target->L23->expectedActivation;
                    $target->L23->expectedActivation = min(1.0,
                        max($currentExpected, $predictionStrength)
                    );
                }
            }
        }
    }

    /**
     * Helper: Get the maximum hierarchical level in the graph.
     */
    private function getMaxLevel(): int
    {
        $max = 0;
        foreach ($this->graph->getAllColumns() as $column) {
            $max = max($max, $column->hierarchicalLevel);
        }

        return $max;
    }

    /**
     * Get the current parsing state (for inspection).
     */
    public function getState(): ParsingState
    {
        return $this->state;
    }

    public function getRuntimeGraph(): RuntimeGraph
    {
        return $this->graph;
    }

    /**
     * Build empty result when parsing fails
     *
     * @param  string  $sentence  Input sentence
     * @param  string  $reason  Failure reason
     * @return array Empty result
     */
    private function emptyResult(string $sentence, string $reason): array
    {
        return [
            'sentence' => $sentence,
            'constructions' => [],
            'metadata' => [
                'success' => false,
                'reason' => $reason,
                'words' => 0,
                'timesteps' => 0,
                'converged' => false,
            ],
        ];
    }

    /**
     * Build final parse result
     *
     * @param  string  $sentence  Input sentence
     * @param  array  $wordData  Word data
     * @param  array  $constructions  Extracted constructions
     * @param  array  $loopResult  Processing loop result
     * @return array Complete parse result
     */
    private function buildResult(
        string $sentence,
        array $wordData,
        array $constructions,
        array $loopResult
    ): array {
        return [
            'sentence' => $sentence,
            'constructions' => $constructions,
            'words' => $wordData,
            'activation_stats' => $loopResult['activation_stats'] ?? [],
            'metadata' => [
                'success' => true,
                'words' => count($wordData),
                'constructions_found' => count($constructions),
                //                'timesteps' => $loopResult['timesteps'],
                //                'converged' => $loopResult['converged'],
                //                'converged_at' => $loopResult['converged_at'],
                //                'simulated_time' => $loopResult['simulated_time'],
                //                'total_pruned' => $loopResult['total_pruned'],
                'stability' => [
                    //                    'max_change' => $loopResult['stability_metrics']['max_change'],
                    //                    'avg_change' => $loopResult['stability_metrics']['avg_change'],
                    //                    'oscillating_nodes' => count($loopResult['stability_metrics']['oscillating_nodes']),
                ],
                'config' => [
                    'dt' => $this->config['dt'],
                    'max_timesteps' => $this->config['max_timesteps'],
                    'pruning_strategy' => $this->config['pruning_strategy'],
                ],
            ],
        ];
    }

    /**
     * Get parser configuration
     *
     * @return array Current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update parser configuration
     *
     * @param  array  $config  Configuration updates
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Get default configuration from config file
     *
     * Loads configuration from config/cln.php and flattens the nested
     * structure into the format expected by the parser.
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        $pcConfig = config('cln.pc_parser', []);

        return [
            // Dynamics
            'dt' => $pcConfig['dynamics']['dt'] ?? 0.01,
            'max_timesteps' => $pcConfig['dynamics']['max_timesteps'] ?? 500,
            'min_timesteps' => $pcConfig['dynamics']['min_timesteps'] ?? 10,

            // Convergence
            'convergence_threshold' => $pcConfig['convergence']['threshold'] ?? 0.001,
            'min_stable_steps' => $pcConfig['convergence']['min_stable_steps'] ?? 5,
            'oscillation_window' => $pcConfig['convergence']['oscillation_window'] ?? 10,
            'convergence_check_interval' => $pcConfig['convergence']['check_interval'] ?? 5,

            // Pruning
            'enable_pruning' => $pcConfig['pruning']['enabled'] ?? true,
            'pruning_interval' => $pcConfig['pruning']['interval'] ?? 10,
            'pruning_strategy' => $pcConfig['pruning']['strategy'] ?? 'smart',
            'pruning_absolute_threshold' => $pcConfig['pruning']['absolute_threshold'] ?? 0.05,
            'pruning_competitive_gap' => $pcConfig['pruning']['competitive_gap'] ?? 0.3,
            'preserve_completed' => $pcConfig['pruning']['preserve_completed'] ?? true,

            // Input scheduling
            'input_mode' => $pcConfig['input']['mode'] ?? 'all_at_once',

            // Output
            'extract_winners_only' => $pcConfig['output']['extract_winners_only'] ?? true,
            'min_activation_threshold' => $pcConfig['output']['min_activation_threshold'] ?? 0.5,

            // RNT Pattern Graph
            'rnt_enabled' => $pcConfig['rnt']['enabled'] ?? false,
            'rnt_cache_queries' => $pcConfig['rnt']['cache_queries'] ?? true,
            'rnt_warmup_cache' => $pcConfig['rnt']['warmup_cache'] ?? true,

            // Incremental Parsing
            'incremental_enabled' => $pcConfig['incremental']['enabled'] ?? false,
            'timesteps_per_word' => $pcConfig['incremental']['timesteps_per_word'] ?? 20,
            'prune_after_each_word' => $pcConfig['incremental']['prune_after_each_word'] ?? false,
            'extract_after_each_word' => $pcConfig['incremental']['extract_after_each_word'] ?? false,
        ];
    }

    public function generateGraph(array $wordData, int $i): void
    {
        // Create exporter
        $exporter = new ParserGraphExporter;
        // Generate DOT content
        $dot = $exporter->exportToDot($this->graph, $wordData[$i]['word'], [], []);
        $outputDir = $this->graph->output_dir;

        // Save DOT file
        //        $timestamp = date('Y-m-d_H-i-s');
        $baseName = "parser_graph_{$i}";
        $dotPath = "{$outputDir}/{$baseName}.dot";

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $exporter->saveDotToFile($dot, $dotPath);
        $pngPath = "{$outputDir}/{$baseName}.png";
        $renderResult = $exporter->renderToPng($dotPath, $pngPath);

    }

    /**
     * Generate parse graph showing only activated constructions (bottom-up).
     */
    private function generateParseGraph(array $wordData): void
    {
        $outputDir = $this->graph->output_dir;
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Build column name map
        $columnNames = [];
        foreach ($this->graph->getAllColumns() as $column) {
            $columnNames[$column->id] = $column->name;
        }

        // Export parse graph
        $exporter = new ParseGraphExporter;
        $dotPath = "{$outputDir}/parse_graph.dot";

        $exporter->exportParseToDot(
            $this->graph,
            $wordData,
            $this->activationHistory,
            $this->state->completedBindings,
            $columnNames,
            $dotPath
        );

        // Render to PNG
        $pngPath = "{$outputDir}/parse_graph.png";
        $success = $exporter->renderToPng($dotPath, $pngPath);

        if ($success) {
            echo "\n✓ Parse graph saved: {$pngPath}\n";
        } else {
            echo "\n✗ Failed to render parse graph PNG\n";
        }
    }
}

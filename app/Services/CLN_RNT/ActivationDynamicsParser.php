<?php

namespace App\Services\CLN_RNT;

use App\Models\CLN_RNT\ConnectionEdge;
use App\Models\CLN_RNT\Node;
use App\Models\CLN_RNT\RuntimeGraph;

/**
 * Activation Dynamics for Parsing (RNT Pattern Graph)
 *
 * Implements simple forward-pass activation propagation through the RNT pattern graph:
 * - DATA (Word/POS) nodes activate and propagate forward
 * - OR nodes: Activate just once when any input arrives
 * - AND nodes: Only propagate when BOTH left and right operands are active
 *
 * Uses lazy database queries with caching for pattern graph access.
 */
class ActivationDynamicsParser
{
    /**
     * Activation threshold for considering a node "active"
     */
    private const ACTIVATION_THRESHOLD = 0.5;

    /**
     * Initial activation level for input word/POS nodes
     */
    private const INITIAL_ACTIVATION = 0.95;

    /**
     * Activation propagation decay factor
     */
    private const PROPAGATION_FACTOR = 0.9;

    /**
     * Maximum number of propagation iterations
     */
    private const MAX_ITERATIONS = 15;

    /**
     * Track which OR nodes have been activated (activate once rule)
     *
     * @var array<int, bool> or_node_id => true
     */
    //private array $activatedOrNodes = [];

    /**
     * Track which SEQUENCER instances (L2 nodes) have been head-activated
     * Key: L2 node ID in runtime graph
     * Value: true
     *
     * @var array<string, bool>
     */
    //private array $headActivatedSequencers = [];

    /**
     * Cache for pattern graph edges (lazy loaded)
     * Key: from_node_id
     * Value: array of edge objects
     *
     * @var array<int, array>
     */
    private array $edgeCache = [];

    /**
     * Cache for pattern graph nodes (lazy loaded)
     * Key: node_id
     * Value: node data array
     *
     * @var array<int, array>
     */
    private array $nodeCache = [];

    /**
     * Cache for DATA node lookups
     * Key: "literal:{word}" or "slot:{pos}"
     * Value: array of matching DATA node IDs
     *
     * @var array<string, array>
     */
    private array $dataNodeCache = [];

    private RuntimeGraph $graph;

    private array $activeNodes = [];

    // Time management
    private int $currentTime = 0;
    private int $windowSize;

    // Activation tracking: nodeId => [time => activation_level]
    private array $activations = [];
    private array $activationsHistory = [];

    // Active predictions: waiting slots for forward binding
    private array $predictions = [];

    // Binding registry: explicit working memory of established bindings
    private array $bindings = [];
    private array $wordByTime = [];

    // Configuration
    private float $activationDecay = 0.1;      // Per-timestep decay
    private float $bindingThreshold = 0.3;     // Minimum for binding

    public function __construct(RuntimeGraph $graph)
    {
        $this->graph = $graph;
        $this->windowSize = 1;
    }

    /**
     * Reset activation state for a new parse
     */
    public function reset(): void
    {
//        $this->activatedOrNodes = [];
//        $this->headActivatedSequencers = [];
    }

    /**
     * Propagate activation through the network
     *
     * @param RuntimeGraph $graph The pattern graph
     * @param array $activeWordNodes Initial active word nodes
     * @param array $seqColumnsL1 L1 SeqColumns
     * @param array $seqColumnsL2 L2 SeqColumns
     * @param bool $applyLearning Whether to apply Hebbian learning
     * @param int $maxIterations Maximum iterations for convergence (default: MAX_ITERATIONS)
     */
    public function propagateActivation(
        int $maxIterations = self::MAX_ITERATIONS
    ): array
    {
        $stats = [
            'iterations' => 0,
            'nodes_activated' => 0,
            'data_nodes_activated' => 0,
            'or_nodes_activated' => 0,
            'and_nodes_activated' => 0,
            'som_nodes_activated' => 0,
            'vip_nodes_activated' => 0,
        ];
        $this->currentTime = 0;
        $literalNodes = $this->graph->getLiteralNodes();
        foreach ($literalNodes as $literal) {
            $this->wordByTime[$this->currentTime] = $literal->getName();
            $this->activate($literal, 1.0);
            $literal->activated = true;
            $activeNodes = [$literal];
            for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
                $this->activeNodes = [];
                foreach ($activeNodes as $node) {
                    $this->propagateActivationFromNode($node, $stats);
                }
                if (empty($this->activeNodes)) {
                    break;
                }
                $activeNodes = $this->activeNodes;
            }
            $this->generateGraph($this->currentTime);
            $this->advanceTime();
        }
//        $this->printActivations();
        $this->printBindings();
        return $stats;
    }

    private function propagateActivationFromNode(Node $source, array &$stats): void
    {

        $edges = $this->graph->getEdges($source->id);
        $activeNodes = [];

        foreach ($edges as $edge) {
            $active = false;
            $target = $this->graph->getNode($edge->target);
            $inputActivation = $source->activation * $edge->weight;
            if ($target->type == 'DATA') {
                $active = $this->propagateToDataNode($target, $inputActivation, $stats);
            }
            if ($target->type == 'OR') {
                $active = $this->propagateToOrNode($target, $inputActivation, $stats);
            }
            if ($target->type == 'AND') {
                $active = $this->propagateToAndNode($source, $target, $edge, $inputActivation, $stats);
            }
            $edge->active = true;
            if ($active) {
                $activeNodes[$target->id] = $target;
            }
        }
        foreach ($edges as $edge) {
            $active = false;
            $target = $this->graph->getNode($edge->target);
            $inputActivation = $source->activation * $edge->weight;
            if ($target->type == 'SOM') {
                $active = $this->propagateToSOMNode($target, $edge, $inputActivation, $stats);
            }
            if ($target->type == 'VIP') {
                $active = $this->propagateToVIPNode($target, $inputActivation, $stats);
            }
            $edge->active = true;
            if ($active) {
                $activeNodes[$target->id] = $target;
            }
        }
        // após processar todos os edges, verifica se algum SOM está no activeNodes
        // se tiver, ele deve ser propagado
        $time = $this->currentTime;
        $next = [];
        foreach ($activeNodes as $node) {
            if ($node->type == 'SOM') {
                $edges = $this->graph->getEdges($node->id);
                foreach ($edges as $edge) {
                    $target = $this->graph->getNode($edge->target);
                    if ($node->isInhibited()) {
                        $target->disinhibit();
                        $next[$target->id] = $target;
                        $node->activated = false;
                        $target->activated = true;
                        $previousTime = $target->time;
                        $this->activate($target, $target->activation);
                        $target->time= $previousTime;
                        $node->disinhibit();
//                        $active = $this->propagateToOrNode($target, $inputActivation, $stats);
                    } else {
                        $target->inhibit();
                    }
                }
            }
        }
        foreach ($activeNodes as $id => $node) {
            if (!$node->isInhibited()) {
                $node->activated = true;
                $next[$id] = $node;
            }
        }
        $this->activeNodes = $next;
    }

    private function propagateToDataNode(Node $node, float $inputActivation, array &$stats): bool
    {
        $activation = max($node->activation, $inputActivation * 0.9);
        $this->activate($node, $activation);
        $stats['data_nodes_activated']++;
        $stats['nodes_activated']++;
        return true;
    }

    private function propagateToOrNode(Node $node, float $inputActivation, array &$stats): bool
    {
        $this->activate($node, max($node->activation, $inputActivation * 0.9));
        $stats['or_nodes_activated']++;
        $stats['nodes_activated']++;
        return true;
    }

    private function propagateToAndNode(
        Node           $source,
        Node           $target,
        ConnectionEdge $edge,
        float          $inputActivation,
        array          &$stats
    ): bool
    {

        $changed = $bound = false;
        if($source->id == 9402) {
            echo 'a';
        }
        $this->activate($target, max($target->activation, $inputActivation * 0.9));
        if ($edge->type == 'left') {
            $binding = $target->getLastBinding();
            if (!is_null($binding)) {
                if ($source->time < $binding->rightTime) {
                    //$this->boostConjunctionActivation($binding);
                    $binding->updateLeft($source, $this->currentTime);
                    $bound = true;
                }
            } else {
                $this->createBinding($source, $target, null);
            }
//            $predicted = null;
//            $incomingEdges = $this->graph->getIncomingEdges($target->id);
//            foreach ($incomingEdges as $incomingEdge) {
//                if ($incomingEdge->type == 'right') {
//                    $predicted = $this->graph->getNode($incomingEdge->target);
//                }
//            }
//            if ($predicted) {
//                $this->createPrediction($source, $target, $predicted, strength: 0.9, persistence: 2);
//            }
        } elseif ($edge->type == 'right') {
            $binding = $target->getLastBinding();
            if (!is_null($binding)) {
                if ($binding->leftTime < $source->time) {
                    //$this->boostConjunctionActivation($binding);
                    $binding->updateRight($source, $this->currentTime);
                    $bound = true;
                }
            } else {
                $this->createBinding(null, $target, $source);
            }

//            $binding = $this->attemptBinding($source, $target);
//            if ($binding) {
//                $bound = true;
//            } else {
//                $previousBinding = $this->bindings[$target->id][$this->currentTime - 2] ?? null;
//                if ($previousBinding) {
//                    $currentTime = $this->currentTime;
//                    $binding = new Binding(
//                        left: $previousBinding->left,
//                        head: $target,
//                        right: $source,
//                        strength: $previousBinding->strength,
//                        leftTime: $previousBinding->leftTime,
//                        headTime: $previousBinding->headTime,
//                        rightTime: $currentTime,
//                        boundAt: $currentTime
//                    );
//                    // Register in working memory
//                    $this->bindings[$target->id][$currentTime - 1] = $binding;
//                    // Boost conjunction neurons (represented by enhanced binding activation)
//                    $this->boostConjunctionActivation($binding);
//                    $binding->updateRight($source, $currentTime);
//                }
//            }
        }
        if ($bound || ($target->thresholdInput == 1)) {
            $changed = true;
            $stats['nodes_activated']++;
            $stats['and_nodes_activated']++;
        }
        return $changed;
    }

    private function propagateToSOMNode(Node $node, ConnectionEdge $edge, float $inputActivation, array &$stats): bool
    {
        if ($edge->type == 'vip') {
            $this->activate($node, 0.0);
            $node->inhibit();
        } else {
            $this->activate($node, max($node->activation, $inputActivation * 0.9));
            $stats['som_nodes_activated']++;
            $stats['nodes_activated']++;
        }
        return true;
    }

    private function propagateToVIPNode(Node $node, float $inputActivation, array &$stats): bool
    {
        $this->activate($node, max($node->activation, $inputActivation * 0.9));
        $stats['vip_nodes_activated']++;
        $stats['nodes_activated']++;
        return true;
    }

    /**
     * Activate a node at current timestep (strong activation)
     * This is what happens when a word is processed or construction fires
     */
    public function activate(Node $node, float $level = 1.0): void
    {
        $node->activate($level);
        $node->time = $this->currentTime;
        $this->activations[$node->id][$this->currentTime] = $level;
        $this->activationsHistory[$this->currentTime][$node->id] = $level;

        // Check if this activation satisfies any waiting predictions
        //$this->attemptPredictionFulfillment($node, $level);
    }

    /**
     * Pre-activate a node for future timestep (weak, lookahead)
     */
    public function preActivate(Node $node, float $strength, int $offset = 1): void
    {
        $futureTime = $this->currentTime + $offset;
        $existing = $this->activations[$node->id][$futureTime] ?? 0.0;
        $this->activations[$node->id][$futureTime] = max($existing, $strength);
    }

    /**
     * Get activation level at specific time
     */
    public function getActivation(Node $node, int $time): float
    {
        return $this->activations[$node->id][$time] ?? 0.0;
    }

    /**
     * Get current activation (convenience method)
     */
    public function getCurrentActivation(Node $node): float
    {
        return $this->getActivation($node, $this->currentTime);
    }

    /**
     * Get activation window for a node (recent history)
     * Returns: [time => activation_level]
     */
    public function getActivationWindow(Node $node): array
    {
        $start = max(0, $this->currentTime - $this->windowSize);
        $end = $this->currentTime;

        $window = [];
        for ($t = $start; $t <= $end; $t++) {
            $level = $this->getActivation($node, $t);
            if ($level > 0.0) {
                // Apply decay for older activations
                $age = $this->currentTime - $t;
                $decayed = $level * pow(1 - $this->activationDecay, $age);
                $window[$t] = max(0.0, $decayed);
            }
        }

        return $window;
    }

    /**
     * Check if node is active within window
     */
    public function isActiveInWindow(Node $node, float $threshold = 0.1): bool
    {
        $window = $this->getActivationWindow($node);
        foreach ($window as $activation) {
            if ($activation >= $threshold) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get peak activation info within window
     */
    public function getPeakActivation(Node $node): array
    {
        $window = $this->getActivationWindow($node);

        $maxTime = $this->currentTime;
        $maxLevel = 0.0;

        foreach ($window as $t => $level) {
            if ($level > $maxLevel) {
                $maxLevel = $level;
                $maxTime = $t;
            }
        }

        return [
            'time' => $maxTime,
            'level' => $maxLevel,
            'recency' => $this->currentTime - $maxTime
        ];
    }

    // ===== PREDICTION OPERATIONS =====

    /**
     * Create a prediction - a "waiting slot" for forward binding
     * The prediction sustains activation across future timesteps
     */
    public function createPrediction(
        Node  $source,
        Node  $predictor,      // Who's making the prediction (e.g., VERB)
        Node  $predicted,      // What's being predicted (e.g., SUBJECT-role)
        float $strength = 0.8,
        int   $persistence = 5
    ): Prediction
    {
        $prediction = new Prediction(
            source: $source,
            predictor: $predictor,
            predicted: $predicted,
            strength: $strength,
            createdAt: $this->currentTime,
            expiresAt: $this->currentTime + $persistence
        );

        $this->predictions[$predicted->id][] = $prediction;

        // Sustain activation in predicted node over future timesteps
        for ($t = $this->currentTime; $t <= $prediction->expiresAt; $t++) {
            $decayFactor = 1.0 - (($t - $this->currentTime) * 0.1);
            $this->activations[$predicted->id][$t] =
                max($this->activations[$predicted->id][$t] ?? 0.0,
                    $strength * $decayFactor);
        }

        return $prediction;
    }

    /**
     * Check if node has active predictions waiting to be filled
     */
    public function getActivePredictions(Node $node): array
    {
        if (!isset($this->predictions[$node->id])) {
            return [];
        }

        return array_filter(
            $this->predictions[$node->id],
            fn($p) => !$p->fulfilled && $p->expiresAt >= $this->currentTime
        );
    }

    /**
     * When an activation occurs, check if it satisfies waiting predictions
     */
    private function attemptPredictionFulfillment(Node $node, float $level): void
    {
        $predictions = $this->getActivePredictions($node);

        foreach ($predictions as $prediction) {
            // This activation could fulfill the prediction
            // But we need the right type of filler...
            // (This gets called from attemptBinding)
        }
    }

    // ===== BINDING OPERATIONS =====

    /**
     * Create a binding when the left node activate a AND node
     * This is where conjunction neurons fire based on temporal overlap
     */
    public function createBinding(?Node $left, Node $head, ?Node $right): ?Binding
    {
        // Get temporal windows for both
//        $leftWindow = $this->getActivationWindow($left);
//        $headWindow = $this->getActivationWindow($head);

        // Compute temporal overlap (conjunction neuron activation)
//        $overlap = $this->computeTemporalOverlap($leftWindow, $headWindow);

        // Binding strength = overlap + prediction strength
//        $bindingStrength = $overlap;
//        if (!empty($predictions)) {
//            $predictionBoost = max(array_map(fn($p) => $p->strength, $predictions));
//            $bindingStrength += $predictionBoost * 0.3; // Prediction boosts binding
//        }

        // Check threshold
//        if ($bindingStrength < $this->bindingThreshold) {
//            return null;
//        }
        // Create binding
        $currentTime = $this->currentTime;
        $binding = new Binding(
            left: $left,
            head: $head,
            right: $right,
            strength: 1.0,
            leftTime: $left ? $left->time : -1,
            headTime: $currentTime,
            rightTime: $right ? $right->time : -1,
            boundAt: $this->currentTime
        );

        // Register in working memory
        $this->bindings[$head->id][] = $binding;

        // Mark predictions as fulfilled
//        foreach ($predictions as $prediction) {
//            $prediction->fulfill($binding);
//        }

        // Boost conjunction neurons (represented by enhanced binding activation)
        //$this->boostConjunctionActivation($binding);

        $head->addBinding($binding);

        return $binding;
    }

    /**
     * Attempt to bind a right node to an AND node
     * This is where conjunction neurons fire based on temporal overlap
     */
    public function attemptBinding(Node $right, Node $head): bool
    {
        // Check for active predictions on the role
        $predictions = $this->getActivePredictions($head);

        if (empty($predictions)) {
            return false;
        }


        // Get temporal windows for both
        $rightWindow = $this->getActivationWindow($right);
        $headWindow = $this->getActivationWindow($head);

        // Compute temporal overlap (conjunction neuron activation)
        $overlap = $this->computeTemporalOverlap($rightWindow, $headWindow);

        // Binding strength = overlap + prediction strength
        $bindingStrength = $overlap;
        if (!empty($predictions)) {
            $predictionBoost = max(array_map(fn($p) => $p->strength, $predictions));
            $bindingStrength += $predictionBoost * 0.3; // Prediction boosts binding
        }

        // Check threshold
        if ($bindingStrength < $this->bindingThreshold) {
            return false;
        }

//
//
//        // Create binding
//        $binding = new Binding(
//            left: $left,
//            head: $head,
//            right: $right,
//            strength: $bindingStrength,
//            leftWindow: $leftWindow,
//            headWindow: $headWindow,
//            rightWindow: $rightWindow,
//            boundAt: $this->currentTime
//        );
//
//        // Register in working memory
//        $this->bindings[$head->id][] = $binding;

        // Mark predictions as fulfilled
//        foreach ($predictions as $prediction) {
//            $prediction->fulfill($binding);
//        }

        // Boost conjunction neurons (represented by enhanced binding activation)
//        $this->boostConjunctionActivation($binding);

//        // get the bindings for this head
        $headBindings = $this->bindings[$head->id] ?? [];
        foreach ($headBindings as $binding) {
            if (!$binding->activated) {
                // update the bind with the right node
                $binding->updateRight($right, $this->currentTime);
                // Mark predictions as fulfilled
                foreach ($predictions as $prediction) {
                    $prediction->fulfill($binding);
                }
            }

//            // Boost conjunction neurons (represented by enhanced binding activation)
            //$this->boostConjunctionActivation($binding);

        }

        return true;
    }

    /**
     * Compute temporal overlap between two activation windows
     * This models conjunction neuron firing
     */
//    private function computeTemporalOverlap(array $window1, array $window2): float
//    {
//        $overlap = 0.0;
//        $allTimes = array_unique(array_merge(array_keys($window1), array_keys($window2)));
//
//        foreach ($allTimes as $t) {
//            $act1 = $window1[$t] ?? 0.0;
//            $act2 = $window2[$t] ?? 0.0;
//            // Overlap = minimum (AND-like operation)
//            $overlap += min($act1, $act2);
//        }
//
//        // Normalize by window size
//        return $overlap / max(1, count($allTimes));
//    }
    private function computeTemporalOverlap(array $window1, array $window2): float
    {
        return $this->computeTemporalOverlapWeighted(
            $window1,
            $window2,
            integrationWindow: 2,
            distanceDecay: 0.4
        );
    }

    /**
     * Compute temporal overlap with distance weighting
     * Models: Conjunction neurons with temporal integration windows
     *
     * Biologically: Dendritic integration has temporal width (~20-50ms)
     * Inputs arriving within small window can summate
     */
    private function computeTemporalOverlapWeighted(
        array $window1,
        array $window2,
        int   $integrationWindow = 2,  // Timesteps within which inputs can summate
        float $distanceDecay = 0.5
    ): float
    {
        $overlap = 0.0;
        $normalization = 0.0;

        // For each timestep in first window
        foreach ($window1 as $t1 => $act1) {
            // Look for activations in second window within integration window
            for ($t2 = $t1 - $integrationWindow; $t2 <= $t1 + $integrationWindow; $t2++) {
                if (!isset($window2[$t2])) continue;

                $act2 = $window2[$t2];
                $distance = abs($t1 - $t2);

                // Weight by temporal proximity (Gaussian-like)
                // distance=0: weight=1.0
                // distance=1: weight=0.6
                // distance=2: weight=0.3
                $temporalWeight = exp(-$distance * $distanceDecay);

                // Contribution = both activations * temporal weight
                $contribution = min($act1, $act2) * $temporalWeight;
                $overlap += $contribution;
            }

            // Track normalization factor
            $normalization += $act1;
        }

        // Also normalize by second window
        foreach ($window2 as $act2) {
            $normalization += $act2;
        }

        return $normalization > 0 ? (2 * $overlap) / $normalization : 0.0;
    }

    /**
     * Boost conjunction neuron activation after successful binding
     * This creates the persistent trace in working memory
     */
    private function boostConjunctionActivation(Binding $binding): void
    {
        // Create virtual "conjunction node" for this binding
        // (In real implementation, these might be actual intermediate nodes)
        $conjunctionId = "CONJ_{$binding->left->id}_{$binding->head->id}";

        // Sustain activation representing the binding
        for ($t = $this->currentTime; $t < $this->currentTime + $this->windowSize; $t++) {
            $this->activations[$conjunctionId][$t] = $binding->strength * 0.8;
        }
    }

    /**
     * Retrieve bindings for a specific role
     */
    public function getBindingsForRole(Node $role): array
    {
        return $this->bindings[$role->id] ?? [];
    }

    /**
     * Find what bound to a role (most recent/strongest)
     */
    public function getBoundFiller(Node $role): ?Binding
    {
        $bindings = $this->getBindingsForRole($role);

        if (empty($bindings)) {
            return null;
        }

        // Return most recent or strongest
        usort($bindings, fn($a, $b) => $b->boundAt <=> $a->boundAt);
        return $bindings[0];
    }

    // ===== TIME MANAGEMENT =====

    /**
     * Advance to next timestep
     * Cleanup old activations outside window
     */
    public function advanceTime(): void
    {
        $this->currentTime++;
        $this->cleanupOldActivations();
        $this->expirePredictions();
    }

    private function cleanupOldActivations(): void
    {
        $cutoff = $this->currentTime - $this->windowSize - 5; // Keep a bit extra

        foreach ($this->activations as $nodeId => &$timeline) {
            foreach ($timeline as $t => $level) {
                if ($t < $cutoff) {
                    unset($timeline[$t]);
                }
            }
        }
    }

    private function expirePredictions(): void
    {
        foreach ($this->predictions as $nodeId => &$predList) {
            $predList = array_filter(
                $predList,
                fn($p) => $p->expiresAt >= $this->currentTime || $p->fulfilled
            );
        }
    }

    // ===== QUERY INTERFACE =====

    /**
     * Get all activations within current window
     * Returns array of [nodeId => peak_info]
     */
    public function getActiveNodes(float $threshold = 0.1): array
    {
        $active = [];

        foreach ($this->activations as $nodeId => $timeline) {
            $window = $this->getActivationWindow(new Node($nodeId, '', ''));
            $peak = max($window);

            if ($peak >= $threshold) {
                $active[$nodeId] = [
                    'peak' => $peak,
                    'times' => array_keys(array_filter($window, fn($v) => $v >= $threshold))
                ];
            }
        }

        return $active;
    }

    /**
     * Debug: Print current state
     */
    public function printState(): void
    {
        echo "=== Time: {$this->currentTime} ===\n";
        echo "Active nodes:\n";
        foreach ($this->getActiveNodes() as $nodeId => $info) {
            echo "  $nodeId: peak={$info['peak']}, times=" . implode(',', $info['times']) . "\n";
        }
        echo "Active predictions: " . count(array_filter($this->predictions, fn($p) => !empty($p))) . "\n";
        echo "Active bindings: " . count($this->bindings) . "\n";
    }

    public function printActivations(): void
    {
        echo "=== Activations ===\n";
        foreach ($this->activationsHistory as $idNode => $times) {
            $node = $this->graph->getNode($idNode);
            if ($node) {
                echo " {$node->metadata['name']} : \n";
                foreach ($times as $time => $level) {
                    echo "  {$time} : {$level}\n";
                }
            }
        }
    }

    public function printBindings(): void
    {
        echo "=== Bindings ===\n";
        foreach ($this->bindings as $idHead => $bindings) {
            $node = $this->graph->getNode($idHead);
            echo "   node: {$node->metadata['name']} \n";
            foreach ($bindings as $binding) {
//                foreach ($bindings as $binding) {
                if ($binding->activated) {
                    $left = $this->graph->getNode($binding->left->id);
                    $head = $this->graph->getNode($binding->head->id);
                    $right = $this->graph->getNode($binding->right->id);
                    echo "  at {$binding->boundAt} -  " .
                        "left: {$left->metadata['name']} [{$binding->leftTime}] ({$this->wordByTime[$binding->leftTime]}) - " .
                        "head :  {$head->metadata['name']} [{$binding->headTime}] ({$this->wordByTime[$binding->headTime]}) - " .
                        "right :  {$right->metadata['name']} [{$binding->rightTime} ({$this->wordByTime[$binding->rightTime]})] \n";
                }
//                }
            }
        }
    }

    public function generateGraph(int $time): void
    {
        // Create exporter
        $exporter = new ParserGraphExporter;
        // Generate DOT content
        $dot = $exporter->exportToDot($this->graph, $this->wordByTime[$time], [], array_keys($this->activationsHistory[$time]));
        $outputDir = $this->graph->output_dir;

        // Save DOT file
//        $timestamp = date('Y-m-d_H-i-s');
        $baseName = "parser_graph_{$time}";
        $dotPath = "{$outputDir}/{$baseName}.dot";

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $exporter->saveDotToFile($dot, $dotPath);
        $pngPath = "{$outputDir}/{$baseName}.png";
        $renderResult = $exporter->renderToPng($dotPath, $pngPath);

    }


}

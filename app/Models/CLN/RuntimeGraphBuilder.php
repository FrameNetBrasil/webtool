<?php

namespace App\Models\CLN;

class RuntimeGraphBuilder
{
    private PatternGraph $patternGraph;

    private RuntimeGraph $runtimeGraph;

    // Cache for computed hierarchical levels
    private array $levelCache = [];

    /**
     * Main entry point: build RuntimeGraph from PatternGraph.
     */
    public function build(PatternGraph $patternGraph): RuntimeGraph
    {
        $this->patternGraph = $patternGraph;
        $this->runtimeGraph = new RuntimeGraph;
        $this->levelCache = [];

        // Phase 1: Compute hierarchical levels for all nodes
        $this->computeAllLevels();

        // Phase 2: Create FunctionalColumn for each PatternNode
        $this->createAllColumns();

        // Phase 3: Create Pathways based on pattern relationships
        $this->createAllPathways();

        // Phase 4: Configure inhibitory circuits for AND nodes
        $this->configureInhibitoryCircuits();

        return $this->runtimeGraph;
    }

    /**
     * Phase 1: Pre-compute hierarchical levels.
     * LITERAL and POS are level 1.
     * OR and AND are one level above their highest child.
     */
    private function computeAllLevels(): void
    {
        foreach ($this->patternGraph->getAllNodes() as $node) {
            $this->computeLevel($node);
        }
    }

    private function computeLevel(PatternNode $node): int
    {
        // Return cached value if already computed
        if (isset($this->levelCache[$node->id])) {
            return $this->levelCache[$node->id];
        }

        $level = match ($node->type) {
            // Terminal nodes are at word level
            PatternNodeType::LITERAL,
            PatternNodeType::POS => 1,

            // OR: one level above the maximum of its children
            PatternNodeType::OR => $this->computeLevelForOR($node),

            // AND: one level above the maximum of left and right
            PatternNodeType::AND => $this->computeLevelForAND($node),
        };

        $this->levelCache[$node->id] = $level;

        return $level;
    }

    private function computeLevelForOR(PatternNode $node): int
    {
        $maxChildLevel = 0;
        foreach ($node->children as $childId) {
            $child = $this->patternGraph->getNode($childId);
            if ($child !== null) {
                $childLevel = $this->computeLevel($child);
                $maxChildLevel = max($maxChildLevel, $childLevel);
            }
        }

        return $maxChildLevel + 1;
    }

    private function computeLevelForAND(PatternNode $node): int
    {
        $leftLevel = 0;
        $rightLevel = 0;

        if ($node->leftChildId !== null) {
            $leftChild = $this->patternGraph->getNode($node->leftChildId);
            if ($leftChild !== null) {
                $leftLevel = $this->computeLevel($leftChild);
            }
        }

        if ($node->rightChildId !== null) {
            $rightChild = $this->patternGraph->getNode($node->rightChildId);
            if ($rightChild !== null) {
                $rightLevel = $this->computeLevel($rightChild);
            }
        }

        return max($leftLevel, $rightLevel) + 1;
    }

    /**
     * Phase 2: Create all FunctionalColumns.
     */
    private function createAllColumns(): void
    {
        foreach ($this->patternGraph->getAllNodes() as $node) {
            $column = $this->createColumn($node);
            $this->runtimeGraph->addColumn($column);

            // Build lookup indices for terminals
            if ($node->type === PatternNodeType::LITERAL && $node->value !== null) {
                $this->runtimeGraph->indexLiteral($node->value, $column);
            }
            if ($node->type === PatternNodeType::POS && $node->value !== null) {
                $this->runtimeGraph->indexPOS($node->value, $column);
            }
        }
    }

    private function createColumn(PatternNode $node): FunctionalColumn
    {
        //        echo "creating column: {$node->id} {$node->value} {$node->type} \n";
        $column = new FunctionalColumn($node->id, $node->value, $node->type);
        //        $column->sourceType = $node->type;
        //        $column->value = $node->value;
        $column->hierarchicalLevel = $this->levelCache[$node->id];

        return $column;
    }

    /**
     * Phase 3: Create all Pathways.
     */
    private function createAllPathways(): void
    {
        foreach ($this->patternGraph->getAllNodes() as $node) {
            match ($node->type) {
                PatternNodeType::OR => $this->createPathwaysForOR($node),
                PatternNodeType::AND => $this->createPathwaysForAND($node),
                default => null,  // LITERAL and POS have no outgoing structure
            };
        }
    }

    /**
     * OR node: any child can activate the parent.
     * Create bidirectional pathways to each child.
     */
    private function createPathwaysForOR(PatternNode $node): void
    {
        $parentColumn = $this->runtimeGraph->getColumnById($node->id);
        if ($parentColumn === null) {
            return;
        }

        foreach ($node->children as $childId) {
            $childColumn = $this->runtimeGraph->getColumnById($childId);
            if ($childColumn === null) {
                continue;
            }

            // Feedforward: child L5 -> parent L4
            // When child is recognized, it activates the parent
            $feedforward = new Pathway(
                source: $childColumn,
                target: $parentColumn,
                sourceLayer: 'L5',
                targetLayer: 'L4',
                direction: PathwayDirection::FEEDFORWARD
            );
            $this->runtimeGraph->addPathway($feedforward);
            $childColumn->feedforwardUp[] = $feedforward;

            // Feedback: parent L5 -> child L23
            // Parent predicts its possible children
            $feedback = new Pathway(
                source: $parentColumn,
                target: $childColumn,
                sourceLayer: 'L5',
                targetLayer: 'L23',
                direction: PathwayDirection::FEEDBACK
            );
            $this->runtimeGraph->addPathway($feedback);
            $parentColumn->feedbackDown[] = $feedback;
        }
    }

    /**
     * AND node: requires both left and right to complete.
     * Left activates first, right completes the construction.
     */
    private function createPathwaysForAND(PatternNode $node): void
    {
        $andColumn = $this->runtimeGraph->getColumnById($node->id);
        if ($andColumn === null) {
            return;
        }

        $leftColumn = null;
        $rightColumn = null;

        if ($node->leftChildId !== null) {
            $leftColumn = $this->runtimeGraph->getColumnById($node->leftChildId);
        }
        if ($node->rightChildId !== null) {
            $rightColumn = $this->runtimeGraph->getColumnById($node->rightChildId);
        }

        // Store references for binding logic
        $andColumn->leftSource = $leftColumn;
        $andColumn->rightSource = $rightColumn;

        // Create pathways for LEFT child
        if ($leftColumn !== null) {
            // Feedforward: left L5 -> AND L4
            // This pathway will be GATED by SOM until right arrives
            $leftFF = new Pathway(
                source: $leftColumn,
                target: $andColumn,
                sourceLayer: 'L5',
                targetLayer: 'L4',
                direction: PathwayDirection::FEEDFORWARD
            );
            $this->runtimeGraph->addPathway($leftFF);
            $leftColumn->feedforwardUp[] = $leftFF;

            // Feedback: AND L5 -> left L23
            $leftFB = new Pathway(
                source: $andColumn,
                target: $leftColumn,
                sourceLayer: 'L5',
                targetLayer: 'L23',
                direction: PathwayDirection::FEEDBACK
            );
            $this->runtimeGraph->addPathway($leftFB);
            $andColumn->feedbackDown[] = $leftFB;
        }

        // Create pathways for RIGHT child
        if ($rightColumn !== null) {
            // Feedforward: right L5 -> AND L4
            // This pathway is NOT gated - right arriving completes the AND
            $rightFF = new Pathway(
                source: $rightColumn,
                target: $andColumn,
                sourceLayer: 'L5',
                targetLayer: 'L4',
                direction: PathwayDirection::FEEDFORWARD
            );
            $this->runtimeGraph->addPathway($rightFF);
            $rightColumn->feedforwardUp[] = $rightFF;

            // Feedback: AND L5 -> right L23
            $rightFB = new Pathway(
                source: $andColumn,
                target: $rightColumn,
                sourceLayer: 'L5',
                targetLayer: 'L23',
                direction: PathwayDirection::FEEDBACK
            );
            $this->runtimeGraph->addPathway($rightFB);
            $andColumn->feedbackDown[] = $rightFB;
        }
    }

    /**
     * Phase 4: Configure inhibitory circuits for AND and OR nodes.
     */
    private function configureInhibitoryCircuits(): void
    {
        foreach ($this->patternGraph->getAllNodes() as $node) {
            if ($node->type === PatternNodeType::AND) {
                $this->configureANDInhibition($node);
            } elseif ($node->type === PatternNodeType::OR) {
                $this->configureORInhibition($node);
            }
        }
    }

    /**
     * For AND nodes:
     * - SOM blocks the construction from completing until right element arrives
     * - VIP fires when right element is recognized, releasing SOM
     */
    private function configureANDInhibition(PatternNode $node): void
    {
        $andColumn = $this->runtimeGraph->getColumnById($node->id);
        if ($andColumn === null) {
            return;
        }

        $leftColumn = $andColumn->leftSource;
        $rightColumn = $andColumn->rightSource;

        if ($leftColumn === null || $rightColumn === null) {
            return;
        }

        // Create SOM inhibitor
        $som = new SOM_Inhibitor;
        $som->owner = $andColumn;
        $som->releaseColumn = $rightColumn;  // Right element triggers release
        $andColumn->som = $som;

        // Create VIP inhibitor
        $vip = new VIP_Inhibitor;
        $vip->owner = $andColumn;
        $vip->targetSOMs = [$som];
        $vip->triggerColumn = $rightColumn;
        $andColumn->vip = $vip;

        // Gate the feedforward pathway FROM left TO the AND node
        foreach ($leftColumn->feedforwardUp as $pathway) {
            if ($pathway->target->id === $andColumn->id) {
                $pathway->gatingInhibitor = $som;
            }
        }
    }

    /**
     * For OR nodes:
     * - SOM blocks output pathways while expected children keep arriving
     * - VIP fires when unexpected POS arrives, releasing SOM
     * - On release, OR propagates based on accumulated activation
     */
    private function configureORInhibition(PatternNode $node): void
    {
        $orColumn = $this->runtimeGraph->getColumnById($node->id);
        if ($orColumn === null) {
            return;
        }

        // Collect the expected POS types for this OR node
        // These are the POS types that can ultimately feed into this OR
        $expectedPOSTypes = $this->collectLeafPOSTypes($node);

        // Only configure blocking if there are expected types
        // (otherwise this OR doesn't need locality blocking)
        if (empty($expectedPOSTypes)) {
            return;
        }

        // Create SOM inhibitor in OR mode
        $som = new SOM_Inhibitor;
        $som->owner = $orColumn;
        $som->mode = SOMMode::OR_ACCUMULATE;
        $som->expectedPOSTypes = $expectedPOSTypes;
        $orColumn->som = $som;

        // Create VIP inhibitor in OR mode
        $vip = new VIP_Inhibitor;
        $vip->owner = $orColumn;
        $vip->mode = SOMMode::OR_ACCUMULATE;
        $vip->expectedPOSTypes = $expectedPOSTypes;
        $vip->targetSOMs = [$som];
        $orColumn->vip = $vip;

        // Gate the OUTPUT pathways of the OR node
        foreach ($orColumn->feedforwardUp as $pathway) {
            $pathway->gatingInhibitor = $som;
        }
    }

    /**
     * Recursively collect all POS node IDs that can feed into this node.
     * This traverses down through OR and AND children to find the leaf POS types.
     */
    private function collectLeafPOSTypes(PatternNode $node): array
    {
        $posTypes = [];

        if ($node->type === PatternNodeType::POS) {
            // This is a leaf POS node
            $posTypes[] = $node->id;

        } elseif ($node->type === PatternNodeType::LITERAL) {
            // This is a leaf LITERAL node
            $posTypes[] = $node->id;

        } elseif ($node->type === PatternNodeType::OR) {
            // Collect from all alternatives
            foreach ($node->children as $childId) {
                $child = $this->patternGraph->getNode($childId);
                if ($child !== null) {
                    $posTypes = array_merge($posTypes, $this->collectLeafPOSTypes($child));
                }
            }

        } elseif ($node->type === PatternNodeType::AND) {
            // For AND, we only consider the LEFT child for "expected" types
            // because the right child completes the construction, it doesn't extend it
            //
            // Actually, for accumulating OR nodes, we might want BOTH children...
            // Let's collect from both for now
            if ($node->leftChildId !== null) {
                $leftChild = $this->patternGraph->getNode($node->leftChildId);
                if ($leftChild !== null) {
                    $posTypes = array_merge($posTypes, $this->collectLeafPOSTypes($leftChild));
                }
            }
            //            if ($node->rightChildId !== null) {
            //                $rightChild = $this->patternGraph->getNode($node->rightChildId);
            //                if ($rightChild !== null) {
            //                    $posTypes = array_merge($posTypes, $this->collectLeafPOSTypes($rightChild));
            //                }
            //            }
        }

        return array_unique($posTypes);
    }
}

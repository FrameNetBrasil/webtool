<?php

namespace App\Models\CLN_RNT;

class Column
{
    public  string $id;
    public  string $name;

    public readonly string $cortical_level;

    public readonly string $construction_type;

    public array $span; // Not readonly - SEQUENCER nodes can grow their span as they accumulate inputs

    public NeuralPopulation $L23;

    public NeuralPopulation $L5;

    public NeuralPopulation $PV;

    public NeuralPopulation $SOM;

    public NeuralPopulation $VIP;

    public float $activation = 0.0;

    public array $features = [];

    public array $bindings = [];

    public mixed $predicted_element = null;

    public bool $isRoot = false;

    /**
     * Internal edges connecting L23 to L5
     */
    public array $internal_edges = [];

    public bool $is_completed = false;

    // === RNT Integration Fields ===

    /**
     * Database ID of the RNT OR node this L2 represents
     * Null for L1 nodes and non-RNT L2 nodes
     */
    public ?int $rnt_or_node_id = null;

    /**
     * Database ID of the RNT DATA node that matched (for single-element constructions)
     * Null for composed constructions or non-RNT nodes
     */
    public ?int $rnt_data_node_id = null;

    /**
     * Database ID of the RNT AND node this L2 represents (if partial/complete)
     * Null for single-element constructions or non-RNT nodes
     */
    public ?int $rnt_and_node_id = null;

    /**
     * Database ID of the RNT SEQUENCER node this L2 represents
     * Null for non-SEQUENCER constructions
     */
    public ?int $rnt_sequencer_node_id = null;

    /**
     * RNT composition status
     * - 'single': Single-element construction (DATA→OR)
     * - 'partial_and': AND node with only one operand active (awaiting completion)
     * - 'complete_and': AND node with both operands active (composition finished)
     * - 'sequencer_partial': SEQUENCER node accumulating activation (awaiting all mandatory inputs)
     * - 'sequencer_ready': SEQUENCER node ready to propagate (all mandatory inputs active)
     * - null: L1 node or non-RNT L2 node
     */
    public ?string $rnt_status = null;

//    /**
//     * For partial AND nodes: Expected right operand
//     * Structure: [
//     *   'type' => 'OR',
//     *   'or_node_id' => int,
//     *   'construction_name' => string,
//     *   'pattern_id' => int,
//     *   'position' => int  // Expected position in sentence
//     * ]
//     */
//    public mixed $rnt_expected_right = null;
//
//    /**
//     * For partial AND nodes: Expected left operand
//     * (Used when right is active but left isn't yet - rare but possible)
//     * Same structure as rnt_expected_right
//     */
//    public mixed $rnt_expected_left = null;

    public function __construct(
        string $cortical_level, // L1 or L2
        string $construction_type, // phrasal, sequencer, mwe
        array $span,
        string $id,
        string $name,
        array $features = []
    ) {
        $this->cortical_level = $cortical_level;
        $this->construction_type = $construction_type;
        $this->span = $span;
        $this->id = $id;
        $this->features = $features;
        $this->name = $name;

        $this->L23 = new NeuralPopulation($this->computeTauL23(), 'L23', $this);
        $this->L5 = new NeuralPopulation($this->computeTauL5(),'L5', $this);
        $this->PV = new NeuralPopulation(10.0,'PV', $this);
        $this->SOM = new NeuralPopulation($this->computeTauSOM(),'SOM', $this);
        $this->VIP = new NeuralPopulation(20.0,'VIP', $this);

        $this->isRoot = false;

        // Create internal structure
        //if (($construction_type != 'literal') && ($construction_type != 'pos')){
            //$this->createInternalStructure($construction_type);
        //}

    }

    private function computeTauL23(): float
    {
        return match ($this->cortical_level) {
            'L1' => 10.0,
            'L2' => 20.0,
            default => 15.0,
        };
    }

    private function computeTauL5(): float
    {
        return match ($this->cortical_level) {
            'L1' => 50.0,
            'L2' => 80.0,
            default => 65.0,
        };
    }

    private function computeTauSOM(): float
    {
        return match ($this->cortical_level) {
            'L1' => 50.0,
            'L2' => 80.0,
            default => 65.0,
        };
    }

    public function getPosition(): int
    {
        return $this->span[0];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSpanString(): string
    {
        return "{$this->span[0]}:{$this->span[1]}";
    }

    public function getNodesByLayer(string $layer): array {
        $nodes = [];
        if ($layer === 'L23') {
            $nodes = $this->L23->getNodes();
        } else if ($layer === 'L5') {
            $nodes = $this->L5->getNodes();
        } else if ($layer === 'PV') {
            $nodes = $this->PV->getNodes();
        } else if ($layer === 'SOM') {
            $nodes = $this->SOM->getNodes();
        } else if ($layer === 'VIP') {
            $nodes = $this->VIP->getNodes();
        }
        return $nodes;
    }

    public function getSNodes(): array {
        return $this->L5->getNodes();
    }

    // === RNT Helper Methods ===

    /**
     * Check if this node represents an RNT construction
     *
     * @return bool True if this is an RNT construction (has OR node ID, AND node ID, SEQUENCER node ID, or RNT status)
     */
    public function isRNTConstruction(): bool
    {
        return $this->rnt_or_node_id !== null
            || $this->rnt_and_node_id !== null
            || $this->rnt_sequencer_node_id !== null
            || $this->rnt_status !== null;
    }

    /**
     * Check if this is an intermediate AND node (no OR composition yet)
     */
    public function isIntermediateAnd(): bool
    {
        return $this->rnt_status === 'intermediate_and';
    }

    /**
     * Check if this is a partial AND composition awaiting completion
     *
     * @return bool True if partial AND (one operand active, awaiting other)
     */
    public function isPartialAnd(): bool
    {
        return $this->rnt_status === 'partial_and';
    }

    /**
     * Check if this represents a completed AND composition
     *
     * @return bool True if complete AND (both operands active)
     */
    public function isCompleteAnd(): bool
    {
        return $this->rnt_status === 'complete_and';
    }

    /**
     * Check if this is a single-element RNT construction
     *
     * @return bool True if single-element construction (DATA→OR)
     */
    public function isSingleElementRNT(): bool
    {
        return $this->rnt_status === 'single';
    }

    /**
     * Check if this is a SEQUENCER node
     *
     * @return bool True if SEQUENCER node
     */
    public function isSequencer(): bool
    {
        return $this->rnt_sequencer_node_id !== null
            || in_array($this->rnt_status, ['sequencer_partial', 'sequencer_ready']);
    }

    /**
     * Check if this is a partial SEQUENCER node (accumulating activation)
     *
     * @return bool True if partial SEQUENCER (awaiting mandatory inputs)
     */
    public function isSequencerPartial(): bool
    {
        return $this->rnt_status === 'sequencer_partial';
    }

    /**
     * Check if this is a ready SEQUENCER node (can propagate)
     *
     * @return bool True if SEQUENCER ready to propagate (all mandatory inputs active)
     */
    public function isSequencerReady(): bool
    {
        return $this->rnt_status === 'sequencer_ready';
    }

    /**
     * Get RNT status description for debugging
     *
     * @return string Human-readable RNT status
     */
    public function getRNTStatusDescription(): string
    {
        if (! $this->isRNTConstruction()) {
            return 'Non-RNT construction';
        }

        return match ($this->rnt_status) {
            'single' => 'Single-element construction',
            'partial_and' => 'Partial AND (awaiting right operand)',
            'complete_and' => 'Complete AND composition',
            'sequencer_partial' => 'SEQUENCER accumulating activation (awaiting mandatory inputs)',
            'sequencer_ready' => 'SEQUENCER ready to propagate',
            default => 'Unknown RNT status',
        };
    }




}

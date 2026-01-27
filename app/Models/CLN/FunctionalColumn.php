<?php

namespace App\Models\CLN;

/**
 * A functional column representing a single grammar element at runtime.
 * Each column has the layered structure corresponding to cortical organization.
 */
class FunctionalColumn
{
    public string $id;              // from idPatternNode
    public string $name;            // e.g., "NOUN", "cat"
    public PatternNodeType $type;            // 'LITERAL', 'POS', 'OR', 'AND'

    public int $hierarchicalLevel;

    // Layer-specific activation states
    public LayerState $L23;         // Prediction Error computation layer
    public LayerState $L4;          // Input reception layer
    public LayerState $L5;          // Internal Representation/prediction layer
    public LayerState $L6a;         // Sequential position tracking layer
    public LayerState $L6b;         // Syntactic context/frame layer

    // Structural connections (the "pipes")
    // Pathway references (filled during graph construction)
    /** @var Pathway[] */
    public array $feedforwardUp = [];    // Pathways going to higher levels
    /** @var Pathway[] */
    public array $feedbackDown = [];     // Pathways going to lower levels
    /** @var Pathway[] */
    public array $lateralOut = [];       // Same-level connections outgoing
    /** @var Pathway[] */
    public array $lateralIn = [];        // Same-level connections incoming

    // Inhibitory control
    public ?SOM_Inhibitor $som;
    public ?VIP_Inhibitor $vip;
    public ?PV_Inhibitor $pv;

    // For AND nodes: references to the columns that must combine
    public ?FunctionalColumn $leftSource = null;
    public ?FunctionalColumn $rightSource = null;

    public function __construct(
        string $id,
        string $name,
        PatternNodeType $type
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->feedforwardUp = [];
        $this->feedbackDown = [];
        $this->lateralIn = [];
        $this->lateralOut = [];
        $this->som = null;
        $this->vip = null;
        $this->pv = null;
        $this->L23 = new LayerState();
        $this->L4 = new LayerState();
        $this->L5 = new LayerState();
        $this->L6a = new LayerState();
        $this->L6b = new LayerState();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function resetActivations(): void
    {
        $this->L23->reset();
        $this->L4->reset();
        $this->L5->reset();
        $this->L6a->reset();
        $this->L6b->reset();
    }

}

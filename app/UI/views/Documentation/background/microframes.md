---
title: Microframes
order: 20
description: Microframes
---

# Microframes

## What is a microframe?

A microframe is a specialized type of frame designed to represent relationships between entities as semantic frames.
The core idea is simple: treat relations themselves as frames, allowing for richer semantic modeling and greater specificity
in describing how entities interact.

## Structure

Microframes share the same structural foundation as traditional frames, but with a key constraint:

- They contain exactly **two Frame Elements (FEs)**: one representing the **Domain** of the relation and another representing the **Range**.

- Each FE must have a name that appropriately reflects the specific relation it represents, following the same naming principles as standard frames.

## Features

### Lexical Units

Like traditional frames, microframes can be evoked by **Lexical Units (LUs)**. This allows specific words or phrases to trigger
the relational concepts encoded in microframes, bridging lexical semantics with structural relations.

### Greater Specificity

Microframes enable the expression of **much more specific relations** than those typically captured by standard FrameNet frames.
While traditional frames model broad conceptual situations, microframes focus on precise relational nuances between entities.

### Multi-Frame Associations

A single microframe can be **associated with multiple frames**, allowing it to function as a reusable semantic component
across different conceptual domains.

## Use Cases

### Components of Complex Frames

Microframes can make explicit the relations between FrameElements of more complex frames through the use of frame-to-frame relations such as:

- **Subframe**: A microframe can be seen as a specific subframe of a larger, more complex frame, focusing in one aspect of the larger frame.
- **Perspective**: Different microframes can offer distinct perspectives on the situation expressed by a larger frame.

The relations Frame-Frame and FrameElement-FrameElement follow the same pattern already established in FrameNet.

### Qualia Relations

Microframes are used to express qualia relations between LUs, evolving the original idea of TQR (Ternary Qualia Relation).

```dot
digraph G {
    graph [fontname="Noto Sans" fontsize=12];
    node [fontname="Noto Sans" fontsize=12 shape=rectangle style=filled color="#9370DB" fillcolor="#ECECFF"];
    edge [fontname="Noto Sans" fontsize=12];
    layout=dot
    rankdir=LR;
    bgcolor=transparent;

    subgraph cluster_0 {
        style=filled;
        color=lightyellow;
        label = "Qualia relations";
        
        cB [label="LU_B"]
        cA [label="LU_A"]
        mf [label="Microframe"]
        relation [shape=point fillcolor=black]
        
        // Force mf to be on the right side
        {rank=same; relation; mf}
        
        cB -> relation  [arrowsize=0.3 label="domain" color="red" fontcolor=red]
        cA -> relation [arrowsize=0.3  label="range"  color="blue" fontcolor=blue]
        mf -> relation [arrowsize=0.3]
    }
}
```

### Ontological relations

Microframes are used to express subsumption relations between classes and between properties (microframes). The same structure is
used to express object properties (relations between ontological classes).

```dot
digraph G {
    graph [fontname="Noto Sans" fontsize=12];
    node [fontname="Noto Sans" fontsize=12 shape=rectangle style=filled color="#9370DB" fillcolor="#ECECFF"];
    edge [fontname="Noto Sans" fontsize=12];
    layout=dot
    rankdir=LR;
    bgcolor=transparent;

    subgraph cluster_0 {
        style=filled;
        color=lightyellow;
        label = "Subsumption/relations between classes/properties";
        
        cB [label="Class_Microframe_B"]
        cA [label="Class_Microframe_A"]
        mf [label="Relation (Microframe)"]
        relation [shape=point fillcolor=black]
        
        // Force mf to be on the right side
        {rank=same; relation; mf}
        
        cA -> relation  [arrowsize=0.3 label="Subframe (domain)" color="red" fontcolor=red]
        cB -> relation [arrowsize=0.3  label="Superframe (range)"  color="blue" fontcolor=blue]
        mf -> relation [arrowsize=0.3]
    }
}
```

Microframes are used to express OWL restrictions for ontology classes (represented as Frame Elements in the the class).

```dot
digraph G {
    graph [fontname="Noto Sans" fontsize=12];
    node [fontname="Noto Sans" fontsize=12 shape=rectangle style=filled color="#9370DB" fillcolor="#ECECFF"];
    edge [fontname="Noto Sans" fontsize=12];
    layout=dot
    rankdir=LR;
    bgcolor=transparent;

    subgraph cluster_0 {
        style=filled;
        color=lightyellow;
        label = "Class properties";
        
        cB [label="Class_B"]
        cA [label="Class_A"]
        mf [label="Microframe"]
        relation [shape=point fillcolor=black]
        feX [shape=circle label="", xlabel="fe_x" width="0.1"]
        
        // Force mf to be on the right side
        {rank=same; relation; mf}
        
        cA -> feX [arrowsize=0.3]
        feX -> relation  [arrowsize=0.3 label="domain" color="red" fontcolor=red]
        cB -> relation [arrowsize=0.3  label="range"  color="blue" fontcolor=blue]
        mf -> relation [arrowsize=0.3]
    }
}
```


### Generalization Across Events

Microframes allow for the **generalization of situations** that are common across a wide range of events.
They can specifically express aspects such as:

- **Time**: Temporal relations between entities or events.
- **Manner**: The way in which an action or relation occurs.
- **Means**: The method or instrument through which a relation is established.
- Other circumstantial or participant-oriented dimensions.

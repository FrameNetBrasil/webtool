---
title: The world model
order: 3
description: The world model
---

# The World Model: Fundamental Categories

## The Top-Level Ontology

DUL's root is **Entity**, defined as: *"Anything: real, possible, or imaginary, which some modeller wants to talk about for some purpose."*

This maximally permissive definition reflects DUL's **pluralistic** stance: if someone needs to model something, it qualifies as an Entity.

### Direct subclasses of Entity:

```
Entity
├── Abstract          (not located in space-time)
├── Event             (temporal extent, participants)
├── InformationEntity (information: abstract or concrete)
├── Object            (spatial location)
├── Quality           (aspects of entities)
└── Situation         (contextualized views)
```

## Abstract Entities

**Abstract**: Entities not located in space-time.

### Key subclasses:

**FormalEntity**
- Formally defined, context-independent, "Platonic" entities
- Mathematical entities: sets, categories, functions
- Distinguished from Concepts (which are social/context-dependent)
- Example: The mathematical set ℕ (natural numbers)

**Region**
- Values in dimensional spaces
- Subclasses represent different dimensions:
    - **SpaceRegion**: Spatial coordinates, geometries
    - **TimeInterval**: Temporal extents
    - **Amount**: Quantities (mass, volume, count)
    - **PhysicalAttribute**: Physical measurements (temperature, pressure)
    - **SocialObjectAttribute**: Social attributes (salary level, legal status)

**Why Regions are Abstract:**
- The number "42" exists independently of any particular 42 objects
- The color "red" exists independently of any particular red object
- They inhabit abstract dimensional spaces, not physical space-time

## Objects

**Object**: Entities with spatial location, participating in events.

### The Physical vs. Social Divide

DUL makes **PhysicalObject** and **SocialObject** disjoint—a fundamental ontological distinction:

### PhysicalObject
- Has spatial region
- Has (typically) mass
- Exists independently of communication

**Subclasses:**
- **PhysicalBody**: Natural material objects
    - **BiologicalObject**: Living organisms
    - **ChemicalObject**: Chemical substances
    - **Substance**: Materials (water, steel, DNA)
- **PhysicalArtifact**: Human-made physical objects
    - **DesignedArtifact**: Artifacts with explicit design (cars, buildings)
- **PhysicalPlace**: Locations understood as physical regions

### SocialObject
- Exists through communication (in "some communication Event")
- Must be **expressed by InformationObject**
- Disjoint from PhysicalObject

**Subclasses:**
- **Description**: Conceptual schemas (theories, frames)
    - Plan, Design, Diagnosis, Norm, Contract, Goal, Theory, Narrative
- **Concept**: Categories defined in descriptions
    - Role, Task, EventType, Parameter
- **Collection**: Containers for entities sharing properties
    - Configuration, Collective, TypeCollection
- **InformationObject**: Abstract information pieces
- **Place**: Socially constructed locations (countries, neighborhoods)

### Agent (Cross-cutting)
A special Object category for entities with agency:

- **PhysicalAgent**: Biological agents (organisms, persons as physical beings)
- **SocialAgent**: Socially constructed agents
    - **Organization**: Structured institutions (companies, governments)
    - **CollectiveAgent**: Groups acting collectively
        - **Group**: Coordinated collectives (committees, teams)
        - **Community**: Large-scale collectives (societies, movements)

**Person** is modeled with two facets:
- **NaturalPerson**: The physical/biological aspect (extends Person and PhysicalAgent)
- **SocialPerson**: The social/legal aspect (extends Person)

This dual modeling reflects that a person is both a physical organism and a social entity with roles, rights, and legal status.

## Events

**Event**: "Any physical, social, or mental process, event, or state."

### DUL's Aspectual Neutrality

The Event class documentation provides extensive philosophical discussion:

**The Problem**: The same real-world occurrence can be viewed as:
- An **accomplishment** (process leading to a result)
- An **achievement** (the result state)
- A **punctual event** (time-collapsed)
- A **transition** (change between states)

**DUL's Solution**: Don't classify Events by aspect—use **Situations** for aspectual views:
- The Event "rock erosion in Sinni valley" has a single identity
- **ErosionAsAccomplishment**: A Situation viewing it as a process
- **ErosionAsTransition**: A Situation viewing it as a state change
- Both Situations include the same Event but satisfy different Descriptions (theories of aspect)

### Subclasses:

**Action**
- Event with at least one **Agent** participant
- Executes a **Task** typically defined in a Plan
- Intentional, goal-directed

**Process**
- Event without agentive focus
- Natural or social processes (erosion, inflation, aging)

**State** (implied but not always distinguished)
- Stative events (being tall, being red)

## Qualities

**Quality**: Individual aspects of entities that cannot exist independently.

**Examples:**
- The specific yellowness of Dmitri's skin (not yellowness in general)
- The specific height of this building (not 180cm in general)
- The specific beauty of this painting

**Key relations:**
- `isQualityOf`: Links quality to its bearer (entity)
- `hasRegion`: Links quality to its value (region)

**When to use Qualities:**
DUL advises using Qualities only when **individual aspects matter**:
- **Relevant**: Antique furniture appraisal (each piece's individual patina, color, texture)
- **Irrelevant**: Assembly line quality control (only conformance to design parameters matters)

For most domains, direct attributes suffice. Qualities enable:
- Fine-grained observation modeling
- Temporal change tracking (same quality, different regions over time)
- Multi-perspective measurement (same quality, different parameters)

## Situations

**Situation**: "A view, consistent with a Description, on a set of entities."

### Dual Nature:

1. **Epistemological**: A framed interpretation of reality
    - Created by observers applying conceptual frames
    - Multiple Situations can include the same entities (different framings)

2. **Technical**: Reified n-ary relations
    - Binary relations project from Situations via `isSettingFor`
    - Enables time-indexing and parameter-based relations

### Key Subclasses:

**TimeIndexedRelation**
- Situations specifically for temporal context
- **Classification**: Time-indexed concept-entity classification
- **Parthood**: Time-indexed part-whole relations

**PlanExecution**
- Situation of executing a Plan
- Links Actions to Tasks, Agents to Roles

**WorkflowExecution**
- Situation of executing a Workflow
- Temporal sequencing of tasks

**Transition**
- Situation of change between states

## InformationEntity

**InformationEntity**: A catchall for information, abstract or concrete.

**Motivation**: Bypass ambiguities in ordinary language:
- "The 3rd Gymnopedie" could mean the composition (abstract) or a particular recording (concrete)
- InformationEntity covers both, allowing underspecification when convenient

**Subclasses:**
- **InformationObject** (Abstract): The information content
- **InformationRealization** (Concrete): Physical/event realization

**Relation**: `realizes` connects realization to object

## ObjectAggregate

**ObjectAggregate**: Aggregates of distributed objects from a Collection.

**Distinction:**
- **Collection**: First-order entity (a social object unifying members conceptually)
- **ObjectAggregate**: The distributed physical aggregate of members
- **Set**: Second-order formal entity (abstract set in mathematical sense)

**Example:**
- **Collection**: "The Louvre Egyptian collection" (institutional concept)
- **ObjectAggregate**: The physical artifacts distributed in display cases
- **Set**: The mathematical set {artifact₁, artifact₂, ...}

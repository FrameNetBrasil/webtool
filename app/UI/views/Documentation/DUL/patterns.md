---
title: Design patterns
order: 5
description: Design patterns
---

# Key Design Patterns

## Overview of Object Properties


```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
    layout=dot;

  Description [color=purple fillcolor=purple fontcolor=purple] 
  Situation [color=orange fillcolor=orange fontcolor=orange]
  Collection [color=deeppink fillcolor=deeppink fontcolor=deeppink]
  Entity
  "Entity*"
  Quality [color=brown fillcolor=brown fontcolor=brown]
  Region [color=brown fillcolor=brown fontcolor=brown]
  "Object Aggregate"
  Event [color=cyan3 fillcolor=cyan3  fontcolor=cyan3]
  
  
  Concept [color=blue fillcolor=blue fontcolor=blue]
  Role [color=blue fillcolor=blue fontcolor=blue]
  Task [color=blue fillcolor=blue fontcolor=blue]
  Parameter [color=blue fillcolor=blue fontcolor=blue]

  Object
  "Social Object"
  "Object Aggregate"

  "Information Realization" [color=darkgreen fillcolor=darkgreen fontcolor=darkgreen]
  "Information Object" [color=darkgreen fillcolor=darkgreen fontcolor=darkgreen]

  Agent [color=red fillcolor=red  fontcolor=red]
  "Physical Agent" [color=red fillcolor=red  fontcolor=red]
  "Social Agent" [color=red fillcolor=red  fontcolor=red]

  Agent -> "Social Agent" [label="acts for" color="red" fontcolor="red"]
  Concept -> "Collection" [label="characterizes" color="blue"  fontcolor="blue"]
  Concept -> "Entity" [label="classifies" color="blue" fontcolor="blue"]
  Agent -> "Social Object"   [label="conceptualizes" color="red"  fontcolor="red"]
  "Information Realization" -> Situation [label="concretely expresses" color="darkgreen" fontcolor="darkgreen"]
  Concept -> "Collection" [label="covers" color="blue" fontcolor="blue"]
  Description -> Concept [label="defines"  color="purple" fontcolor="purple"]
  Description -> Role [label="defines role"   color="purple" fontcolor="purple"]
  Description -> Task [label="defines task"   color="purple" fontcolor="purple"]
  Description -> Parameter [label="defines"   color="purple" fontcolor="purple"]
  Description -> Entity [label="describes"   color="purple" fontcolor="purple"]
  "Information Object" -> "Social Object" [label="expresses" color="darkgreen" fontcolor="darkgreen"]
  "Information Object" -> Concept [label="expresses concept" color="darkgreen"  fontcolor="darkgreen"]
  Collection -> Entity [label="has member" color="deeppink" fontcolor="deeppink" fontcolor="deeppink"]
  Entity -> Quality [label="has quality"]
  Entity -> Region [label="has region"]
  Object -> Role [label="has  role"]
  Situation -> Entity [label="is setting for"  color="orange" fontcolor="orange"]
  Role -> Task [label="has task" color="blue" fontcolor="blue"]
  Description -> "Social Agent" [label="introduces"   color="purple" fontcolor="purple"]
  "Information Object" -> Entity [label="is about" color="darkgreen" fontcolor="darkgreen"]
  "Information Realization" -> "Information Object" [label="realizes" color="darkgreen" fontcolor="darkgreen"]
  "Information Realization" -> Entity [label="realizes information about" color="darkgreen" fontcolor="darkgreen"]
  Situation -> Description [label="satisfies" color="orange" fontcolor="orange"]
  Description -> Collection [label="unifies"   color="purple" fontcolor="purple"]
  Description -> Concept [label="uses concept"    color="purple" fontcolor="purple"]
  "Physical Agent" -> "Social Agent" [label="acts through" color="red" fontcolor="red"]
  "Object Aggregate" -> Collection [label="is member of"]
  Event -> Object [label="has participant" color=cyan3 fontcolor=cyan3]
  Event -> Agent [label="has participant" color=cyan3 fontcolor=cyan3]
  Entity -> "Entity*" [label="has part"]
  Entity -> "Entity*" [label="has proper part"]
  Entity -> "Entity*" [label="has component"]
  Entity -> "Entity*" [label="has constituent"]
  Entity -> "Entity*" [label="precedes"]
  Entity -> "Entity*" [label="directly precedes"]
  Parameter -> Region [label="classifies" color="blue" fontcolor="blue"]
  Situation -> Event [label="includes event"  color="orange" fontcolor="orange"]
  Situation -> Object [label="includes object"  color="orange" fontcolor="orange"]
}

```

## Overview of DUL Patterns

DUL is not just a taxonomy but a **library of reusable patterns**. Each pattern solves a recurring modeling problem.

**Major Patterns:**
1. Descriptions & Situations (D&S)
2. Quality-Region
3. Participation
4. Information Realization
5. Mereological Patterns
6. Classification
7. Place
8. Sequence

## The Descriptions & Situations (D&S) Pattern

**Problem**: How to model context-dependent relations and time-varying properties?

**Solution**: Separate **intensional** (conceptual) and **extensional** (actual) aspects:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=TB;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
    layout=dot;

  Description [color=blue fillcolor=blue] 
  Concept [color=blue fillcolor=blue]
  Task [color=blue fillcolor=blue]
  Parameter [color=blue fillcolor=blue]
  Role [color=blue fillcolor=blue]
  Situation [color=orange fillcolor=orange]
  E [label="Entity \n(classified, related,\ncontextualized)"]
  
  Description -> Concept [label="defines" color=blue fontcolor=blue]
  Description -> Role [label="defines" color=blue fontcolor=blue]
  Description -> Task [label="defines" color=blue fontcolor=blue]
  Description -> Parameter [label="defines" color=blue fontcolor=blue]
  
  Situation -> Description [label="satisfies" color=orange fontcolor=orange]
  Situation -> E [label="is setting for" color=orange fontcolor=orange]
  
}

```

**When to use:**
- Role assignment: Agent plays Role in Situation
- Task execution: Action executes Task in Situation
- Time-indexed relations: Relations hold within temporal Situations
- Multi-perspective modeling: Same entities, different Situations/Descriptions

**Example Pattern Instance:**
```
Recipe (Plan, a Description)
  --defines--> 'Ingredient' (Role)
  --defines--> 'Mixing' (Task)

CookingSession (PlanExecution, a Situation)
  --satisfies--> Recipe
  --includesAgent--> Chef₁ (plays 'Cook' Role)
  --includesAction--> Mixing₁ (executes 'Mixing' Task)
  --includesObject--> Flour₁ (plays 'Ingredient' Role)
```

## The Quality-Region Pattern

**Problem**: How to model attributes with:
- Multiple measurement systems
- Temporal change
- Observational relativity

**Solution**: Reify attributes as Qualities, link to abstract Regions:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
    layout=dot;

  Entity -> Quality [label="has quality"]
  Quality -> Region [label="has region"]
  
}

```

**When to use:**
- Scientific observation: Same phenomenon, different measurements
- Individual aspects matter: Not just "height" but "this building's height"
- Parameterized constraints: Concepts constrain regions through parameters

**Example Pattern Instance:**
```
DmitrisSkin (Quality)
  --isQualityOf--> Dmitri (Person)
  --hasRegion--> Yellow₁ (SocialObjectAttribute / Color value)

MeasurementSituation
  --includesObject--> Dmitri
  --includesQuality--> DmitrisSkin
  --satisfies--> ColorimetryDescription
    --defines--> RGBParameter
```

## The Participation Pattern

**Problem**: How to link events and objects?

**Solution**: Symmetric participation relation:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;
    
   
  Event -> Object [label="has participant"]
  Event -> "Agent (Object)" [label="has participant"]
  
}

```

**Extensions:**
- **Agent participation**: Action --hasParticipant--> Agent (agent-specific)
- **Co-participation**: Object --coparticipatesWith--> Object (derived from shared event)

**When to use:**
- Event modeling: Who/what is involved?
- Provenance: What events affected this object?
- Social network analysis: Who co-participated in events?

**Example Pattern Instance:**
```
TennisMatch₁ (Event)
  --hasParticipant--> Vitas (Agent)
  --hasParticipant--> Jimmy (Agent)
  --hasParticipant--> TennisBall₁ (Object)

Vitas --coparticipatesWith--> Jimmy (inferred)
```

## The Information Realization Pattern

**Problem**: Distinguish abstract information from concrete manifestations.

**Solution**: Two-level information model:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;
    
  "Information Realization" [color=darkgreen fillcolor=darkgreen]
  "Information Object" [color=darkgreen fillcolor=darkgreen]
  "Social Object" [color=red fillcolor=red]
  
  "Information Realization" -> "Information Object" [label="realizes" color=darkgreen fontcolor=darkgreen]
  "Information Object" -> "Social Object" [label="expresses" color=darkgreen fontcolor=darkgreen] 
}

```

**When to use:**
- Cultural heritage: Work vs. manifestations (FRBR-compatible)
- Legal documents: Legal content vs. physical copies
- Knowledge representation: Concepts vs. terms expressing them

**Example Pattern Instance:**
```
Constitution_ItalianRepublic (InformationObject)
  --expresses--> ItalianLegalSystem (Description/Norm)

PhysicalCopy₁ (InformationRealization / PhysicalObject)
  --realizes--> Constitution_ItalianRepublic

OralRecitation₁ (InformationRealization / Event)
  --realizes--> Constitution_ItalianRepublic
```

## Mereological Patterns

**Problem**: Model part-whole relations with different characteristics.

**Solution**: Multiple parthood properties:

**Pattern A: Reflexive Part-Whole**

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    E [label="Entity\n(reflexive, transitive)"]
    Entity -> E [label="has part"]    
}

```

Use when: General decomposition, entity is part of itself (mereologically valid)

**Pattern B: Proper Part-Whole**

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    E [label="Entity\n(asymetric, transitive)"]
    Entity -> E [label="has proper part"]    
}

```

Use when: Strict decomposition, part ≠ whole

**Pattern C: Component-System**

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    E [label="Entity\n(asymetric, NOT transitive)"]
    Entity -> E [label="has component"]    
}

```

Use when: Designed artifacts with direct structural components

**Pattern D: Constitution**

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    E [label="Entity\n(cross-layer)"]
    Entity -> E [label="has constituent"]    
}

```

Use when: Different ontological strata (social/physical, organism/molecular)

**Example Pattern Instance:**
```
Car₁ (DesignedArtifact)
  --hasComponent--> Engine₁ (direct component)
  --hasComponent--> Wheel₁ (direct component)

Engine₁
  --hasProperPart--> Piston₁ (engine part)

Car₁ --hasPart--> Piston₁ (inferred via transitivity of hasPart, if rules applied)

Person₁ (BiologicalObject)
  --hasConstituent--> Molecule₁ (cross-layer: organism → molecular)
```

## The Classification Pattern

**Problem**: Context and time-dependent classification.

**Solution**: Classification as a special Situation:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=LR;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    C [label="Classification (TimeIndexedRelation)"]
    D [label="Description (defines Concept)"]
    CC [label="Concept (classifier)"]
    EC [label="Entity (classified)"]
    T [label="TimeInterval (when)"]
    
    C -> D [label="satisfies"]
    C -> CC [label="is setting for"]
    C -> EC [label="is setting for"]
    C -> T [label="is setting for"]    
}

```

**When to use:**
- Role assignment: Agents play different roles at different times
- Taxonomic classification: Species membership, artifact categories
- Status tracking: Legal status, health status, employment status over time

**Example Pattern Instance:**
```
Classification₁
  --satisfies--> TrafficLaw (Norm defining vehicle types)
  --isSettingFor--> Vehicle₁ (Entity)
  --isSettingFor--> 'Truck' (Concept)
  --isSettingFor--> Interval₂₀₂₄ (when registered as truck)

Classification₂
  --satisfies--> HistoricalArchive (Description)
  --isSettingFor--> Vehicle₁ (same entity)
  --isSettingFor--> 'ClassicCar' (different Concept)
  --isSettingFor--> Interval₂₀₃₀ (after retirement from service)
```

## The Place Pattern

**Problem**: Locations are both physical and social constructs.

**Solution**: Distinguish PhysicalPlace and Place:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=TB;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    PP [label="PhysicalPlace (PhysicalObject)"]
    SR [label="SpaceRegion (Region)\n(geometric location)"]
    PS [label="Place (SocialObject)"]
    IO [label="InformationObject\n(social definition)"]
    
    PS -> PP [label="describes"]
    PS -> IO [label="is expressed by"]
    PP -> SR [label="has (space) region"] 
    
}

```

**When to use:**
- Physical location: Coordinates, geometric regions
- Social/administrative places: Countries, neighborhoods, institutions
- Hybrid: "Paris" is both a geographic region and a social construct

**Example Pattern Instance:**
```
Paris_Geographic (PhysicalPlace)
  --hasSpaceRegion--> Region48.8566N_2.3522E

Paris_City (Place, SocialObject)
  --isExpressedBy--> ParisCharter (InformationObject / legal document)
  --describes--> Paris_Geographic
  --isMemberOf--> FrenchCities (Collection)
```

## The Sequence Pattern

**Problem**: Model temporal or logical ordering.

**Solution**: Precedence relations:

```dot
# http://www.graphviz.org/content/cluster

digraph G {
  rankdir=TB;
  graph [fontname = "Noto Sans"];
  node [fontname = "Noto Sans" shape="box" style="rounded" ];
  edge [fontname = "Noto Sans"];
  layout=dot;

    ET [label="Entity\n(transitive)"]
    EI [label="Entity\n(intransitive, strict adjacency)"]
    
    Entity -> ET [label="precedes"]
    Entity -> EI [label="directly precedes"]
     
    
}

```

**When to use:**
- Workflow: Task sequences
- Narrative: Event chronology
- Procedure: Step ordering

**Example Pattern Instance:**
```
Workflow₁ (Description)
  --defines--> Task₁, Task₂, Task₃

Task₁ --directlyPrecedes--> Task₂
Task₂ --directlyPrecedes--> Task₃

Task₁ --precedes--> Task₃ (inferred via transitivity)

WorkflowExecution₁ (Situation)
  --satisfies--> Workflow₁
  --includesAction--> Action₁ (executes Task₁)
  --includesAction--> Action₂ (executes Task₂)
  --includesAction--> Action₃ (executes Task₃)
```

## Pattern Selection Guide

| Modeling Need | Pattern | Key Classes | Key Properties |
|---------------|---------|-------------|----------------|
| Role assignment | D&S + Classification | Description, Role, Agent, Classification | defines, classifies, hasRole |
| Task execution | D&S + Participation | Plan, Task, Action, Agent | defines, executesTask, hasParticipant |
| Time-varying attributes | Quality-Region | Quality, Region, TimeInterval | hasQuality, hasRegion |
| Document modeling | Information Realization | InformationObject, InformationRealization | realizes, expresses |
| Part-whole decomposition | Mereological | Entity | hasPart, hasComponent, hasConstituent |
| Event participation | Participation | Event, Object, Agent | hasParticipant, coparticipatesWith |
| Workflow modeling | D&S + Sequence | Workflow, Task, Action, WorkflowExecution | defines, precedes, executesTask |
| Multi-perspective views | D&S | Description, Situation, Entity | satisfies, isSettingFor |
| Location modeling | Place | PhysicalPlace, Place, SpaceRegion | hasLocation, hasRegion |
| Organizational structure | SocialAgent + D&S | Organization, Role, Agent, Description | actsFor, hasRole, defines |

## Pattern Composition

Real-world modeling typically **combines multiple patterns**:

**Example: Project Management Ontology**

Uses:
1. **D&S**: Project (Plan) defines Roles/Tasks, ProjectExecution (Situation) satisfies Project
2. **Participation**: Actions have Agent participants
3. **Classification**: Agents classified by Roles (ProjectManager, Developer)
4. **Sequence**: Tasks ordered by precedence
5. **Quality-Region**: Project attributes (budget, timeline) as Qualities with Amount/TimeInterval Regions
6. **Mereological**: Project has sub-projects as proper parts

**Pattern Integration Strategy:**
1. Identify core entities (agents, resources, activities)
2. Apply D&S for role/task structure
3. Apply Participation for event-object links
4. Apply Classification for role assignment
5. Apply Sequence for workflow ordering
6. Apply Quality-Region for measurements/KPIs
7. Apply Mereology for hierarchical decomposition

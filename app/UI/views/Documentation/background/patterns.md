---
title: Patterns
order: 16
description: Patterns
---

# Patterns

The idea of "design patterns" was introduced in the 1970s by mathematician and architect Christopher Alexander. In simplified terms, the use of "patterns" aims to share guidelines that help solve design problems. As he says, "each pattern describes a problem in our environment and the core of its solution, in such a way that you can use this solution more than a million times, without ever doing it the same way."

The use of patterns has been adapted to Software Engineering, Data Modeling, Requirements Analysis, Conceptual Modeling, and Ontological Engineering, among other areas. In this section, we provide a first outline of modeling patterns for a framenet, according to the FrameNet guidelines.

As a rule, patterns emerge from solutions adopted in previous projects that have proven to be functional. Since existing framenets are basically based on Berkeley's FN, we don't have "previous projects" that can serve as a basis. Thus, the patterns proposed in this section are merely abstractions of detectable structures in the current network, whose applicability in future modeling needs to be evaluated. Therefore, the set of patterns is entirely open to modifications and extensions.

### Change-of-state

- Name: Change-of-state (Causative-inchoative alternation)
- Intention: The FN regularly separates causative frames from inchoative frames (The book, 2.1.1), having even defined two specific relations for this case (**Causative_of** and **Inchoative_of**). 
According to *The book* (6.1.6), the systematic relations between stative frames and the causative and inchoative frames that refer to them are recorded. 
As seen before, causative frames should inherit from **Transitive_action**, inchoative frames should inherit from **Event**, and stative frames should inherit from **State** or **Gradable_attribute**. 
Most of these relations have not yet been implemented.

- Diagrams

```mermaid
classDiagram 
class `Transitive_action` 
class `Event` 
class `Causative_frame` 
class `Stative_frame` 
class `Inchoative_frame` 
`Transitive_action` <-- `Causative_frame` 
`State or Gradable_attributes` <|-- `Stative_frame` 
`Event` <|-- `Inchoative_frame` 
`Causative_frame` --> `Inchoative_frame`: causative_of
`Causative_frame` --> `Stative_frame`: causative_of  
`Inchoative_frame` --> `Stative_frame`: inchoative_of
```

- Example:
  - Stative: the door is open.
  - Inchoative: the door opened.
  - Causative: the boy opened the door.

Each use of the verb should be represented by different LU, which should be related to each other by lexical relations (causative_of/inchoative_of):

```mermaid
graph LR
abrir_LEMMA -- has_lu --> abrir_EVENT_LU1
abrir_LEMMA -- has_lu --> abrir_EVENT_LU2
aberta_LEMMA -- has_lu --> aberta_EVENT_LU3
menino_AGENT -- causes --> abrir_CAUSATIVE_FRAME
porta_THEME -- attends --> abrir_INCHOATIVE_FRAME
abrir_EVENT_LU1 -- evokes --> abrir_CAUSATIVE_FRAME
abrir_EVENT_LU2 -- evokes --> abrir_INCHOATIVE_FRAME
aberta_EVENT_LU3 -- evokes --> abrir_STATIVE_FRAME
abrir_CAUSATIVE_FRAME -- causative_of --> abrir_INCHOATIVE_FRAME
abrir_INCHOATIVE_FRAME -- inchoative_of --> abrir_STATIVE_FRAME

```

### Perspective
- Name: Perspective
- Intention: The implementation of this pattern (through the **Perspective_on** relation) indicates the presence of at least two different viewpoints, in relation to a neutral frame. 
Specifically, frames that have FEs related by **Excludes** have a separate viewpoint associated with each choice of an excluded FE.

- Conditions: the neutral frame is normally *Non-lexical* and *Non-perspectivized*.

- Diagram

```mermaid
classDiagram 
class `Frame_A:Neutral` 
class `Frame_B:Perspectivized` 
class `Frame_C:Perspectivized` 
`Frame_A:Neutral` --> `Frame_B:Perspectivized`: perspective_on 
`Frame_A:Neutral` --> `Frame_C:Perspectivized`: perspective_on
```
### Experience
- Name: Experience
- Intention: Implementation of this pattern (through the **Causative_of** relation) indicates an experience that change conditions of a entity.

- Diagram

```mermaid
classDiagram 
class `Frame_A:Experience` 
class `Frame_B:State` 
class `Frame_C:Attribute` 
`Frame_A:Experience` --> `Frame_B:State`: causative_of 
`Frame_A:Experience` --> `Frame_C:Attribute`: causative_of
```

### EventState
- Name: EventState
- Intention: To explicitly separate LUs associated with events (in eventive frames) from LUs related to states that precede or follow these events (in stative frames). 
While the eventive frame focuses on the *occurrence of the event* and its participants, the stative frame focuses on the *condition of an entity* participating in the event.

- Diagram

```mermaid
classDiagram

class `Frame_A:Eventive`

class `Frame_B:Stative`

`Frame_B:Stative` --> `Frame_A:Eventive`: using
```
### Agentive
- Name: Agentive_noun
- Intention: Remove the semantic type *Biframal_LU::Agentive_noun* by creating (or using) a frame related to the agentive name. 
The LU representing an "agent" must evoke a entity frame. 
The FE associated with the agent in the eventive frame must be associated with the entity frame via a FE-F relation if all LUs in the entity frame can be associated with the FE. 
Or through the LU-FE relation, in the case of a very generic entity frame, where only some LUs could be *filler* for the FE.

- Diagram

```mermaid
classDiagram 
class `Frame_A:Eventive` 
class `Frame_B:Entity` 
`Frame_A:Eventive` --> `Frame_B:Entity`: using
```
### Participant
- Name: Participating_entity
- Intention: Remove the semantic type *Biframal_LU::Participating_entity* by creating (or using) a frame related to the participant's name in the event.
  The LU representing an "participant" must evoke a entity frame.
  The FE associated with the participant in the eventive frame must be associated with the entity frame via a FE-F relation if all LUs in the entity frame can be associated with the FE.
  Or through the LU-FE relation, in the case of a very generic entity frame, where only some LUs could be *filler* for the FE.

- Diagram

```mermaid
classDiagram 
class `Frame_A:Eventive` 
class `Frame_B:Entity` 
`Frame_A:Eventive` --> `Frame_B:Entity`: using
```

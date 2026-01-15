---
title: Frame Relations
order: 11
description: Frame relations
---

# Frame relations (The book, 6.1)

## Inheritance
- The basic idea for this relationship is that each semantic fact about the parent frame must correspond to an equally specific (or more specific) fact about the child frame.

- Conditions associated with the inheritance relationship:

- A child frame can have FEs that are not listed in the parent frame.

- A child frame generally does not mention the parent frame FEs that have the type "Core-unexpressed".

- Two FEs from a parent frame can be mapped to one FE of the child frame, with all properties of both FEs being imposed on the single FE of the child frame.

- There are divergences in CoreSet or Excludes relationships.

- The Frame-Frame relationships that the parent frame participates in are implicit in the child frame.

## Perspective_on
- The use of this relation indicates the presence of at least 2 different viewpoints on the neutral frame.

- The neutral frame is normally non-lexical and non-perspectivized.

- A neutral frame usually has at least 2 perspectiveized frames, but in some cases the words of the neutral frame are consistent with multiple different viewpoints, while the perspectiveized frame is consistent with only one.

- It is quite common for a frame to inherit from a parent frame and be a perspective of another parent frame.

## Using
- FrameNet uses the Using relation almost exclusively for cases where a part of the scene evoked by the child frame refers to the parent frame.

## Subframe
- Some frames are complex, in the sense that they refer to sequences of states and transitions, each of which can be described separately as a frame. Separate frames (called *subframes*) are related to the complex frame through the Subframe relationship. In such cases, FEs of the complex frame can be connected to the FEs of the subframes, although not all FEs will have a relationship.

## Precedes
- This relationship occurs only between two subframes of a complex frame. It specifies the sequence of states and events that define a certain state of affairs.

- This is the only relationship in which loops are allowed.

## Causative of and Inchoative of
- These relationships record the systematic association between stationary frames and the inchoative and causative frames that refer to the state.

- Causative frames should inherit from Transitive_action, inchoative frames should inherit from Event, and stationary frames should inherit from State or Gradable_attribute.

## Relationships Implemented in FNBr

### FE-F
The basic idea of the FE-F relationship is that the *filler* of an FE can be a LU evoked by the associated frame. In the case of entity frames, where the LU that evokes the frame would be incorporated into the Core FE, this relationship can also be expressed – at parsing time – as a FE-FE relationship.

### TQR (Ternary Qualia Relation)
The qualia structure of an LU is represented by relationships with other LUs (as in SIMPLE). The set of relationships must be open (i.e., it must be expandable as the FNBr modeling progresses).

"Background Frames" are used as the basis for the qualia relationships; these frames provide both the "meaning" and the "structure" of each TQR relationship.

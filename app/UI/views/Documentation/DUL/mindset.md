---
title: The cognitive-epistemological mindset
order: 2
description: The cognitive-epistemological mindset
---

# The Cognitive-Epistemological Mindset

## The Central Insight: Descriptions and Situations

The most distinctive feature of DUL is the **D&S (Descriptions and Situations) pattern**, which embodies a fundamentally **epistemological** approach to ontology.

### Core Distinction:
- **Description**: A conceptual schema, theory, or frame that defines concepts and their relationships
- **Situation**: A view on reality that satisfies (is consistent with) a description

**Example:**
- **Description**: "A recipe for chocolate cake" (defines ingredients, procedures, roles like 'baker', tasks like 'mixing')
- **Situation**: "My baking session this morning" (satisfies the recipe: I played the baker role, I executed the mixing task, etc.)

This separation allows us to model:
1. **The same reality under different theories**: An avalanche can be framed as a natural process (physics) or as a crime scene (law)
2. **Theories without instances**: We can model the recipe even if no one has ever baked that cake
3. **Multiple interpretations**: The same physical event can satisfy different descriptions (diagnosis, narrative, legal norm)

## The Epistemological Commitment

DUL recognizes that **knowledge is always mediated by conceptual frameworks**. This has profound implications:

### No Direct Access to Reality
We never model "raw objects" or "raw events"—we model:
- Objects-as-classified (a physical entity as a "car", as a "weapon", as "evidence")
- Events-as-interpreted (a physical process as an "action", as an "accident", as a "crime")

DUL makes this explicit through:
- **classifies/isClassifiedBy**: Concepts classify entities within contexts
- **satisfies/isSatisfiedBy**: Situations satisfy descriptions that provide interpretation

### Context Dependence
The "same" entity can have different identities in different contexts:
- An old cradle is a **baby furniture** in a museum (satisfying a historical design description)
- The same cradle is a **flower pot** in a garden (satisfying a home decoration description)

Both are valid, non-contradictory views—they are different Situations that include the same physical object.

### Observer Relativity
DUL acknowledges that **observers create contexts**:
- A Situation is "a view created by an observer on the basis of a frame"
- Different observers (or the same observer at different times) can create different situations from the same data

## The Social Construction of Reality

DUL distinguishes **PhysicalObject** and **SocialObject** as disjoint:

###  PhysicalObject
- Has spatial location and (typically) mass
- Exists independently of human conceptualization
- Examples: rocks, trees, human bodies, buildings

###  SocialObject
- Exists **only through communication** and shared understanding
- Must be **expressed by InformationObject**s (speech, writing, gestures)
- Examples: laws, roles, organizations, concepts, money, marriages

**Key insight**: Social reality is **ontologically dependent** on information:
- A marriage exists because it is expressed in legal documents and social practices
- A role (e.g., "professor") exists because it is defined in institutional descriptions
- An organization exists because its structure is documented and communicated

This captures the **constitutive role of language and communication** in creating social facts (echoing John Searle's philosophy of social ontology).

## Information and Meaning

DUL provides a sophisticated model of **information** that distinguishes:

### InformationObject (Abstract)
The information content itself, independent of physical realization:
- The 3rd Gymnopedie by Satie (the musical composition)
- The text of the Italian Constitution (the legal content)
- The concept "dog" (the mental/social construct)

### InformationRealization (Concrete)
Physical or event-based realizations:
- A printed music sheet, a piano performance, a recording
- A specific book copy, an oral recitation
- Utterances of the word "dog", written tokens

**Relation**: `realizes` connects InformationRealization to InformationObject

### expresses Relation
InformationObjects **express** SocialObjects (their "meanings"):
- The term "professor" expresses the Role concept 'Professor'
- The recipe text expresses the Plan for making cake
- The Constitution text expresses the legal Norm system

This three-level model (Physical Realization → Abstract Information → Social Meaning) provides a foundation for:
- Semantic web and knowledge representation
- Cultural heritage documentation
- Legal and institutional modeling
- Discourse and narrative analysis

## The Cognitive Premise: Concepts and Classification

DUL models the **cognitive act of categorization**:

### Concept
A SocialObject that **classifies** entities:
- Defined within a Description
- Can be reused across descriptions
- Acts as an intensional category (the "idea" of something)

### Classification (Time-Indexed)
A special Situation that captures:
- What concept classifies what entity
- At what time interval
- Within what larger context

**Example**: "My old cradle **is classified as** a flower pot **in June 2024** (within the situation of my garden decoration project)"

This allows:
- **Dynamic classification**: Entities can be reclassified over time
- **Context-dependent classification**: Different classifications in different situations
- **Multiple simultaneous classifications**: The cradle is still a cradle (historically) even while functioning as a flower pot

## The Quality-Region Pattern

DUL reifies attributes through the **Quality-Region** pattern:

### Quality
An individual **aspect** of an entity:
- Cannot exist without the entity (dependent)
- The specific yellowness of Dmitri's skin
- The specific height of the Eiffel Tower

### Region
An abstract value in a dimensional space:
- Independent of particular entities
- "180 cm" in the space of possible heights
- "Yellow" in color space

**Relation**: `hasRegion` connects Quality to Region (the quality's value)

**Why this complexity?**
1. **Observation vs. reality**: The same quality (Dmitri's skin color) can be measured differently (RGB values, wavelengths, color names)
2. **Temporal change**: Qualities persist even as their regions change (Dmitri's skin can tan)
3. **Parameter-based constraints**: Concepts can constrain regions (Role 'Driver' requires Parameter 'MinimumAge' > 16)

## Epistemological vs. Ontological Modeling

DUL allows modeling at two levels:

### Ontological Level
What exists in the domain:
- Physical objects, events, their parts and qualities
- Mereological (part-whole) relations
- Spatial and temporal location

### Epistemological Level
How we conceptualize and organize what exists:
- Descriptions, concepts, roles, tasks
- Situations, classifications, contexts
- Information objects expressing knowledge

Most ontologies focus only on the ontological level. DUL's innovation is **integrating both**, acknowledging that:
- Domain models are not "reality" but "conceptualizations of reality"
- Different conceptualizations can coexist (pluralism)
- The act of modeling itself involves descriptions and situations

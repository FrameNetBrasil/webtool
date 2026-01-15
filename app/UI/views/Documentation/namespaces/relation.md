---
title: Relational
order: 8
description: Relational
---

# Relational Namespace

## Core Definition

Relational frames foreground **relations between entities**, emphasizing the connections, associations, and dependencies that hold between two or more participants.
These frames center on **dyadic or polyadic structures** where the relationship itself is the primary semantic content, rather than properties of individual entities or events occurring to them.

**Theoretical foundation**: Relational frames encode the basic template `RELATION(x, y)` or `RELATION(x, y, z, ...)`, where entities stand in specific relationships to each other. Unlike monadic states (which describe properties of single entities) or events (which describe changes), relations describe **how entities are connected or associated**.

**Key characteristics**:
- **Multi-participant structure**: Minimally two participants (dyadic), sometimes more (polyadic)
- **Relational predicate**: The relation itself is profiled, not the entities
- **Asymmetric or symmetric**: Relations may distinguish participant roles or treat them equivalently
- **Static/stative**: Relations typically hold over time without internal change
- **No inherent endpoint**: Relations obtain without natural termination (atelic)
- **Non-agentive**: Relations hold between entities without volitional control

**Vendler aspectual class**: States (like other statives, but inherently relational)

**Philosophical grounding**: Relations connect to **relational properties** in metaphysics - properties that entities have in virtue of standing in relations to other entities. This includes possession, kinship, spatial configuration, part-whole structure, and abstract connections.

## Relational Types: A Comprehensive Taxonomy

Relational frames exhibit diverse types based on what kind of connection or association they encode.

### Possession Relations

**Definition**: Relations where one entity (possessor) has control over, ownership of, or association with another entity (possessed).

**Semantic template**: `POSSESS(Possessor, Possessed)`

**Portuguese realization**:
- Primary verb: *ter* (have)
- Emphatic: *possuir* (possess/own)
- Copular: *ser de* (belong to)

#### Alienable Possession (Transferable)

**Definition**: Possession of entities that can be transferred, acquired, or lost without changing the possessor's essential nature.

**Examples**:
```
João tem um carro (João has a car)
- Possessor: João
- Possessed: carro (car)
- Alienable: car can be sold/transferred

Maria possui uma casa (Maria owns a house)
- Emphatic possession verb *possuir*
- Alienable ownership

Pedro tem muito dinheiro (Pedro has much money)
- Mass noun possessed (money)
- Highly alienable
```

**Properties**:
- Can be acquired or lost
- Typically material objects, money, property
- Verb: *ter* (have) or *possuir* (own/possess)
- Can be made explicit with copula: *Este livro é meu* (This book is mine)

**Copular expression**:
```
Este livro é meu (This book is mine)
A casa é de João (The house is João's)
- Uses *ser de* to express belonging
- Emphasizes ownership/belonging relation
```

#### Inalienable Possession (Non-transferable)

**Definition**: Possession of entities that cannot be transferred and are inherently associated with the possessor (body parts, kinship, inherent properties).

**Examples**:
```
João tem dois irmãos (João has two siblings)
- Kinship: inalienable relation
- Cannot transfer siblings

Maria tem cabelo preto (Maria has black hair)
- Body part: inalienable
- Part of person's physical makeup

A casa tem três quartos (The house has three rooms)
- Architectural part: integral component
- Cannot transfer rooms separately from house
```

**Properties**:
- Cannot be transferred or sold
- Typically body parts, kinship, inherent components
- Verb: *ter* (have)
- Reflects essential structure or inherent association

#### Possession Participant Structure

**Asymmetric structure**: Possessor and possessed have distinct roles

**Possessor**:
- Typically animate (for alienable possession)
- Can be inanimate (for part-whole: house has rooms)
- Controller or bearer of possessed entity
- Syntactic subject

**Possessed**:
- Typically object or entity under control
- Can be abstract (ideas, rights)
- Syntactic direct object (with *ter*) or complement (with *ser de*)

**Example analysis**:
```
João tem um carro
[POSSESS(João[Possessor], carro[Possessed])]

Structure:
- João: Animate possessor, controller
- carro: Alienable possessed object
- Asymmetric: João ≠ carro in role
```

### Kinship Relations

**Definition**: Relations based on family connections, including biological descent, marriage, and extended family ties.

**Semantic template**: `KINSHIP(Relatum₁, KinshipType, Relatum₂)`

**Portuguese realization**:
- Primary copula: *ser* (be)
- Indicates permanent/essential family relation

**Examples**:
```
João é pai de Maria (João is Maria's father)
- Relatum₁: João (father)
- Relatum₂: Maria (child)
- Kinship type: parent-child

Pedro é irmão de Ana (Pedro is Ana's brother)
- Relatum₁: Pedro
- Relatum₂: Ana
- Kinship type: sibling

Maria é avó de João (Maria is João's grandmother)
- Relatum₁: Maria (grandmother)
- Relatum₂: João (grandchild)
- Kinship type: grandparent-grandchild

Ana é casada com Pedro (Ana is married to Pedro)
- Relatum₁: Ana (spouse)
- Relatum₂: Pedro (spouse)
- Kinship type: marriage
```

#### Properties of Kinship Relations

**1. Permanence**: Marked with *ser* (permanent relation)
```
✓ João é pai de Maria (João is Maria's father - permanent)
✗ *João está pai de Maria (ungrammatical - cannot be temporary)
```

**2. Symmetry patterns**:

**Symmetric kinship** (same relation both directions):
```
Pedro é irmão de Ana ↔ Ana é irmã de Pedro
(Pedro is Ana's brother ↔ Ana is Pedro's sister)
- Same kinship type (sibling), gender-marked
- Symmetric structure

João é casado com Maria ↔ Maria é casada com João
(João is married to Maria ↔ Maria is married to João)
- Marriage: symmetric relation
```

**Asymmetric kinship** (different relations, complementary):
```
João é pai de Maria ↔ Maria é filha de João
(João is Maria's father ↔ Maria is João's daughter)
- Complementary pair: father ↔ daughter
- Asymmetric: different roles

Maria é avó de Pedro ↔ Pedro é neto de Maria
(Maria is Pedro's grandmother ↔ Pedro is Maria's grandson)
- Complementary pair: grandmother ↔ grandson
```

**3. Transitivity** (some kinship relations):
```
If: João é pai de Maria AND Maria é mãe de Pedro
Then: João é avô de Pedro
(Transitivity through generations)
```

**4. Types of kinship**:
- **Consanguineal** (blood relation): *pai* (father), *mãe* (mother), *irmão* (brother)
- **Affinal** (marriage): *marido* (husband), *esposa* (wife), *sogro* (father-in-law)
- **Extended**: *tio* (uncle), *primo* (cousin), *sobrinho* (nephew)

### Social and Professional Relations

**Definition**: Relations based on social roles, professional hierarchies, friendships, and institutional connections.

**Semantic template**: `SOCIAL_RELATION(Relatum₁, RelationType, Relatum₂)`

**Portuguese realization**: *ser* (be) for roles, *ter* (have) for some relations

**Examples**:

**Professional/hierarchical**:
```
João é professor de Maria (João is Maria's teacher)
- Professional role relation
- Hierarchical: teacher > student

O diretor é chefe dos funcionários (The director is the employees' boss)
- Institutional hierarchy
- Boss > employees

Maria é colega de João (Maria is João's colleague)
- Professional peer relation
- Symmetric (colleagues)
```

**Social/friendship**:
```
Pedro é amigo de Ana (Pedro is Ana's friend)
- Social bond: friendship
- Typically symmetric

João é conhecido de Maria (João is an acquaintance of Maria)
- Weak social tie
- Symmetric
```

#### Properties of Social/Professional Relations

**1. Role-based structure**:
- Define participants by their social/professional roles
- Often contextual (teacher-student relation holds in educational context)

**2. Symmetry variation**:

**Symmetric social relations**:
```
Pedro é amigo de Ana ↔ Ana é amiga de Pedro
(Friendship is symmetric)

João é colega de Maria ↔ Maria é colega de João
(Colleague relation is symmetric)
```

**Asymmetric professional relations**:
```
João é professor de Maria ≠ Maria é professora de João
(Teacher-student is asymmetric)

O diretor é chefe de João ≠ João é chefe do diretor
(Boss-subordinate is asymmetric)
```

**3. Temporality**:
- Some social/professional relations can change
- Use *ser* for current/general relation
- Context determines if temporary or permanent

### Part-Whole Relations (Meronymy)

**Definition**: Relations where one entity (part) is a component, member, or portion of another entity (whole).

**Semantic template**: `PART_OF(Part, Whole)` or `HAS_PART(Whole, Part)`

**Portuguese realization**:
- *ser parte de* (be part of)
- *pertencer a* (belong to)
- *ter* (have - from whole's perspective)

**Examples**:
```
A roda é parte do carro (The wheel is part of the car)
- Part: roda (wheel)
- Whole: carro (car)
- Component-integral object

O dedo pertence à mão (The finger belongs to the hand)
- Part: dedo (finger)
- Whole: mão (hand)
- Body part meronymy

A cidade tem muitos bairros (The city has many neighborhoods)
- Whole: cidade (city)
- Parts: bairros (neighborhoods)
- Place-area relation

O livro tem dez capítulos (The book has ten chapters)
- Whole: livro (book)
- Parts: capítulos (chapters)
- Structural components
```

#### Types of Part-Whole Relations

Following **Winston et al. (1987)**, part-whole relations exhibit several distinct types:

**a) Component-Integral Object**:
```
A roda é parte do carro (The wheel is part of the car)
O motor é componente do carro (The motor is a component of the car)
- Part is functional component of whole
- Removal affects whole's function
- Integral relationship
```

**b) Member-Collection**:
```
A árvore é parte da floresta (The tree is part of the forest)
João é membro do grupo (João is a member of the group)
- Individual member of collection
- Whole is collection of similar members
- Member-collective relation
```

**c) Portion-Mass**:
```
A fatia é parte do bolo (The slice is part of the cake)
Um copo de água (A glass of water - portion of water)
- Portion taken from mass
- Whole is homogeneous mass
- Quantitative extraction
```

**d) Stuff-Object**:
```
A madeira é o material da mesa (Wood is the material of the table)
A mesa é de madeira (The table is of wood)
- Material constitution
- Stuff composes object
- Material-object relation
```

**e) Feature-Activity**:
```
Pagar é parte de comprar (Paying is part of buying)
A assinatura é parte do contrato (Signing is part of the contract)
- Sub-activity of larger activity
- Feature of complex event
- Activity decomposition
```

**f) Place-Area**:
```
O bairro fica na cidade (The neighborhood is in the city)
A sala é parte da casa (The room is part of the house)
- Spatial part-whole
- Location-within-location
- Geographic containment
```

#### Properties of Part-Whole Relations

**1. Asymmetry**: Part-whole relations are inherently asymmetric
```
A roda é parte do carro (The wheel is part of the car)
≠ O carro é parte da roda (The car is part of the wheel)
```

**2. Transitivity**: Part-whole relations are transitive
```
If: O dedo é parte da mão AND A mão é parte do corpo
Then: O dedo é parte do corpo
(Finger is part of hand, hand is part of body → finger is part of body)
```

**3. Proper parthood**: Part is not identical to whole
```
A roda ≠ o carro (The wheel is not the car)
- Part is distinct from whole
- Whole has other parts besides the focal part
```

**4. Essential vs. optional parts**:
- **Essential parts**: Remove them, whole ceases to function/exist properly
  - *O motor do carro* (car's motor - essential)
- **Optional parts**: Can be removed without destroying whole
  - *O espelho retrovisor* (rearview mirror - less essential)

### Quantitative and Measure Relations

**Definition**: Relations expressing measurements, quantities, dimensions, ages, values, or costs associated with entities.

**Semantic template**: `MEASURE(Entity, Dimension, Value)`

**Portuguese realization**:
- *ter* (have) for age, dimensions
- *medir* (measure) for dimensions
- *custar* (cost) for value
- *pesar* (weigh) for weight

**Examples**:

**Age relations**:
```
João tem 30 anos (João is 30 years old / João has 30 years)
- Entity: João
- Dimension: age
- Value: 30 anos (30 years)

A árvore tem 100 anos (The tree is 100 years old)
```

**Dimension relations**:
```
A mesa tem 2 metros de comprimento (The table is 2 meters long / has 2 meters of length)
- Entity: mesa (table)
- Dimension: comprimento (length)
- Value: 2 metros

A sala mede 20 metros quadrados (The room measures 20 square meters)
- Verb *medir* (measure)
- Dimension: area
- Value: 20 m²

A montanha tem 3000 metros de altura (The mountain is 3000 meters high)
- Dimension: altura (height)
- Value: 3000 metros
```

**Value/cost relations**:
```
O livro custa 50 reais (The book costs 50 reais)
- Entity: livro (book)
- Dimension: monetary value
- Value: 50 reais

O carro vale 50 mil reais (The car is worth 50 thousand reais)
- Verb *valer* (be worth)
```

**Weight relations**:
```
O pacote pesa 5 quilos (The package weighs 5 kilos)
- Verb *pesar* (weigh)
- Dimension: weight
- Value: 5 kg
```

#### Properties of Quantitative Relations

**1. Measurement structure**:
- Entity measured (syntactic subject)
- Dimension measured (type of measurement)
- Value/quantity (numerical value + unit)

**2. Verb variation by dimension**:
- Age, length, area: *ter* (have)
- General measurement: *medir* (measure)
- Cost: *custar* (cost)
- Worth: *valer* (be worth)
- Weight: *pesar* (weigh)

**3. Non-volitional**: Entities don't control their measurements
```
✗ *João decidiu ter 30 anos (João decided to be 30 years old - odd)
- Measurements are not volitional
```

### Abstract and Attributive Relations

**Definition**: Relations between abstract entities, including logical implications, semantic connections, and abstract attributions.

**Semantic template**: `ABSTRACT_RELATION(Entity₁, RelationType, Entity₂)`

**Portuguese realization**: *ter* (have), *implicar* (imply), various relational verbs

**Examples**:

**Implication relations**:
```
A teoria tem implicações (The theory has implications)
- Theory: source entity
- Implicações: consequence entities
- Logical/conceptual connection

Se p, então q (If p, then q)
- Conditional relation
- Logical implication
```

**Solution relations**:
```
O problema tem solução (The problem has a solution)
- Problem-solution pair
- Abstract pairing relation

A questão exige resposta (The question requires an answer)
- Question-answer relation
```

**Meaning relations**:
```
A história tem sentido (The story has meaning)
- Story: entity with semantic content
- Sentido: meaning attributed

A palavra significa "casa" (The word means "house")
- Semantic relation between sign and referent
```

**Causation relations** (abstract):
```
A teoria explica o fenômeno (The theory explains the phenomenon)
- Explanatory relation
- Abstract causation

O argumento justifica a conclusão (The argument justifies the conclusion)
- Justificatory relation
```

**Dependency relations**:
```
O resultado depende dos dados (The result depends on the data)
- Dependency: result contingent on data
- Abstract dependence relation
```

## Participant Structure in Relational Frames

Relational frames are characterized by their **multi-participant structure** - they inherently involve two or more entities in relation.

### Dyadic Relations (Two Participants)

**Definition**: Relations involving exactly two participants standing in relation R to each other.

**Structure**: `RELATION(x, y)` where x and y are the two relatums

**Most common type**: The majority of relational frames are dyadic

**Examples by type**:

**Possession** (dyadic):
```
João tem um carro
- Participant₁: João (possessor)
- Participant₂: carro (possessed)
- Relation: possession
```

**Kinship** (dyadic):
```
Maria é mãe de João
- Participant₁: Maria (mother)
- Participant₂: João (child)
- Relation: motherhood
```

**Social** (dyadic):
```
Pedro é amigo de Ana
- Participant₁: Pedro (friend)
- Participant₂: Ana (friend)
- Relation: friendship
```

**Part-whole** (dyadic):
```
A roda é parte do carro
- Participant₁: roda (part)
- Participant₂: carro (whole)
- Relation: part-of
```

### Polyadic Relations (More than Two Participants)

**Definition**: Relations involving three or more participants.

**Structure**: `RELATION(x, y, z, ...)` where multiple entities stand in relation

**Less common than dyadic**: Most relations reduce to dyadic structure

**Examples**:

**Ternary kinship**:
```
João apresentou Maria a Pedro (João introduced Maria to Pedro)
- Participant₁: João (introducer)
- Participant₂: Maria (introduced)
- Participant₃: Pedro (introduced to)
- Relation: introduction (ternary)
```

**Comparative relations** (ternary):
```
João é mais alto que Maria em relação a Pedro
(João is taller than Maria relative to Pedro)
- Participant₁: João (compared entity)
- Participant₂: Maria (comparison standard)
- Participant₃: Pedro (reference point)
- Relation: relative comparison
```

**Exchange relations**:
```
João trocou o livro pelo disco com Maria
(João exchanged the book for the record with Maria)
- Participant₁: João (exchanger)
- Participant₂: livro (given object)
- Participant₃: disco (received object)
- Participant₄: Maria (exchange partner)
- Relation: exchange
```

**Note**: Many apparent polyadic relations can be decomposed into multiple dyadic relations.

## Symmetry, Asymmetry, and Reflexivity

Relational frames exhibit important **logical properties** regarding how participants relate to each other.

### Symmetric Relations

**Definition**: Relations where `R(x,y) → R(y,x)` - if x relates to y, then y relates to x in the same way.

**Properties**:
- Participants have equivalent roles
- Relation holds bidirectionally
- Cannot distinguish "direction" of relation

**Examples**:

**Friendship**:
```
João é amigo de Pedro ↔ Pedro é amigo de João
(João is friends with Pedro ↔ Pedro is friends with João)
- Symmetric: friendship goes both ways
- No asymmetry in roles
```

**Similarity/resemblance**:
```
Maria parece Ana ↔ Ana parece Maria
(Maria resembles Ana ↔ Ana resembles Maria)
- Symmetric: resemblance is bidirectional
```

**Equality**:
```
João tem a mesma idade que Maria ↔ Maria tem a mesma idade que João
(João is the same age as Maria ↔ Maria is the same age as João)
- Symmetric: equality is bidirectional
```

**Adjacency**:
```
A casa fica ao lado da escola ↔ A escola fica ao lado da casa
(The house is next to the school ↔ The school is next to the house)
- Symmetric: adjacency works both ways
```

**Marriage** (symmetric):
```
João é casado com Maria ↔ Maria é casada com João
(João is married to Maria ↔ Maria is married to João)
- Symmetric: marriage relation is mutual
```

### Asymmetric Relations

**Definition**: Relations where `R(x,y)` does not imply `R(y,x)` - direction matters, and roles are distinct.

**Properties**:
- Participants have distinct roles
- Relation is directional
- Reversing participants changes meaning or truth

**Examples**:

**Possession**:
```
João tem um carro (João has a car)
≠ O carro tem João (The car has João - nonsensical)
- Asymmetric: possessor ≠ possessed
```

**Parent-child kinship**:
```
João é pai de Maria (João is Maria's father)
≠ Maria é pai de João (Maria is João's father - false)
- Asymmetric: parent ≠ child
- (Though has complementary: Maria é filha de João)
```

**Part-whole**:
```
A roda é parte do carro (The wheel is part of the car)
≠ O carro é parte da roda (The car is part of the wheel - false)
- Asymmetric: part ≠ whole
```

**Teacher-student**:
```
João é professor de Maria (João is Maria's teacher)
≠ Maria é professora de João (Maria is João's teacher - false in same context)
- Asymmetric: different roles
```

**Before/after** (temporal):
```
Segunda vem antes de terça (Monday comes before Tuesday)
≠ Terça vem antes de segunda (Tuesday comes before Monday - false)
- Asymmetric: temporal order
```

### Complementary Relations (Converse Relations)

**Definition**: Asymmetric relations that come in pairs, where `R₁(x,y) ↔ R₂(y,x)` - reversing participants requires changing the relation to its converse.

**Properties**:
- Two distinct but related predicates
- Describe same situation from different perspectives
- Participant role reversal requires predicate change

**Examples**:

**Kinship converses**:
```
João é pai de Maria ↔ Maria é filha de João
(Father-of ↔ daughter-of converse pair)

Maria é avó de Pedro ↔ Pedro é neto de Maria
(Grandmother-of ↔ grandson-of converse pair)
```

**Commercial converses**:
```
João comprou o livro de Maria ↔ Maria vendeu o livro para João
(Buy-from ↔ sell-to converse pair)
```

**Locational converses**:
```
São Paulo fica ao norte de Curitiba ↔ Curitiba fica ao sul de São Paulo
(North-of ↔ south-of converse pair)

A casa fica acima da escola ↔ A escola fica abaixo da casa
(Above ↔ below converse pair)
```

**Ownership converses**:
```
João possui esta casa ↔ Esta casa pertence a João
(Own ↔ belong-to converse pair)
```

### Reflexive Relations

**Definition**: Relations where an entity can stand in relation to itself: `R(x, x)` is possible.

**Examples**:

**Self-resemblance**:
```
João parece consigo mesmo (João resembles himself)
- Reflexive: entity can resemble itself
- Usually trivial/tautological
```

**Self-friendship** (usually odd):
```
? João é amigo de si mesmo (João is friends with himself)
- Pragmatically odd but logically possible
```

**Most relations are irreflexive**: Cannot hold between entity and itself
```
✗ João é pai de João (João is João's father - impossible)
✗ A roda é parte da roda (The wheel is part of the wheel - nonsensical)
```

### Transitive Relations

**Definition**: Relations where `R(x,y) AND R(y,z) → R(x,z)` - if x relates to y, and y relates to z, then x relates to z.

**Examples**:

**Part-whole** (transitive):
```
If: O dedo é parte da mão (Finger is part of hand)
AND: A mão é parte do corpo (Hand is part of body)
Then: O dedo é parte do corpo (Finger is part of body)
```

**Kinship** (some types transitive):
```
If: João é pai de Maria (João is Maria's father)
AND: Maria é mãe de Pedro (Maria is Pedro's mother)
Then: João é avô de Pedro (João is Pedro's grandfather)
- Transitivity with relation change (father → grandfather)
```

**Before/after** (transitive):
```
If: Segunda vem antes de terça (Monday before Tuesday)
AND: Terça vem antes de quarta (Tuesday before Wednesday)
Then: Segunda vem antes de quarta (Monday before Wednesday)
```

**Friendship** (not transitive):
```
João é amigo de Maria (João is friends with Maria)
Maria é amiga de Pedro (Maria is friends with Pedro)
NOT necessarily: João é amigo de Pedro (João is friends with Pedro)
- Friendship is not transitive
```

## Negation in Relational Frames

Relational frames exhibit distinctive **negation patterns** that differ from property negation and event negation.

### Relation Negation = Absence of Relation

**Principle**: Negating a relation asserts that the **relation does not hold** between the entities (absence of connection), not that an opposite relation holds.

**Contrast with property negation**:
- Property negation: *João não é alto* → João é baixo (opposite property)
- Relation negation: *João não tem carro* → absence of possession (not: João has non-car)

### Possession Negation

**Affirmative**:
```
João tem um carro (João has a car)
- Possession relation holds
```

**Negated**:
```
João não tem carro (João doesn't have a car)
→ Absence of possession relation
→ NOT: João possesses a non-car
→ Simply: No car is possessed by João
```

**Characteristic**: Possession negation asserts **lack of the possessed entity**, not possession of something else.

**More examples**:
```
Maria não tem irmãos (Maria doesn't have siblings)
→ Maria has zero siblings (absence of sibling relation)

Pedro não tem dinheiro (Pedro doesn't have money)
→ Pedro lacks money
```

### Kinship Negation

**Affirmative**:
```
João é pai de Maria (João is Maria's father)
- Kinship relation holds
```

**Negated**:
```
João não é pai de Maria (João is not Maria's father)
→ Absence of fatherhood relation
→ João and Maria do not stand in father-child relation
→ NOT: João has opposite kinship to Maria
```

**Characteristic**: Kinship negation asserts **absence of specific kinship tie**, not presence of different kinship.

**Contrast**:
```
PROPERTY: João não é brasileiro → João é de outra nacionalidade (has different nationality)
KINSHIP: João não é pai de Maria → absence of relation (not: João is something else to Maria)
```

### Locational Negation

**Affirmative**:
```
O livro está na mesa (The book is on the table)
- Locational relation holds: book located at table
```

**Negated**:
```
O livro não está na mesa (The book is not on the table)
→ Book is located elsewhere (alternative location)
→ Absence of specific location relation
→ Implies: book is in some other location
```

**Characteristic**: Locational negation asserts **absence of specific location** but implies entity is located somewhere else.

**Locational negation implies alternative location**:
```
If: O livro não está na mesa
Then: O livro está em algum outro lugar (The book is in some other place)
- Entities must be located somewhere
- Negation shifts location, doesn't eliminate it
```

### Social/Professional Relation Negation

**Examples**:
```
João não é amigo de Maria (João is not Maria's friend)
→ Absence of friendship relation
→ Not: João has opposite relation (enemy)
→ Simply: no friendship bond exists

Pedro não é professor de Ana (Pedro is not Ana's teacher)
→ Absence of teaching relation
→ Not: Pedro is student of Ana
→ Simply: no teacher-student relation
```

## Diagnostic Tests for Relational Frames

How do we identify relational frames and distinguish them from other types? Here are systematic diagnostic tests:

### Test 1: Minimal Arity (Number of Participants)

**Principle**: Relational frames require **at least two participants** (minimally dyadic).

**Test**: Can the predicate occur with only one participant?

**Relational (requires two)**:
```
✗ *João tem (João has - missing possessed object)
✗ *Maria é amiga (Maria is friends - missing friend)
✗ *A roda é parte (The wheel is part - missing whole)
- Ungrammatical or incomplete without second participant
```

**Non-relational (can have one)**:
```
✓ João corre (João runs - monadic, one participant)
✓ Maria é alta (Maria is tall - monadic property)
✓ Pedro chegou (Pedro arrived - monadic event)
```

**Diagnostic result**: If requires at least two participants → RELATIONAL

### Test 2: Participant Role Differentiation

**Principle**: Relational frames assign **distinct roles** to participants (even if symmetric, roles are marked).

**Test**: Can participants swap positions without changing meaning?

**Asymmetric relational** (roles differ):
```
João tem um carro (João has a car)
≠ O carro tem João (The car has João - role swap changes meaning)
- Roles: possessor ≠ possessed
```

**Symmetric relational** (roles equivalent but marked):
```
João é amigo de Pedro ≈ Pedro é amigo de João
(Same meaning, roles are equivalent)
- But participants still distinguished
- Relation requires both participants
```

**Non-relational**:
```
João corre (João runs)
- Only one participant role
- No role differentiation
```

**Diagnostic result**: If participants have distinct or marked roles → RELATIONAL

### Test 3: Cannot Be Satisfied by Single Entity Alone

**Principle**: Relational predicates cannot be true of an entity in isolation - they require relation to another entity.

**Test**: Is the predicate meaningful for entity in isolation?

**Relational (requires other entity)**:
```
✗ João tem ??? (João has - what? Needs possessed entity)
✗ Maria é amiga de ??? (Maria is friends with - whom? Needs friend)
✗ A roda é parte de ??? (The wheel is part of - what? Needs whole)
- Cannot be satisfied in isolation
```

**Non-relational (can be satisfied alone)**:
```
✓ João é alto (João is tall - property of João alone)
✓ Maria corre (Maria runs - event involving Maria alone)
✓ Pedro existe (Pedro exists - monadic)
```

**Diagnostic result**: If cannot be satisfied by single entity → RELATIONAL

### Test 4: Converse Test

**Principle**: Many relational frames have **converse predicates** that express the same relation from opposite perspective.

**Test**: Is there a converse predicate that expresses same situation?

**Relational (has converse)**:
```
João é pai de Maria ↔ Maria é filha de João
(Father-of ↔ daughter-of: converse pair)

João comprou de Maria ↔ Maria vendeu para João
(Buy-from ↔ sell-to: converse pair)
```

**Non-relational (no converse)**:
```
João corre (João runs - no converse)
Maria é alta (Maria is tall - no converse)
```

**Note**: Symmetric relations are their own converses
```
João é amigo de Maria ↔ Maria é amiga de João
(Friendship: self-converse)
```

**Diagnostic result**: If has converse predicate → RELATIONAL

### Test 5: Symmetry Test

**Principle**: Some relations are symmetric (hold in both directions), others asymmetric.

**Test**: Does `R(x,y)` imply `R(y,x)`?

**Symmetric relational**:
```
João é amigo de Pedro → Pedro é amigo de João
(Symmetric: friendship bidirectional)

A casa fica ao lado da escola → A escola fica ao lado da casa
(Symmetric: adjacency bidirectional)
```

**Asymmetric relational**:
```
João é pai de Maria ≠ Maria é pai de João
(Asymmetric: parenthood directional)

A roda é parte do carro ≠ O carro é parte da roda
(Asymmetric: part-of directional)
```

**Non-relational (test not applicable)**:
```
João corre (not relational - test doesn't apply)
```

**Diagnostic result**:
- If symmetric or asymmetric → RELATIONAL
- If test not applicable → NOT RELATIONAL

### Test 6: Transitivity Test

**Principle**: Some relations are transitive (chain through intermediate entity).

**Test**: If `R(x,y)` and `R(y,z)`, does `R(x,z)` follow?

**Transitive relational**:
```
If: O dedo é parte da mão AND A mão é parte do corpo
Then: O dedo é parte do corpo
- Transitive: part-of chains through intermediate
```

**Intransitive relational**:
```
If: João é amigo de Maria AND Maria é amiga de Pedro
NOT necessarily: João é amigo de Pedro
- Intransitive: friendship doesn't chain
```

**Non-relational (test not applicable)**:
```
João corre (not relational - test doesn't apply)
```

**Diagnostic result**:
- If transitive or intransitive → RELATIONAL
- If test not applicable → NOT RELATIONAL

### Test 7: Relational Noun Test

**Principle**: Relational predicates often have corresponding **relational nouns** that name the relation.

**Test**: Is there a noun form that names the relation?

**Relational (has relational noun)**:
```
João é amigo de Pedro → amizade (friendship - relational noun)
João é pai de Maria → paternidade (fatherhood/parenthood)
A roda é parte do carro → relação parte-todo (part-whole relation)
```

**Non-relational (no relational noun)**:
```
João corre → ? (no relational noun, "corrida" is event noun)
Maria é alta → ? (no relational noun, "altura" is property noun)
```

**Diagnostic result**: If has relational noun → RELATIONAL

## Boundary Cases and Overlaps

Relational frames can overlap or be confused with other namespace types. Here we clarify the boundaries.

### Relational vs. Stative Property

**Overlap**: Both are stative (no change), but relations involve multiple entities, properties involve single entity.

**Stative property** (monadic):
```
João é alto (João is tall)
- Property of João alone
- Monadic: one participant
- No relation to other entity required
```

**Relational** (dyadic):
```
João é mais alto que Maria (João is taller than Maria)
- Comparative: involves two entities
- Dyadic: two participants (João, Maria)
- Relation between entities
```

**Diagnostic**: Count participants
- One participant → Stative property
- Two or more participants → Relational

### Relational vs. Locational

**Overlap**: Locational states are a **subtype of relational** - they express spatial relations.

**Locational** (relational subtype):
```
O livro está na mesa (The book is on the table)
- Spatial relation between book and table
- Dyadic: Figure (livro) and Ground (mesa)
- IS relational (location is a type of relation)
```

**Classification**: Locational frames are relational, with specific spatial content.

**Distinction**:
- General relational: any type of relation (possession, kinship, etc.)
- Locational: specifically spatial relation (location)

**Recommendation**: Tag locational as both **relational** and **locational** (subcategory)

### Relational vs. Eventive/Process

**Clear distinction**: Dynamics

**Relational** (static):
```
João tem um carro (João has a car)
- Static relation: possession holds over time
- No change or process
- Stative
```

**Eventive** (dynamic):
```
João comprou um carro (João bought a car)
- Dynamic event: acquisition process
- Change occurs: João goes from not-having to having car
- Process with temporal structure
```

**Diagnostic**: Change test
- No change, holds over time → Relational
- Change or process → Eventive

**Note**: Some verbs can be relational or eventive depending on aspect:
```
RELATIONAL: João tem carro (João has a car - stative possession)
EVENTIVE: João está comprando carro (João is buying a car - dynamic acquisition)
```

### Relational vs. Causative

**Distinction**: Causatives involve causation (Agent causes Event), relations involve connections between entities.

**Causative**:
```
João quebrou o vaso (João broke the vase)
- Agent (João) causes event (breaking)
- Dynamic: event occurs
- Causal structure
```

**Relational**:
```
João tem um vaso (João has a vase)
- Possessor (João) related to possessed (vaso)
- Static: relation holds
- No causation
```

**Diagnostic**: Causation presence
- Causation central → Causative
- Connection/association → Relational

### Experiencer-Stimulus as Relational?

**Overlap**: Psychological states with Experiencer-Stimulus structure can be seen as relational.

**Psychological state** (relational interpretation):
```
João gosta de música (João likes music)
- Experiencer: João
- Stimulus: música
- Relational: liking-relation between João and music
- Static: holds over time

João sabe a resposta (João knows the answer)
- Knower: João
- Known: resposta
- Relational: knowledge-relation
```

**Classification choice**:
- Can tag as **Experiential namespace** (psychological/epistemic content)
- Can also tag as **Relational** (dyadic structure, experiencer-stimulus relation)

**Recommendation**: Tag psychological states as **Experiential** primary, **Relational** secondary (they are relational in structure but experiential in content).

## Summary Table: Relational Frame Properties

| **Relational Type** | **Symmetry** | **Transitivity** | **Typical Verb/Copula** | **Example** |
|---------------------|--------------|------------------|------------------------|-------------|
| **Possession** | Asymmetric | No | *ter*, *possuir*, *ser de* | *João tem carro* |
| **Kinship** | Varies (sym/asym) | Sometimes | *ser* | *João é pai de Maria* |
| **Social/Professional** | Varies | No (usually) | *ser* | *Pedro é amigo de Ana* |
| **Part-whole** | Asymmetric | Yes | *ser parte de*, *ter* | *Roda é parte do carro* |
| **Quantitative** | Asymmetric | No | *ter*, *medir*, *custar* | *Mesa tem 2 metros* |
| **Abstract** | Varies | Varies | *ter*, *implicar* | *Teoria tem implicações* |
| **Locational** | Asymmetric | Yes (containment) | *estar*, *ficar* | *Livro está na mesa* |

## Comprehensive Diagnostic Test Battery

| **Test** | **Relational Result** | **Non-Relational Result** |
|----------|----------------------|--------------------------|
| **Minimal arity** | Requires ≥2 participants | Can have 1 participant |
| **Role differentiation** | Distinct participant roles | Single role or no roles |
| **Isolation satisfaction** | Cannot be satisfied alone | Can be satisfied alone |
| **Converse existence** | Has converse predicate | No converse |
| **Symmetry** | Symmetric or asymmetric | Test not applicable |
| **Transitivity** | Transitive or intransitive | Test not applicable |
| **Relational noun** | Has relational noun | No relational noun |
| **Stativity** | Static (like statives) | May be dynamic |

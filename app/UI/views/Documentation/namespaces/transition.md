---
title: Transition
order: 5
description: Transition
---

# Transition Namespace

## Core Definition

Transition frames foreground the **TELIC ROLE** with emphasis on the **path or trajectory** connecting states, locations, or conditions. 
These frames center on entities moving through space or changing along dimensions, profiling the **directed motion** or **process of change** rather 
than just the starting or ending points. The essence of transition is the **traversal** itself - the journey, not just the destination.

**Theoretical foundation**: Transitions encode the template:

```
MOVE(Theme, FROM(Source), TO(Goal), ALONG(Path), via_Manner)
```

Where:
- **Theme**: The moving or changing entity
- **Source**: Starting point/state (optional, can be implicit)
- **Goal**: Endpoint/destination (optional, can be implicit)
- **Path**: The trajectory or route connecting Source to Goal
- **Manner**: How the movement occurs (optional)

**Key characteristics**:
- **Path-centric**: The trajectory is semantically prominent
- **Directionality**: Movement has inherent direction (from → to)
- **Bounded**: Movement toward or away from boundaries
- **Theme-focused**: Moving entity is primary participant
- **Telic (usually)**: Movement toward goal/endpoint

**Vendler aspectual class**: Accomplishments (durative with directed endpoint)

**Talmy's Motion Event typology**: Transitions are prototypical MOTION events, encoding Figure (Theme), Ground (Path/Goal), Motion, and optionally Manner.

## Path Structure: Source-Path-Goal Organization

The fundamental structure of transitions involves three spatial/abstract components:

### The SOURCE-PATH-GOAL Schema

**Image schema** (Johnson 1987, Lakoff 1987): A basic cognitive structure organizing our understanding of motion and change.

**Components**:

**SOURCE (Origin/Starting Point)**:
- Where Theme begins
- Marked by: *de* (from), *desde* (from/since)
- Often optional/backgrounded

**PATH (Trajectory/Route)**:
- Route Theme traverses
- Marked by: *por* (through), *através de* (through/across), *ao longo de* (along)
- Can be elaborated or unspecified

**GOAL (Destination/Endpoint)**:
- Where Theme ends up
- Marked by: *para/a* (to), *até* (until/up to)
- Often obligatory/foregrounded

**Examples**:

**Full SOURCE-PATH-GOAL structure**:
```
João foi [DE São Paulo] [ATRAVÉS DE Belo Horizonte] [PARA o Rio]
(João went [FROM São Paulo] [THROUGH Belo Horizonte] [TO Rio])

SOURCE: de São Paulo (from São Paulo)
PATH: através de Belo Horizonte (through Belo Horizonte)
GOAL: para o Rio (to Rio)
```

**Minimal structures** (components can be omitted):

```
João foi para o Rio (João went to Rio)
- Goal only, Source and Path implicit

João saiu de casa (João left home)
- Source only, Goal and Path implicit

João passou pela praça (João passed through the square)
- Path only, Source and Goal implicit
```

### Profiling Patterns: What Gets Foregrounded?

Different transition verbs **profile** (make salient) different components:

**GOAL-profiling verbs** (most common):
- **ir** (go), **vir** (come), **chegar** (arrive), **entrar** (enter)
- Goal is obligatory or strongly preferred
- Source and Path backgrounded

```
✓ João foi para casa (João went home) - Goal obligatory
✗ *João foi (João went) - ungrammatical without Goal or context
✓ João chegou ao Brasil (João arrived in Brazil) - Goal required
```

**SOURCE-profiling verbs**:
- **sair** (leave/exit), **partir** (depart), **emergir** (emerge)
- Source is obligatory or strongly preferred
- Goal backgrounded

```
✓ João saiu de casa (João left home) - Source required
✗ *João saiu para o trabalho (odd - "saiu" doesn't naturally take Goal)
✓ João partiu de São Paulo (João departed from São Paulo)
```

**PATH-profiling verbs**:
- **passar** (pass), **atravessar** (cross), **percorrer** (traverse)
- Path is obligatory
- Source and Goal can be unspecified

```
✓ João passou pela praça (João passed through the square) - Path required
✓ João atravessou a rua (João crossed the street) - Path required
✓ João percorreu todo o país (João traveled throughout the country) - Path extended
```

**SOURCE-GOAL verbs** (transition from X to Y):
- **mudar** (move/change), **transferir** (transfer), **passar** (pass/move from-to)
- Both Source and Goal relevant
- Path less emphasized

```
✓ A propriedade passou de João para Maria
(The property passed from João to Maria)
- Source: de João (from João)
- Goal: para Maria (to Maria)

✓ João mudou de São Paulo para o Rio
(João moved from São Paulo to Rio)
- Source: de São Paulo
- Goal: para o Rio
```

### Path Elaboration vs. Path Backgrounding

Transitions vary in whether the **Path** can be elaborated or remains schematic:


#### Elaboratable Path

Verbs that allow detailed specification of route:

```
João foi de São Paulo para o Rio [PASSANDO por Belo Horizonte e Brasília]
(João went from SP to Rio passing through BH and Brasília)

Maria caminhou [AO LONGO DA praia, ATRAVÉS DO parque, ATÉ a montanha]
(Maria walked along the beach, through the park, up to the mountain)
```

**Path elaboration markers**:
- *por* (through): *João passou por Minas*
- *através de* (across/through): *atravessou através do deserto*
- *ao longo de* (along): *caminhou ao longo do rio*
- *via* (via): *viajou via Paris*



#### Backgrounded/Schematic Path

Some verbs have **implied but unelaborated** Path:

```
João chegou ao Rio (João arrived in Rio)
- Path implicit (João traveled somehow but route not specified)
- Cannot elaborate: ✗ *João chegou através de Minas (odd)

Maria entrou na casa (Maria entered the house)
- Path: crossing boundary into house (minimal, not elaboratable)
- Cannot elaborate: ✗ *Maria entrou através do jardim na casa (odd)
```

## Domain Specificity: Physical vs. Abstract Transitions

Transition frames apply across multiple **domains** via metaphorical extension from spatial motion.

### Physical/Spatial Motion (Concrete)

**Definition**: Literal movement of entities through physical space.

**Semantic template**: `MOVE_PHYSICAL(Theme, FROM(Location₁), TO(Location₂), ALONG(Spatial_Path))`

#### Subtypes

**a) Translational motion (change of location)**:
```
João foi de casa para o trabalho
(João went from home to work)

Maria viajou para a Europa
(Maria traveled to Europe)

O carro moveu-se pela rua
(The car moved through the street)
```

**b) Vertical motion**:
```
O balão subiu ao céu
(The balloon rose to the sky)

A pedra caiu no chão
(The stone fell to the ground)

O avião decolou
(The plane took off)
```

**c) Boundary-crossing motion**:
```
João entrou na casa
(João entered the house - crossing boundary inward)

Maria saiu da sala
(Maria left the room - crossing boundary outward)

Pedro atravessou a fronteira
(Pedro crossed the border)
```

**d) Manner-of-motion**:
```
João correu até a escola
(João ran to school - running manner + Goal)

Maria nadou para a ilha
(Maria swam to the island - swimming manner + Goal)

Pedro dançou pela sala
(Pedro danced through the room - dancing manner + Path)
```

#### Properties

- Theme has physical existence and location
- Path is physical space
- Movement observable/verifiable
- Can be tracked frame-by-frame

### Abstract Transitions: Possession Transfer

**Definition**: Transfer of ownership, control, or possession between entities.

**Semantic template**: `TRANSFER(Theme, FROM(Source_Possessor), TO(Goal_Recipient))`

**Examples**:

**a) Object transfer (giving/taking)**:
```
João deu o livro para Maria
(João gave the book to Maria)
- Theme: o livro (the book)
- Source: João (from João's possession)
- Goal: Maria (to Maria's possession)

Maria passou a bola para Pedro
(Maria passed the ball to Pedro)
```

**b) Ownership transfer**:
```
A propriedade passou de João para Pedro
(The property passed from João to Pedro)

O título transferiu-se para o comprador
(The title transferred to the buyer)
```

**c) Abstract entity transfer**:
```
O poder passou para o novo governo
(Power passed to the new government)

A responsabilidade foi para a gerência
(Responsibility went to management)
```

**Metaphorical mapping**:
- **Possession = Location**: Having something = something being at you
- **Change of possession = Motion**: Transfer = movement from one location (possessor) to another
- **Recipient = Goal**: Person receiving = destination

### Abstract Transitions: State Change

**Definition**: Movement along abstract dimensions or between states.

**Semantic template**: `CHANGE(Theme, FROM(State₁), TO(State₂), ALONG(Abstract_Dimension))`

#### Subtypes

**a) Property scale transitions (degree achievements)**:
```
A temperatura subiu de 20° para 30°
(The temperature rose from 20° to 30°)
- Dimension: temperature scale
- Source: 20°
- Goal: 30°

O preço caiu de R$100 para R$50
(The price fell from R$100 to R$50)
- Dimension: value/price scale
```

**b) Condition/status transitions**:
```
João passou de estudante a professor
(João went from student to professor)
- Dimension: professional status
- Source state: estudante
- Goal state: professor

Maria mudou de solteira para casada
(Maria changed from single to married)
- Dimension: marital status
```

**c) Emotional/psychological transitions**:
```
João passou da alegria ao desespero
(João went from joy to despair)
- Dimension: emotional scale

Maria transitou da confiança para a dúvida
(Maria transitioned from confidence to doubt)
```

**d) Life stage transitions**:
```
A criança passou da infância para a adolescência
(The child passed from childhood to adolescence)

João foi de jovem a velho
(João went from young to old)
```

**Metaphorical mapping**:
- **States = Locations**: Being in a state = being at a location
- **Change of state = Motion**: Changing state = moving from one location to another
- **Abstract dimension = Path**: Scale/continuum = spatial path


### Abstract Transitions: Information/Message Transfer

**Definition**: Communication and information flow between entities.

**Semantic template**: `COMMUNICATE(Message, FROM(Source), TO(Goal), via_Channel)`

**Examples**:

**a) Verbal communication**:
```
João disse a verdade para Maria
(João told the truth to Maria)
- Theme: a verdade (the truth - information)
- Source: João (sender)
- Goal: Maria (receiver)

A notícia chegou aos jornais
(The news reached the newspapers)
```

**b) Written communication**:
```
João enviou uma carta para Pedro
(João sent a letter to Pedro)

A mensagem foi para o destinatário
(The message went to the recipient)
```

**c) Abstract information flow**:
```
A ideia passou de geração em geração
(The idea passed from generation to generation)

O conhecimento foi transmitido aos alunos
(Knowledge was transmitted to the students)
```

**Metaphorical mapping**:
- **Information = Object**: Ideas/messages = transferable entities
- **Communication = Transfer**: Communicating = sending/moving information
- **Recipient = Goal**: Listener/reader = destination

### Abstract Transitions: Category Membership

**Definition**: Movement between categories, classifications, or types.

**Semantic template**: `RECLASSIFY(Theme, FROM(Category₁), TO(Category₂))`

**Examples**:

**a) Taxonomic reclassification**:
```
Plutão passou de planeta para planeta anão
(Pluto went from planet to dwarf planet)
- Source category: planeta
- Goal category: planeta anão

O tomate foi de vegetal para fruta (na classificação)
(The tomato went from vegetable to fruit in classification)
```

**b) Status/role change**:
```
João passou de funcionário para gerente
(João went from employee to manager)

Maria transitou de estudante para profissional
(Maria transitioned from student to professional)
```

## Manner vs. Path Conflation: Portuguese Encoding Patterns

Languages differ in how they encode **Manner** (how motion occurs) and **Path** (trajectory/direction) in motion verbs. This is crucial for understanding Portuguese transition frames.

### Talmy's Typology: Verb-Framed vs. Satellite-Framed

**Talmy (1985, 2000)**: Languages fall into two types:

**Satellite-framed languages** (Germanic: English, German):
- **PATH** expressed in satellite (particle, preposition)
- **MANNER** conflated in verb root

**Verb-framed languages** (Romance: Portuguese, Spanish, French):
- **PATH** conflated in verb root
- **MANNER** expressed separately (adverb, gerund, adjunct)

### Portuguese as Verb-Framed Language

**Portuguese pattern**: Verb expresses PATH, Manner expressed separately

**Examples**:

**English (Satellite-framed)**:
```
The bottle floated INTO the cave
- Manner: float (in verb)
- Path: into (in satellite)
```

**Portuguese (Verb-framed)**:
```
A garrafa ENTROU na caverna FLUTUANDO
(The bottle entered the cave floating)
- Path: entrou (entered - in verb)
- Manner: flutuando (floating - in gerund/adjunct)
```

#### More contrasts:

| **English (Manner in verb)** | **Portuguese (Path in verb)** |
|------------------------------|-------------------------------|
| run out | sair correndo (exit running) |
| swim across | atravessar nadando (cross swimming) |
| dance into | entrar dançando (enter dancing) |
| fly away | partir voando (depart flying) |
| roll down | descer rolando (descend rolling) |

### Portuguese Manner-of-Motion Verbs

Portuguese does have some manner verbs, but they're **less common** and have **restricted path-encoding**:

**Manner verbs** (manner conflated):
- **correr** (run), **nadar** (swim), **voar** (fly), **andar** (walk), **rastejar** (crawl)

**But**: These typically need **separate path expression**:

```
✓ João correu PARA casa (João ran TO home)
  - Manner in verb: correr (run)
  - Path in PP: para casa (to home)

✓ Maria nadou ATÉ a ilha (Maria swam TO the island)
  - Manner in verb: nadar (swim)
  - Path in PP: até a ilha (to the island)
```

**Contrast with English** where path can be in particle:
```
English: João ran OUT (path in particle "out")
Portuguese: João saiu CORRENDO (path in verb "saiu", manner in gerund "correndo")
```

### Path Verbs with Manner Adjuncts

The **productive pattern** in Portuguese: Path verb + Manner adjunct

**Structure**: `[PATH_VERB] + [MANNER_GERUND/ADJUNCT]`

**Examples**:

```
João SAIU CORRENDO de casa
(João exited running from home)
- Path: saiu (exited)
- Manner: correndo (running)

Maria ENTROU DANÇANDO na festa
(Maria entered dancing into the party)
- Path: entrou (entered)
- Manner: dançando (dancing)

O carro PASSOU ZUNINDO pela rua
(The car passed whizzing through the street)
- Path: passou (passed)
- Manner: zunindo (whizzing)

Pedro DESCEU ESCORREGANDO pela encosta
(Pedro descended sliding down the slope)
- Path: desceu (descended)
- Manner: escorregando (sliding)
```

### Implications for FrameNet Brasil

**For frame classification**:

1. **Path verbs** (entrar, sair, subir, descer, passar, etc.) → **Transition namespace**
   - Core PATH semantics
   - Goal/Source orientation

2. **Manner verbs** (correr, nadar, voar, etc.) → More complex:
   - If used with Goal/Source → **Transition namespace** (path-directed motion)
   - If used without Goal/Source → **Activity namespace** (manner-focused activity)

**Examples**:
```
TRANSITION: João correu PARA casa (path-directed)
ACTIVITY: João correu (durante uma hora) (manner activity, no path)
```

3. **Manner adjuncts** (gerunds like *correndo, nadando*) → Should be marked as **Manner** FE, not separate frames

## Boundary Crossing: Bounded vs. Unbounded Transitions

Transitions vary in whether they involve crossing a **boundary** or **threshold**.

### Boundary-Crossing Transitions

**Definition**: Motion that involves crossing a discrete boundary or threshold, entering/exiting a bounded region.

**Semantic structure**: `CROSS_BOUNDARY(Theme, FROM(Region₁), TO(Region₂))`

**Key property**: Emphasizes the **momentary crossing event** at the boundary

#### Subtypes

**a) Entry (crossing inward)**:
```
João entrou na casa
(João entered the house)
- Boundary: threshold of house
- Direction: outside → inside (inward)

Maria ingressou na universidade
(Maria entered the university - abstract)
- Boundary: membership threshold
```

**b) Exit (crossing outward)**:
```
João saiu da sala
(João exited the room)
- Boundary: threshold of room
- Direction: inside → outside (outward)

Pedro deixou o país
(Pedro left the country)
```

**c) Crossing (traversing)**:
```
João atravessou a fronteira
(João crossed the border)
- Boundary: border/frontier
- Direction: one side → other side

Maria transpôs a barreira
(Maria crossed over the barrier)
```

**d) Penetration (entering substance/medium)**:
```
A faca penetrou na carne
(The knife penetrated the meat)
- Boundary: surface of meat
- Direction: outside → inside (with force)

O submarino submergiu na água
(The submarine submerged in the water)
```

#### Diagnostic features

1. **Punctual or near-punctual**: Boundary crossing is relatively instantaneous
   ```
   João entrou às 3h (João entered at 3pm - point in time)
   ```

2. **Binary state change**: Before crossing ≠ After crossing
   ```
   Before: João está fora (João is outside)
   Crossing: João entra (João enters)
   After: João está dentro (João is inside)
   ```

3. **Incompatible with extended Path elaboration**:
   ```
   ✗ *João entrou através de vários quartos na casa
   (odd - "enter" is about crossing threshold, not path through rooms)
   ```

4. **Focus on threshold moment**:
   - Path before/after boundary is backgrounded
   - The crossing itself is profiled

### Non-Boundary (Unbounded) Transitions

**Definition**: Motion through open space without necessarily crossing discrete boundaries.

**Semantic structure**: `MOVE_THROUGH(Theme, ALONG(Extended_Path))`

**Key property**: Emphasizes **continuous trajectory** through space/domain

**Examples**:

**a) Passing through (traversal)**:
```
João passou pelo parque
(João passed through the park)
- No discrete boundary crossing
- Continuous motion through space

Maria percorreu toda a cidade
(Maria traveled throughout the city)
- Extended, continuous path
```

**b) Following path**:
```
O rio segue pela montanha
(The river follows through the mountain)

A estrada vai de SP até o Rio
(The road goes from SP to Rio)
```

**c) Wandering/roaming**:
```
João vagou pelas ruas
(João wandered through the streets)
- No specific boundaries
- Diffuse, extended path

Maria perambulou pelo bairro
(Maria roamed through the neighborhood)
```

#### Diagnostic features

1. **Durative**: Takes time, not instantaneous
   ```
   João passou pelo parque durante uma hora
   (João passed through the park for an hour)
   ```

2. **No binary state change**: Gradual, continuous
   ```
   No sharp before/after distinction
   ```

3. **Compatible with extended Path elaboration**:
   ```
   ✓ João passou pela praça, através do mercado, ao longo do rio
   (João passed through the square, across the market, along the river)
   ```

4. **Focus on trajectory**:
   - The path itself is profiled
   - Boundaries (if any) are incidental

### The Boundary Gradient

Some transitions involve **partial** or **diffuse** boundaries:

**Examples**:

**Approaching (nearing boundary)**:
```
João aproximou-se da casa
(João approached the house)
- Moving toward boundary but not necessarily crossing
```

**Departing (leaving vicinity)**:
```
João afastou-se da cidade
(João departed from the city)
- Leaving region around boundary
```

**These are transitions but boundary-crossing is less definite.**

## Reversibility: Unidirectional vs. Bidirectional Transitions

Transitions vary in whether they can be **reversed** or are inherently **unidirectional**.

### Bidirectional (Reversible) Transitions

**Definition**: Transitions that can occur in either direction, with explicit linguistic marking for each direction.

**Semantic structure**: Direction can be reversed without changing fundamental event type

**Spatial reversibility**:

| **Direction A** | **Direction B** | **Dimension** |
|-----------------|-----------------|---------------|
| *subir* (go up) | *descer* (go down) | Vertical |
| *entrar* (enter) | *sair* (exit) | In/out |
| *avançar* (advance) | *recuar* (retreat) | Forward/back |
| *aproximar* (approach) | *afastar* (move away) | Near/far |
| *abrir* (open) | *fechar* (close) | Open/closed |

**Examples**:
```
João SUBIU a montanha (João went up the mountain)
João DESCEU a montanha (João went down the mountain)
- Same path, opposite directions
- Both are reversible transitions

Maria ENTROU na casa (Maria entered the house)
Maria SAIU da casa (Maria exited the house)
- Reversible in/out movement
```

**Abstract reversibility**:

| **Direction A** | **Direction B** | **Dimension** |
|-----------------|-----------------|---------------|
| *aumentar* (increase) | *diminuir* (decrease) | Quantity/intensity |
| *melhorar* (improve) | *piorar* (worsen) | Quality |
| *enriquecer* (get rich) | *empobrecer* (get poor) | Wealth |
| *esquentar* (heat) | *esfriar* (cool) | Temperature |

**Examples**:
```
A temperatura AUMENTOU de 20° para 30°
(Temperature increased from 20° to 30°)

A temperatura DIMINUIU de 30° para 20°
(Temperature decreased from 30° to 20°)
- Reversible scalar change
```

**Properties**:
- Both directions are lexicalized (have distinct verbs)
- Can alternate freely based on direction of change
- Often involve symmetric scales or paths

### Unidirectional (Irreversible) Transitions

**Definition**: Transitions that occur in only one direction, or where reversal requires different framing/event type.

#### Subtypes

**a) Physical unidirectionality** (entropy/irreversibility):
```
O vaso quebrou (The vase broke)
- ✗ No simple verb for "un-breaking"
- Repair ≠ reverse of breaking

O gelo derreteu (The ice melted)
- Can refreeze, but "derreter" (melt) is unidirectional
- *Congelar* (freeze) is separate process, not simple reversal
```

**b) Temporal unidirectionality** (time's arrow):
```
João envelheceu (João aged)
- ✗ Cannot "un-age" (no *des-envelhecer)
- Aging is unidirectional with time

A criança cresceu (The child grew)
- ✗ Growing up is not reversible
```

**c) Life cycle transitions**:
```
João nasceu (João was born)
- ✗ Cannot reverse birth

Maria morreu (Maria died)
- ✗ Death is irreversible (in non-religious contexts)
```

**d) Social/legal transitions** (often unidirectional):
```
João formou-se (João graduated)
- ✗ Cannot "un-graduate"
- Degree achievement is permanent

Maria casou (Maria married)
- Reversal is *divorciar* (divorce), different event
- Not simple reversal but new legal act
```

#### Properties
- No simple opposite-direction verb
- Reversal (if possible) requires different event type or complex process
- Often involve irreversible physical/temporal processes
- May be culturally/legally one-way

### Pseudo-Reversible Transitions

Some transitions appear reversible but involve **different event structures**:

**Example**: Opening vs. Closing

```
OPENING: João abriu a porta (João opened the door)
CLOSING: João fechou a porta (João closed the door)
```

**Apparent reversal, but**:
- *Abrir* and *fechar* are separate lexical items
- Not morphologically related (unlike *ligar/desligar* - turn on/off)
- Represent different actions, not just direction reversal

---

**Example**: Arriving vs. Departing

```
ARRIVING: João chegou (João arrived)
DEPARTING: João partiu (João departed)
```

**Different framing**:
- *Chegar* profiles Goal (endpoint of journey)
- *Partir* profiles Source (starting point of journey)
- Not simple directional opposites

## Aspectual Properties of Transitions

Transitions vary in their **temporal structure** and **aspectual behavior**.

### Durative Transitions (Accomplishments)

**Definition**: Transitions that unfold over extended time with measurable duration.

**Vendler class**: Accomplishments

**Semantic structure**: Process leading to endpoint

**Examples**:

**a) Extended journeys**:
```
João viajou de SP para o Rio (em 6 horas)
(João traveled from SP to Rio in 6 hours)
- Duration: 6 hours
- Process: traveling
- Endpoint: arrival in Rio

Maria atravessou o país (em três semanas)
(Maria crossed the country in three weeks)
- Extended, measurable duration
```

**b) Gradual state changes**:
```
A temperatura aumentou de 20° para 30° (durante a tarde)
(Temperature increased from 20° to 30° during the afternoon)
- Gradual, continuous change
- Measurable time span

João envelheceu ao longo dos anos
(João aged over the years)
- Extended temporal process
```

#### Aspectual diagnostics

**Test 1: Durative temporal modification**
```
✓ João viajou DURANTE três horas (for three hours)
✓ A temperatura aumentou AO LONGO DO dia (throughout the day)
```

**Test 2: Progressive compatible**
```
✓ João está viajando para o Rio (João is traveling to Rio)
✓ A temperatura está aumentando (Temperature is increasing)
```

**Test 3: "Em X tempo" (completion time)**
```
✓ João viajou para o Rio EM seis horas (in six hours - completed)
= Took 6 hours to complete the journey
```

### Punctual Transitions (Achievements)

**Definition**: Transitions conceptualized as occurring instantaneously or near-instantaneously.

**Vendler class**: Achievements

**Semantic structure**: Instant of transition at boundary

**Examples**:

**a) Boundary crossings**:
```
João entrou na casa (às 3h)
(João entered the house at 3pm)
- Conceptualized as instantaneous crossing
- Point in time

Maria saiu da sala (de repente)
(Maria exited the room suddenly)
- Sudden, punctual event
```

**b) Arrival/departure**:
```
João chegou (às 5h)
(João arrived at 5pm)
- Arrival moment is punctual

O trem partiu (exatamente às 8h)
(The train departed exactly at 8am)
- Departure moment is punctual
```

**c) Sudden state changes**:
```
A luz acendeu (de repente)
(The light turned on suddenly)
- Instantaneous transition

O motor parou (às 3h15)
(The engine stopped at 3:15pm)
- Punctual cessation
```

#### Aspectual diagnostics

**Test 1: Point-in-time modification**
```
✓ João entrou ÀS 3h (at 3pm - point)
✗ *João entrou DURANTE três horas (for three hours - odd)
```

**Test 2: Progressive incompatible or coerced**
```
✗ *João está entrando (João is entering - requires special interpretation)
  → Can only mean: "is in process of entering" (slow motion) or "is about to enter" (imminence)

✓ João entrou (João entered - simple past, punctual)
```

**Test 3: "Em X tempo" = "after X time"**
```
? João chegou em três horas
  = After three hours, João arrived (not: completion time of arriving)
  vs. Accomplishment: = It took three hours to complete
```

### The Durative-Punctual Gradient

Many transitions can be construed either way depending on **granularity** and **focus**:

**Example**: *Atravessar* (cross)

**Durative construal** (focus on traversal process):
```
João atravessou a rua (lentamente, olhando para os dois lados)
(João crossed the street slowly, looking both ways)
- Focus: process of crossing
- Progressive OK: *João está atravessando a rua*
```

**Punctual construal** (focus on boundary moment):
```
João atravessou a fronteira (às 3h)
(João crossed the border at 3pm)
- Focus: moment of crossing threshold
- Progressive odd: ?*João está atravessando a fronteira*
```

#### Factors affecting construal

1. **Scale of Path**: Longer paths → durative; short paths → punctual
2. **Manner elaboration**: Manner specified → durative; unspecified → punctual
3. **Boundary salience**: Sharp boundary → punctual; diffuse → durative
4. **Temporal granularity**: Fine-grained → punctual; coarse → durative

## Transition Verbs: A Comprehensive Classification

Portuguese transition verbs can be systematically classified:

### Basic Path Verbs (Most Productive)

| **Verb** | **Path Type** | **Profiling** | **Boundary** | **Example** |
|----------|---------------|---------------|--------------|-------------|
| *ir* | Goal-oriented | Goal | No | *ir para casa* |
| *vir* | Goal-oriented (toward speaker) | Goal | No | *vir para cá* |
| *chegar* | Goal-oriented (arrival) | Goal | Yes (reaching) | *chegar ao Brasil* |
| *sair* | Source-oriented | Source | Yes (exiting) | *sair de casa* |
| *partir* | Source-oriented (departure) | Source | Yes (leaving) | *partir de SP* |
| *passar* | Path-oriented | Path | No (through) | *passar pela praça* |
| *entrar* | Inward boundary | Goal | Yes (entering) | *entrar na sala* |
| *atravessar* | Crossing | Path | Yes (traversing) | *atravessar a rua* |


### Vertical Motion Verbs

| **Verb** | **Direction** | **Reversible With** | **Example** |
|----------|---------------|---------------------|-------------|
| *subir* | Upward | *descer* | *subir a montanha* |
| *descer* | Downward | *subir* | *descer a escada* |
| *elevar* | Upward (raise) | *abaixar* | *elevar o braço* |
| *abaixar* | Downward (lower) | *elevar* | *abaixar a cabeça* |
| *levantar* | Upward (lift) | — | *levantar o objeto* |
| *cair* | Downward (fall) | — | *cair no chão* |

### Approach/Recession Verbs

| **Verb** | **Direction** | **Reversible With** | **Example** |
|----------|---------------|---------------------|-------------|
| *aproximar* | Toward | *afastar* | *aproximar-se da casa* |
| *afastar* | Away from | *aproximar* | *afastar-se do perigo* |
| *avançar* | Forward | *recuar* | *avançar para frente* |
| *recuar* | Backward | *avançar* | *recuar alguns passos* |

### Configuration Change Verbs (Spatial transitions)

| **Verb** | **Change** | **Reversible With** | **Example** |
|----------|------------|---------------------|-------------|
| *abrir* | Closed → Open | *fechar* | *abrir a porta* |
| *fechar* | Open → Closed | *abrir* | *fechar a janela* |
| *expandir* | Small → Large | *contrair* | *expandir o balão* |
| *contrair* | Large → Small | *expandir* | *contrair o músculo* |


### Abstract Transition Verbs

| **Verb** | **Domain** | **Transition Type** | **Example** |
|----------|------------|---------------------|-------------|
| *mudar* | State/Location | General change | *mudar de casa* |
| *transformar* | State/Category | Radical change | *transformar-se em borboleta* |
| *passar* | State/Possession | Transfer | *passar a propriedade* |
| *transferir* | Possession/Location | Transfer | *transferir o dinheiro* |
| *tornar-se* | State/Category | Become | *tornar-se médico* |
| *virar* | State/Category | Become (informal) | *virar adulto* |


## Summary Table: Transition Properties

| **Dimension** | **Type A** | **Type B** | **Diagnostic** |
|---------------|------------|------------|----------------|
| **Profiling** | Goal (*ir, chegar*) | Source (*sair, partir*) | Required argument |
| **Path** | Elaborated (*atravessar*) | Schematic (*chegar*) | Path detail possible |
| **Domain** | Physical (*correr*) | Abstract (*mudar*) | Concrete vs. metaphorical |
| **Manner** | Separate (*sair correndo*) | Conflated (*correr*) | Verb-framed pattern |
| **Boundary** | Crossing (*entrar*) | Unbounded (*passar*) | Sharp threshold |
| **Reversibility** | Bidirectional (*subir/descer*) | Unidirectional (*nascer*) | Opposite direction verb |
| **Aspect** | Durative (*viajar*) | Punctual (*chegar*) | Progressive test |


## Diagnostic Tests for Transition Frames

### Test 1: Path Expression
Can the verb take FROM/TO/THROUGH expressions?
```
✓ João foi DE casa PARA o trabalho (transition)
✗ *João correu (sem Goal/Source) (activity, not transition unless Goal added)
```

### Test 2: MOVE Decomposition
Can the verb be decomposed as MOVE(Theme, Source, Goal, Path)?
```
✓ ir = MOVE(Theme, FROM(x), TO(y)) → TRANSITION
✗ existir ≠ MOVE(...) → NOT TRANSITION (stative)
```

### Test 3: Theme Mobility
Does the Theme change location/state?
```
✓ João viajou (João moved locations) → TRANSITION
✗ João permaneceu (João remained - no change) → STATIVE
```

### Test 4: Directionality
Does the verb encode inherent direction?
```
✓ entrar (enter - inward direction) → TRANSITION
✓ subir (rise - upward direction) → TRANSITION
✗ estar (be - no direction) → STATIVE
```

### Test 5: Telicity (for path-verbs)
Does the verb have inherent endpoint?
```
✓ chegar (arrive - endpoint required) → TELIC TRANSITION
? passar (pass - endpoint optional) → LESS TELIC TRANSITION
```

## Boundary Cases and Overlaps

### Transition vs. Inchoative

**Overlap**: Both involve change, but different focus

**Inchoative**: Focus on **resultant state**
```
A porta abriu (The door opened)
- Focus: door is now in open state
- Endpoint-oriented
```

**Transition**: Focus on **path/trajectory**
```
João foi para casa (João went home)
- Focus: journey from X to home
- Path-oriented
```

**Diagnostic**:
- If result state prominent → Inchoative
- If path/trajectory prominent → Transition

### Transition vs. Action

**Overlap**: Manner-of-motion verbs

**Action** (no Goal/Path):
```
João correu (durante uma hora)
(João ran for an hour)
- Focus: manner of action (running activity)
- No inherent endpoint
- Atelic
- ACT(João, run)
```

**Transition** (with Goal/Path):
```
João correu PARA casa
(João ran to home)
- Focus: path to destination
- Inherent endpoint (home)
- Telic
- MOVE(João, to_Goal(casa), via_Manner(running))
```

**Diagnostic**: Presence of Goal/Source/Path → Transition; absence → Action

**Key difference**:
- **Action**: Profiles the activity itself (running as an activity)
- **Transition**: Profiles the path/trajectory (movement to a goal)

### Transition vs. Experiential

**Overlap**: Abstract "movement" in perceptual/epistemic domain

**Experiential**:
```
João percebeu o erro
(João perceived the error)
- Focus: perceptual/cognitive event
- No spatial metaphor prominent
```

**Transition** (metaphorical):
```
João passou da ignorância ao conhecimento
(João passed from ignorance to knowledge)
- Focus: movement along epistemic dimension
- Spatial metaphor prominent
```

[//]: # (### **12. Recommendations for FrameNet Brasil / DAISY**)

[//]: # ()
[//]: # (1. **Tag transition frames with features**:)

[//]: # (   ```)

[//]: # (   [transition_type: spatial|possession|state|information|category])

[//]: # (   [path_profiling: source|goal|path|source-goal])

[//]: # (   [manner_encoding: verb-conflated|adjunct])

[//]: # (   [boundary_crossing: yes|no])

[//]: # (   [reversibility: bidirectional|unidirectional])

[//]: # (   [aspect: durative|punctual])

[//]: # (   ```)

[//]: # ()
[//]: # (2. **Mark manner separately**:)

[//]: # (   - Manner verbs with Goal → Transition frame, mark manner as FE)

[//]: # (   - Manner gerunds → Manner FE, not separate frame)

[//]: # (   - Examples: *correr para* → Transition:Running with Goal)

[//]: # ()
[//]: # (3. **Distinguish abstract domains**:)

[//]: # (   - Physical motion → Motion:Transition)

[//]: # (   - Possession transfer → Transfer:Possession)

[//]: # (   - State change → Change:State)

[//]: # (   - Use metaphorical frame relations to link domains)

[//]: # ()
[//]: # (4. **For DAISY parsing**: Use Source/Goal/Path presence for disambiguation:)

[//]: # (   - If Goal/Source/Path present → Transition frame)

[//]: # (   - If manner-verb without Goal → Activity frame)

[//]: # (   - If boundary-crossing verb → check for Transition:Boundary_crossing)

[//]: # ()
[//]: # (5. **Encode path structure**:)

[//]: # (   - Mark Source, Path, Goal as separate FEs)

[//]: # (   - Indicate which is obligatory/optional/profiled)

[//]: # (   - Example: *entrar* requires Goal, backgrounds Source/Path)

[//]: # ()
[//]: # (6. **Link reversible pairs**:)

[//]: # (   - *subir* ↔ *descer* &#40;opposite_direction_of&#41;)

[//]: # (   - *entrar* ↔ *sair* &#40;opposite_direction_of&#41;)

[//]: # (   - Encode as frame relations)

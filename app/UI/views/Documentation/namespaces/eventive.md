---
title: Eventive
order: 7
description: Eventive
---

# Eventive Namespace

## Core Definition

Eventive frames foreground **dynamic occurrences** as central phenomena, emphasizing the **Event itself** rather than specific participant roles.
These frames profile **happenings or processes** where the event's occurrence is the primary semantic content, with participants playing supporting
but less differentiated or less semantically specified roles. The essence of eventive frames is that "something happens" - the event is salient,
participants may be backgrounded, underspecified, or absent.

**Theoretical foundation**: Eventive frames encode the basic template:

```
OCCUR(Event)
  [with optional: PARTICIPANT(Theme/Patient, role_unspecified)]
  [with optional: LOCATION(where)]
  [with optional: TIME(when)]
```

**Key characteristics**:
- **Event-centric**: The event itself is primary, not who causes it or who is affected
- **Participant flexibility**: Participants may be optional, backgrounded, or unspecified
- **No obligatory Agent/Cause**: Unlike causatives and actions, no volitional causer is required or profiled
- **No obligatory affected Patient**: Unlike inchoatives, change-of-state may be absent or backgrounded
- **No obligatory Path**: Unlike transitions, directional movement is not central
- **Dynamic**: Unlike statives, involves change/happening over time
- **Non-agentive**: Typically involves natural phenomena, not intentional agents

**Vendler aspectual diversity**: Can be Activities (atelic) or Accomplishments (telic), rarely States

**Philosophical grounding**: Eventive frames capture what Davidson (1967) called "pure events" - occurrences that can be described without necessarily specifying all participants. They emphasize **eventuality** (that something occurred) over **participation structure**.

## Scope Clarification

**What Eventive frames INCLUDE**:
- Natural meteorological phenomena: *Choveu* (It rained), *Nevou* (It snowed)
- Natural forces and processes: *O vento soprou* (The wind blew), *O rio transbordou* (The river overflowed)
- **Non-agentive causal events**: *O vento quebrou a janela* (The wind broke the window), *O terremoto destruiu a cidade* (The earthquake destroyed the city)
- Spontaneous occurrences: *Aconteceu um acidente* (An accident happened)
- Biological/physiological processes (non-volitional): *A planta cresceu* (The plant grew)

**What Eventive frames EXCLUDE** (see other namespaces):
- **Agentive activities** → See **Action namespace** (*João correu* - João ran)
- **Agentive causation with results** → See **Causative namespace** (*João quebrou o vaso* - João broke the vase)
- **Result-focused without explicit cause** → See **Inchoative namespace** (*O vaso quebrou* - The vase broke)

**Note on non-agentive causes**: Natural forces (wind, rain, earthquakes) causing changes are classified as **Eventive** rather than Causative. While they may cause results, the lack of agency/intentionality places them in this namespace:
- *O vento quebrou a janela* (Wind broke the window) → **Eventive** (natural force, no agent)
- *João quebrou a janela* (João broke the window) → **Causative** (agentive causation)

## Natural Phenomena Subtypes

A major class of eventive frames involves **natural processes** - events in the physical world without human agency.

### Meteorological Events (Weather)

**Definition**: Atmospheric phenomena and weather conditions.

**Semantic template**: `OCCUR(Weather_Event, at_Location, at_Time)`

**Characteristic property**: Often **zero-argument** or **impersonal constructions** in Portuguese

#### Subtypes

**a) Precipitation events**:
```
Choveu (ontem)
(It rained yesterday)
- Zero-argument verb
- No explicit subject (impersonal)

Nevou nas montanhas
(It snowed in the mountains)

Granizou durante a tempestade
(It hailed during the storm)
```

**Portuguese pattern**: Weather verbs are **impersonal** (no grammatical subject)
- ✗ *A chuva choveu (ungrammatical - "the rain rained")
- ✓ Choveu (impersonal - "it rained")

**b) Wind events**:
```
Ventou muito ontem
(It was very windy yesterday / The wind blew hard)

O vento soprou
(The wind blew)
- Can have explicit subject "o vento" (the wind)
- Or impersonal: Ventou
```

**c) Temperature events**:
```
Esfriou durante a noite
(It got cold during the night)

Esquentou muito hoje
(It got very hot today)
```

**d) Storm events**:
```
Trovejou a noite toda
(It thundered all night)

Relampejou antes da chuva
(It lightninged / There was lightning before the rain)

Houve uma tempestade
(There was a storm)
```

**e) Atmospheric conditions**:
```
Amanheceu nublado
(It dawned cloudy / The day broke cloudy)

Anoiteceu rapidamente
(It got dark quickly / Night fell quickly)
```

#### Participant structure

**Minimal participants**:
- Often **no Theme/Patient** explicitly
- Location and Time are primary adjuncts
- Event itself is primary

**Example analysis**:
```
Choveu [em São Paulo] [ontem]
(It rained [in São Paulo] [yesterday])

Event: chuva (rain occurrence)
Location: em São Paulo
Time: ontem
No Theme, no Agent, no Patient
```

**Cross-linguistic note**: Portuguese differs from English in allowing more flexibility:
```
Portuguese: 
- Choveu (impersonal, most common)
- Caiu chuva (lit. "fell rain" - rare)
- A chuva caiu (lit. "the rain fell" - possible but marked)

English:
- It rained (impersonal with dummy "it")
- ✗ *Rained (no subject required)
```

### Geological/Seismic Events

**Definition**: Earth processes and tectonic phenomena.

**Examples**:

**a) Seismic activity**:
```
Houve um terremoto
(There was an earthquake)
- Existential construction with "houve" (there was)

O chão tremeu
(The ground shook)
- Can have explicit Theme "o chão" (the ground)

A terra balançou
(The earth/ground swayed)
```

**b) Volcanic activity**:
```
O vulcão entrou em erupção
(The volcano erupted)
- Explicit Theme "o vulcão" (the volcano)

Houve uma erupção
(There was an eruption)
- Existential, no explicit Theme
```

**c) Erosion processes**:
```
A rocha erodiu
(The rock eroded)
- Theme "a rocha" can be specified

Ocorreu erosão na costa
(Erosion occurred on the coast)
- Event-nominalization, minimal participant structure
```

**d) Landslides/avalanches**:
```
Houve um deslizamento de terra
(There was a landslide)

A montanha desmoronou
(The mountain collapsed)
```

#### Properties
- Can have explicit Theme (physical entity undergoing process)
- Or existential construction backgrounding Theme
- No Agent/Cause typically (natural forces)
- Process-focused

### Astronomical/Celestial Events

**Definition**: Cosmic and celestial phenomena.

**Examples**:

**a) Solar/lunar events**:
```
O sol nasceu
(The sun rose / Sunrise occurred)
- Explicit Theme "o sol"

Amanheceu
(It dawned / Day broke)
- Impersonal

Anoiteceu
(It got dark / Night fell)
- Impersonal

A lua surgiu
(The moon appeared/rose)
```

**b) Eclipses**:
```
Houve um eclipse
(There was an eclipse)

O sol se eclipsou
(The sun was eclipsed)
- Reflexive construction
```

**c) Meteor events**:
```
Uma estrela caiu
(A star fell / A meteor fell)

Choveram meteoritos
(Meteors rained down)
- Meteorological metaphor
```

### Biological/Organic Processes

**Definition**: Natural biological processes and organic events.

**Examples**:

**a) Growth/development**:
```
A planta cresceu
(The plant grew)
- Theme "a planta" specified

As flores brotaram
(The flowers sprouted)

A semente germinou
(The seed germinated)
```

**b) Decay/decomposition**:
```
A madeira apodreceu
(The wood rotted)

O fruto estragou
(The fruit spoiled)

A matéria se decompôs
(The matter decomposed)
- Reflexive construction
```

**c) Blooming/flowering**:
```
A árvore floresceu
(The tree bloomed)

As rosas desabrocharam
(The roses blossomed)
```

**d) Natural cycles**:
```
As folhas caíram
(The leaves fell)

A maré subiu
(The tide rose)

A estação mudou
(The season changed)
```

#### Properties
- Theme typically specified (plant, organism, natural entity)
- Processes are **spontaneous** (internal biological programs)
- No external Agent/Cause profiled
- Gradual, natural development

### Physical/Chemical Processes

**Definition**: Non-living natural processes governed by physical/chemical laws.

**Examples**:

**a) Combustion**:
```
O fogo queimou
(The fire burned)

A madeira ardeu
(The wood burned)

Houve uma combustão
(There was a combustion)
```

**b) Phase transitions**:
```
A água evaporou
(The water evaporated)

O gelo derreteu
(The ice melted)
- Can be framed as inchoative (focus on result state)
- Or eventive (focus on process occurring)

O vapor condensou
(The vapor condensed)
```

**c) Chemical reactions**:
```
A substância reagiu
(The substance reacted)

Ocorreu uma reação química
(A chemical reaction occurred)

O metal oxidou
(The metal oxidized)
```

**d) Sound/light phenomena**:
```
O som ecoou
(The sound echoed)

A luz refletiu
(The light reflected)

A onda propagou-se
(The wave propagated)
```

## Processes Without Endpoints: Activities vs. Accomplishments

Eventive frames vary in **telicity** - whether they have inherent endpoints.

### Atelic Activities (No Inherent Endpoint)

**Definition**: Events that can continue indefinitely without natural terminus; no built-in completion point.

**Vendler class**: Activities

**Semantic structure**: `OCCUR(Activity(x))` with no inherent bound

**Examples**:

**a) Continuous processes**:
```
O rio flui
(The river flows)
- Can continue indefinitely
- No natural endpoint

O vento sopra
(The wind blows)
- Ongoing, unbounded

A máquina funciona
(The machine functions)
- Continuous operation
```

**b) Oscillatory/repetitive processes**:
```
O coração bate
(The heart beats)
- Repetitive, ongoing

As ondas se agitam
(The waves churn)
- Continuous motion

A bandeira tremula
(The flag flutters)
```

**c) Emission processes**:
```
A luz brilha
(The light shines)
- Continuous emission

O rádio transmite
(The radio transmits)

A fonte jorra
(The fountain gushes)
```

### Aspectual diagnostics for Activities

**Test 1: "Por X tempo" (for X time) - compatible**
```
✓ O rio fluiu por horas (The river flowed for hours)
✓ O vento soprou por três dias (The wind blew for three days)
- Specifies duration, no endpoint implied
```

**Test 2: "Em X tempo" (in X time) - incompatible**
```
✗ *O rio fluiu em uma hora (odd - no completion point)
✗ *O vento soprou em três dias (odd - "in" suggests completion)
- Cannot specify completion time (no completion)
```

**Test 3: Progressive - fully compatible**
```
✓ O rio está fluindo (The river is flowing)
✓ O vento está soprando (The wind is blowing)
- Natural, ongoing interpretation
```

**Test 4: Stop test - can stop at any point**
```
O rio fluiu por uma hora e então parou
(The river flowed for an hour and then stopped)
- Stopping at any point is natural
- No sense of incompleteness
```

**Test 5: Homogeneity - subinterval property**
```
If: O rio fluiu de 2h às 5h
Then: O rio fluiu de 2h às 3h (any subinterval is also flowing)
- Homogeneous temporal structure
```

### Telic Accomplishments (With Inherent Endpoint)

**Definition**: Events that have natural completion points or inherent goals.

**Vendler class**: Accomplishments

**Semantic structure**: `OCCUR(Process(x)) → RESULT(State(x))`

**Examples**:

**a) Maturation processes**:
```
A fruta amadureceu
(The fruit ripened)
- Endpoint: ripe state achieved
- Process: gradual ripening

A criança desenvolveu-se
(The child developed)
- Endpoint: mature/developed state
```

**b) Consumption processes**:
```
A vela consumiu-se
(The candle was consumed / burned down)
- Endpoint: complete consumption
- Process: gradual burning

O combustível esgotou-se
(The fuel was exhausted)
```

**c) Completion processes**:
```
O ciclo completou-se
(The cycle completed)
- Inherent endpoint: cycle completion

O processo terminou
(The process ended)
```

#### Aspectual diagnostics for Accomplishments

**Test 1: "Em X tempo" (in X time) - compatible**
```
✓ A fruta amadureceu em duas semanas (ripened in two weeks)
✓ A vela consumiu-se em três horas (burned down in three hours)
- Completion time specified
```

**Test 2: "Por X tempo" (for X time) - less natural**
```
? A fruta amadureceu por duas semanas (ripened for two weeks)
- Less natural than "em"; suggests process duration but odd
```

**Test 3: Progressive - compatible**
```
✓ A fruta está amadurecendo (The fruit is ripening)
✓ A vela está se consumindo (The candle is burning down)
- Ongoing toward endpoint
```

**Test 4: Stop test - incomplete if stopped**
```
A fruta amadureceu por uma semana e então parou
(The fruit ripened for a week and then stopped)
- Implies incomplete ripening
- Sense of incompleteness
```

**Test 5: Non-homogeneous - phases**
```
A fruta amadureceu de segunda a sexta
NOT necessarily: A fruta amadureceu na segunda (specific day)
- Different phases of ripening (beginning, middle, end)
- Not homogeneous like activities
```

### The Atelic-Telic Boundary

Many eventive processes can be construed as either atelic or telic depending on context:

**Example**: *Queimar* (burn)

**Atelic construal** (activity):
```
A madeira queimou por horas
(The wood burned for hours)
- Focus: ongoing burning activity
- No completion implied
- Can stop at any point
```

**Telic construal** (accomplishment):
```
A madeira queimou em duas horas
(The wood burned down in two hours)
- Focus: complete consumption
- Endpoint: wood fully consumed
- Completion-oriented
```

#### Factors affecting construal

1. **Object definiteness**: Definite objects → telic; mass/indefinite → atelic
2. **Temporal modification**: "em X" → telic; "por X" → atelic  
3. **Completion prefixes**: *consumir-se* → telic emphasis
4. **Context**: Completion context → telic; ongoing context → atelic

## Participant Underspecification

A defining feature of eventive frames is **participant flexibility** - participants may be optional, backgrounded, or semantically underspecified.

### Optional Participants

Many eventive frames allow participants to be omitted without ungrammaticality:

**Examples**:

**a) Optional Theme**:
```
Choveu (It rained)
- No Theme necessary (impersonal)

vs.

A chuva caiu (The rain fell)
- Theme specified but not required
```

**b) Optional Location**:
```
Houve um terremoto (There was an earthquake)
- Location unspecified

vs.

Houve um terremoto no Chile (There was an earthquake in Chile)
- Location specified
```

**c) Optional Manner/Instrument**:
```
O fogo queimou (The fire burned)
- No manner specified

vs.

O fogo queimou intensamente (The fire burned intensely)
- Manner specified
```

### Backgrounded Participants

Some eventive frames have potential participants that are **systematically backgrounded** - present conceptually but not expressed:

**Examples**:

**a) Backgrounded Cause**:
```
A rocha erodiu (The rock eroded)
- Cause (wind, water, etc.) not specified
- Focus on event happening, not what caused it

O metal oxidou (The metal oxidized)
- Chemical cause (oxygen exposure) backgrounded
- Event itself profiled
```

**b) Backgrounded Agent** (in spontaneous events):
```
A porta abriu (The door opened)
- Possible Agent not mentioned
- Construed as spontaneous

A janela quebrou (The window broke)
- Possible Causer backgrounded
- Event-focused interpretation
```

### Semantically Underspecified Participants

When participants are expressed in eventive frames, their roles may be **semantically general** or **underspecified**:

**Generic Theme role**:
```
A água flui (The water flows)
- Theme: água (water)
- Role: generic participant in flowing event
- Not affected/changed (unlike Patient)
- Not actively causing (unlike Agent)
- Just: thing that flows

O som propagou-se (The sound propagated)
- Theme: som (sound)
- Role: thing that propagates (generic)
```

**Contrast with specified roles**:

**Causative frame** (specified roles):
```
João quebrou o vaso
- Agent: João (intentional causer)
- Patient: vaso (affected entity)
- Roles well-differentiated
```

**Eventive frame** (underspecified role):
```
O vaso quebrou
- Theme: vaso (undergoer/participant)
- Role: thing that breaks (generic participation)
```

### Zero-Participant Events (Impersonal)

Some eventive frames have **no obligatory participants**:

**Weather verbs** (fully impersonal):
```
Choveu (It rained)
Nevou (It snowed)
Trovejou (It thundered)
- No grammatical subject
- No Theme required
- Pure event expression
```

**Existential events**:
```
Houve uma festa (There was a party)
- Existential construction
- Event/entity existence asserted
- No participant roles assigned
```

**Temporal events**:
```
Amanheceu (It dawned / Day broke)
Anoiteceu (It got dark / Night fell)
- Impersonal temporal transitions
- No participants
```

## Spontaneous Events: Events Without Clear External Causation

A key characteristic of many eventive frames is **spontaneity** - events occur without clear external cause.

### What is Spontaneous Causation?

**Definition**: Events conceptualized as arising from **internal forces**, **natural processes**, or **unknown causes** rather than external agents or clear causal events.

**Semantic structure**: `OCCUR(Event)` without `CAUSE(x, Event)` component

**Contrast with causatives**:
```
CAUSATIVE: João quebrou o vaso
- External Agent João causes breaking
- Causation is profiled

SPONTANEOUS/EVENTIVE: O vaso quebrou
- No external cause specified
- Event just happens (from perspective expressed)
- Possible causes backgrounded
```

### Types of Spontaneous Events

**a) Physical spontaneity (structural failure)**:
```
A corda arrebentou (The rope snapped)
- Internal stress causes failure
- No external agent profiled

O galho partiu (The branch broke)
- Structural weakness, wind, weight → causes backgrounded
- Event itself profiled

A parede rachinou (The wall cracked)
```

**b) Natural maturation (biological programs)**:
```
A fruta amadureceu (The fruit ripened)
- Internal biological program drives maturation
- No external agent needed

A planta cresceu (The plant grew)
- Natural growth process

O cabelo branqueou (The hair turned white/gray)
```

**c) Chemical/physical processes (natural laws)**:
```
O metal enferrujou (The metal rusted)
- Chemical oxidation occurs naturally
- Exposure to oxygen = condition, not profiled cause

A água evaporou (The water evaporated)
- Phase transition due to temperature
- Conditions enable but aren't profiled as causes

O alimento estragou (The food spoiled)
```

**d) Appearance/disappearance**:
```
Um problema surgiu (A problem arose/appeared)
- Emergence without specified cause
- Spontaneous manifestation

A dor desapareceu (The pain disappeared)
- Spontaneous cessation
```

### Spontaneity vs. Causation: The Perspective Shift

Many events can be framed as **either spontaneous or caused** depending on perspective:

**Spontaneous framing** (eventive):
```
A porta abriu
(The door opened)
- No cause mentioned
- Event just happens
- Construed as spontaneous

Focus: Event occurrence
```

**Caused framing** (causative):
```
João abriu a porta
(João opened the door)
- Causer specified
- Intentional causation
- Agent-oriented

Focus: Causation by Agent
```

**Or with non-intentional cause**:
```
O vento abriu a porta
(The wind opened the door)
- Natural force cause
- Still causative (not spontaneous)
```

#### Linguistic evidence for spontaneity

**Incompatibility with causal questions**:
```
Spontaneous eventive:
A fruta amadureceu
? Por que a fruta amadureceu? (Why did the fruit ripen?)
  → Odd question (natural process, not requiring explanation)
  → Or: answer refers to conditions, not cause: "porque estava na árvore" (because it was on the tree)

Causative:
João quebrou o vaso
✓ Por que João quebrou o vaso? (Why did João break the vase?)
  → Natural question expecting intentional reason
```

**Manner of causation unspecifiable**:
```
Spontaneous:
✗ *A fruta amadureceu deliberadamente (The fruit ripened deliberately)
✗ *O metal enferrujou intencionalmente (The metal rusted intentionally)

Causative:
✓ João quebrou o vaso deliberadamente (João broke the vase deliberately)
```

## Collective/Distributed Events

Some eventive frames involve **multiple participants** acting collectively or distributively.

### Collective Events (Group as Unit)

**Definition**: Events where multiple participants act as a unified collective.

**Semantic structure**: `OCCUR(Event, PARTICIPANT(Collective_Theme))`

**Examples**:

**a) Social gatherings**:
```
As pessoas se reuniram
(The people gathered/met)
- Collective Theme: as pessoas (the people)
- Action: gathering (collective)

Os estudantes se manifestaram
(The students protested)
- Collective action
```

**b) Group motion**:
```
A multidão se moveu
(The crowd moved)
- Collective entity in motion

Os animais migraram
(The animals migrated)
- Collective migration
```

**c) Collective states becoming**:
```
O grupo se formou
(The group formed)
- Coming into existence as collective

A organização se dissolveu
(The organization dissolved)
- Ceasing to exist as collective
```

#### Properties

1. **Collective noun subjects**:
   ```
   multidão (crowd), grupo (group), população (population), time (team)
   ```

2. **Plural subjects acting as unit**:
   ```
   Os manifestantes (the protesters), as pessoas (the people)
   ```

3. **Reflexive construction** common:
   ```
   se reunir (gather), se manifestar (protest), se organizar (organize)
   ```

4. **No individual differentiation**:
   - Participants not distinguished individually
   - Focus on collective action/state


### Distributed Events (Multiple Individuals)

**Definition**: Events where multiple participants act separately but in parallel or related manner.

**Semantic structure**: `∀x ∈ Group: OCCUR(Event(x))`

**Examples**:

**a) Distributed occurrence**:
```
As flores desabrocharam
(The flowers bloomed)
- Each flower blooms individually
- Distributed across individuals
- May be temporally staggered

As folhas caíram
(The leaves fell)
- Each leaf falls separately
- Distributed event
```

**b) Distributed appearance**:
```
Os sintomas surgiram
(The symptoms appeared)
- Each symptom appears (possibly at different times)
- Distributed manifestation

Os problemas se multiplicaram
(The problems multiplied)
```

**c) Mass distributed events**:
```
As pedras rolaram
(The stones rolled)
- Each stone rolls (individual event)
- Many instances simultaneously

As estrelas brilharam
(The stars shone)
- Distributed across many individual stars
```

#### Properties

1. **Plural subjects** (not collective nouns):
   ```
   as flores (the flowers - individual flowers)
   as pedras (the stones - individual stones)
   ```

2. **Distributive reading preferred**:
   ```
   As flores desabrocharam uma por uma
   (The flowers bloomed one by one)
   - Sequential distribution explicit
   ```

3. **Can modify with distributive quantifiers**:
   ```
   Cada flor desabrochou
   (Each flower bloomed)
   ```

### Reciprocal Events (Mutual Interaction)

**Definition**: Events where participants mutually affect each other.

**Semantic structure**: `INTERACT(x, y)` where both are agents and patients

**Examples**:

**a) Social reciprocals**:
```
João e Maria se encontraram
(João and Maria met (each other))
- Mutual meeting
- Both are participants

Os amigos se cumprimentaram
(The friends greeted each other)
- Reciprocal action

As pessoas se abraçaram
(The people hugged (each other))
```

**b) Physical reciprocals**:
```
As moléculas se chocaram
(The molecules collided (with each other))
- Mutual collision

Os corpos se atraíram
(The bodies attracted (each other))
- Mutual attraction (physics)
```

**c) Verbal reciprocals**:
```
Eles se comunicaram
(They communicated (with each other))
- Mutual communication

Os países se confrontaram
(The countries confronted each other)
```

#### Linguistic properties

1. **Reflexive/reciprocal *se***:
   ```
   se encontrar (meet each other)
   se abraçar (hug each other)
   ```

2. **Plural or coordinated subjects**:
   ```
   João e Maria (João and Maria - coordinated)
   Os amigos (the friends - plural)
   ```

3. **Reciprocal interpretation test**:
   ```
   João e Maria se encontraram
   = João encontrou Maria E Maria encontrou João
   (Mutual/reciprocal reading)
   ```

4. **Can add reciprocal intensifier**:
   ```
   um ao outro (one another), mutuamente (mutually)
   João e Maria se ajudaram um ao outro
   (João and Maria helped one another)
   ```

## Relationship to Voice: Agent-Oriented Readings

Eventive frames have complex interactions with **voice** (active, passive, middle) and can sometimes take **agent-oriented readings**.

### Middle Voice and Eventive Frames

Many eventive frames use **middle voice** constructions - the reflexive *se* marking spontaneous/medial events.

**Middle voice characteristics** (Kemmer 1993):
- Event happens spontaneously (not externally caused)
- Subject is both initiator and endpoint (not clearly agent or patient)
- Marked with reflexive *se* in Portuguese

**Examples**:

**a) Spontaneous events (middle)**:
```
A porta se abriu
(The door opened (itself) / opened spontaneously)
- Middle construction with *se*
- Emphasizes spontaneity
- No external agent implied

O vaso se quebrou
(The vase broke (itself) / broke spontaneously)

O metal se oxidou
(The metal oxidized (itself))
```

**Contrast**:
```
WITHOUT SE (eventive, simple): A porta abriu (The door opened)
WITH SE (middle, emphatic spontaneity): A porta se abriu (The door opened spontaneously)
```

**b) Natural processes (middle)**:
```
A matéria se decompôs
(The matter decomposed (itself))

A substância se dissolveu
(The substance dissolved (itself))

O problema se resolveu
(The problem resolved (itself))
```

**Function of *se* in eventive frames**:

1. **Emphasizes spontaneity**:
   - *Se* highlights lack of external agent
   - Event occurs "by itself"

2. **Detransitivization**:
   - Removes potential agent from structure
   - *João abriu a porta* (transitive) → *A porta se abriu* (intransitive middle)

3. **Middle semantics**:
   - Subject is affected by event it undergoes
   - But not clearly a patient (no external agent)


### Passive vs. Eventive Interpretations

Some structures are **ambiguous** between passive (with implicit agent) and eventive (spontaneous) readings:

**Ambiguous structure**:
```
A porta foi aberta
(The door was opened)
```

**Reading 1: Passive (agent implied)**
```
A porta foi aberta (por alguém)
(The door was opened by someone)
- Implicit agent exists
- Someone opened it
- Focus on patient (door) but agent conceptually present
```

**Reading 2: Eventive/Spontaneous**
```
A porta foi aberta (espontaneamente/de alguma forma)
(The door opened / came to be open)
- No agent implied
- Spontaneous or unknown cause
- Pure event reading
```

#### Disambiguation factors

**Agent-addition test**:
```
✓ A porta foi aberta por João (passive - agent can be added)
? A porta foi aberta por si só (eventive - "by itself" suggests spontaneous)
```

**Manner test**:
```
A porta foi aberta deliberadamente
→ Passive reading (intentional agent implied)

A porta foi aberta de repente
→ Can be either (sudden event or sudden action)
```

**Context**:
```
Alguém abriu a porta? Sim, a porta foi aberta.
(Someone opened the door? Yes, the door was opened.)
→ Passive (agent in discourse context)

O que aconteceu? A porta foi aberta.
(What happened? The door opened.)
→ Eventive (event-asking context)
```

### Agentive Coercion of Eventive Frames

Some eventive frames can be **coerced into agentive readings** with appropriate context:

**Base eventive interpretation**:
```
O fogo queimou
(The fire burned)
- Simple occurrence
- No agent implied
```

**Coerced agentive interpretation**:
```
O fogo queimou (deliberadamente/por muito tempo/intensamente para consumir tudo)
(The fire burned deliberately/for a long time/intensely to consume everything)
→ Personification: fire construed as agent-like
→ Or: implies human agent maintaining fire
```

**More examples**:

```
As flores cresceram (The flowers grew - eventive)
vs.
As flores cresceram (com os cuidados dela) (The flowers grew with her care - facilitative agent implied)
```

#### Conditions for agentive coercion

1. **Intentional adverbs** suggest agency:
   ```
   deliberately, carefully, strategically
   ```

2. **Purpose clauses** imply intentionality:
   ```
   O fogo queimou para purificar a floresta
   (The fire burned to purify the forest - purposes suggest intent)
   ```

3. **Control predicates**:
   ```
   João fez o fogo queimar (João made the fire burn - control)
   ```

## Summary Table: Eventive Frame Properties

| **Subtype** | **Participants** | **Telicity** | **Spontaneity** | **Example** |
|-------------|------------------|--------------|-----------------|-------------|
| **Weather** | Minimal/none | Atelic (usually) | High | *chover, nevar* |
| **Geological** | Theme (optional) | Varies | High | *tremer, erupcionar* |
| **Biological** | Theme (usually) | Telic (often) | High | *crescer, amadurecer* |
| **Physical process** | Theme | Varies | High | *queimar, evaporar* |
| **Activity** | Theme | Atelic | Varies | *fluir, brilhar* |
| **Accomplishment** | Theme | Telic | Varies | *consumir-se, completar* |
| **Collective** | Collective Theme | Varies | Low | *reunir-se, manifestar-se* |
| **Distributed** | Multiple Themes | Varies | Varies | *desabrochar, cair* |
| **Reciprocal** | Multiple mutual | Atelic (often) | Low | *encontrar-se, chocar-se* |


## Diagnostic Tests for Eventive Frames

### Test 1: Participant Minimality
Can the frame occur with minimal or no specified participants?
```
✓ Choveu (It rained - no participants) → EVENTIVE
✗ *João matou (João killed - requires Patient) → NOT EVENTIVE (causative)
```

### Test 2: Agent Impossibility
Is an intentional agent incompatible or very odd?
```
✓ A rocha erodiu (The rock eroded - no agent) → EVENTIVE
✗ João construiu (João built - agent required) → NOT EVENTIVE (causative)
```

### Test 3: Spontaneity
Is the event conceptualized as occurring spontaneously/naturally?
```
✓ A fruta amadureceu (The fruit ripened - spontaneous) → EVENTIVE
✗ João amadureceu a fruta (odd - ripening not typically caused) → If acceptable, CAUSATIVE
```

### Test 4: Middle Voice *se*
Does the frame naturally take reflexive *se* to emphasize spontaneity?
```
✓ O problema se resolveu (The problem resolved itself) → EVENTIVE
✗ *João se construiu uma casa (ungrammatical middle) → NOT EVENTIVE
```

### Test 5: Impersonal Construction
Can the frame appear in impersonal constructions?
```
✓ Choveu muito (It rained a lot - impersonal) → EVENTIVE
✗ *João muito (nonsensical) → NOT EVENTIVE
```

### Test 6: Existential Construction
Can the frame appear with *haver* (there be) or similar?
```
✓ Houve uma tempestade (There was a storm) → EVENTIVE
✗ *Houve João matando Pedro (odd) → NOT typically EVENTIVE
```

### Test 7: Generic Theme Role
When participants are present, are their roles semantically underspecified?
```
✓ A água flui (Water flows - generic Theme, not affected/causing) → EVENTIVE
✗ João quebrou o vaso (Agent and Patient well-specified) → CAUSATIVE
```

## Boundary Cases and Overlaps

### Eventive vs. Inchoative

**Overlap**: Both involve change, but focus differs

**Inchoative**: Focus on **resultant state**
```
A porta abriu (The door opened)
- Interpretation: Door is now open (state achieved)
- Result-oriented
```

**Eventive**: Focus on **event occurrence**
```
A porta abriu (The door opened)
- Interpretation: Opening event occurred
- Process-oriented (though same sentence!)
```

**Ambiguity**: Many sentences are genuinely ambiguous
- Context determines interpretation
- Inchoative reading emphasized with result state contexts
- Eventive reading emphasized with temporal/event contexts

**Example context**:
```
INCHOATIVE context:
"Como está a porta?" "Está aberta - a porta abriu"
(How is the door? It's open - the door opened)
→ Focus on resultant state

EVENTIVE context:
"O que aconteceu?" "A porta abriu"
(What happened? The door opened)
→ Focus on event occurrence
```

### Eventive vs. Causative

**Overlap**: Same verb can be framed either way

**Causative**: Agent causes event
```
João abriu a porta (João opened the door)
- Agent-oriented
- Causation profiled
```

**Eventive**: Event occurs (spontaneously)
```
A porta abriu (The door opened)
- Event-oriented
- Causation backgrounded
```

**Diagnostic**: Presence/absence of Agent distinguishes
- Agent present → Causative
- Agent absent, spontaneous → Eventive

### Eventive vs. Action

**Core distinction**: **Agency** - volitional agent vs. natural phenomenon

**Action**: Volitional agent performs activity
```
João correu (João ran)
- Sentient, volitional agent
- Intentional activity
- Can take imperatives: *Corra!*
- ACT(João, run)
```

**Eventive**: Natural phenomenon or non-agentive occurrence
```
O vento soprou (The wind blew)
Choveu (It rained)
- No volitional agent
- Natural process
- Cannot take imperatives: ✗ *Vento, sopre!*
- OCCUR(wind_blow)
```

**Clear boundary**:
- Sentient, volitional agent required → **Action**
- No agent or non-agentive force → **Eventive**

**Note on causative natural forces**: When natural forces cause results (*O vento quebrou a janela* - Wind broke the window), these are classified as **Eventive** rather than Causative, maintaining the distinction between agentive and non-agentive causation.

### Eventive vs. Stative

**Clear distinction**: Dynamics

**Eventive**: Dynamic (something happens)
```
O rio flui (The river flows)
- Ongoing process/activity
- Dynamic
```

**Stative**: Static (something holds/obtains)
```
O rio é longo (The river is long)
- Property holds
- Static
```

**Diagnostic**: Progressive and change
- Eventive: Compatible with progressive, involves change
- Stative: Incompatible with progressive (or coerced), no change


### Eventive vs. Transition

**Overlap**: Both dynamic, but path structure differs

**Transition**: Path/Goal-oriented
```
O rio vai para o mar
(The river goes to the sea)
- Path from source to goal
- Goal profiled
```

**Eventive**: Event-oriented, path not essential
```
O rio flui
(The river flows)
- Ongoing activity
- No goal necessarily
```

**Diagnostic**: Presence of Source/Path/Goal
- Goal/Path profiled → Transition
- Activity without path → Eventive



[//]: # (### **11. Recommendations for FrameNet Brasil / DAISY**)

[//]: # ()
[//]: # (1. **Tag eventive frames with subtype features**:)

[//]: # (   ```)

[//]: # (   [eventive_type: weather|geological|biological|physical|chemical])

[//]: # (   [participant_structure: zero_argument|minimal|underspecified|collective])

[//]: # (   [telicity: atelic_activity|telic_accomplishment])

[//]: # (   [spontaneity: high|medium|low])

[//]: # (   [voice: active|middle|passive_reading])

[//]: # (   ```)

[//]: # ()
[//]: # (2. **Handle impersonal constructions**:)

[//]: # (   - Weather verbs: Mark as zero-argument eventive)

[//]: # (   - Existential constructions: Mark event/entity as Theme)

[//]: # (   - No forced participant roles where none exist)

[//]: # ()
[//]: # (3. **Distinguish middle voice**:)

[//]: # (   - Reflexive *se* with spontaneity → Middle voice eventive)

[//]: # (   - Mark separately from reflexive with reciprocal meaning)

[//]: # (   - Example: *A porta se abriu* &#40;middle&#41; vs. *Eles se encontraram* &#40;reciprocal&#41;)

[//]: # ()
[//]: # (4. **Mark ambiguous readings**:)

[//]: # (   - When structure allows passive OR eventive reading, mark both possibilities)

[//]: # (   - Use context to disambiguate when possible)

[//]: # (   - Example: *A porta foi aberta* can be passive or eventive)

[//]: # ()
[//]: # (5. **For DAISY parsing**: Use participant tests for disambiguation:)

[//]: # (   - If minimal participants + spontaneous → Eventive)

[//]: # (   - If Agent present → Check Causative first)

[//]: # (   - If Goal/Path present → Check Transition first)

[//]: # (   - If result state emphasized → Check Inchoative first)

[//]: # ()
[//]: # (6. **Handle collective/distributed**:)

[//]: # (   - Collective subjects → Mark as collective eventive)

[//]: # (   - Distributive plural → Mark as distributed)

[//]: # (   - Reciprocal *se* → Mark as reciprocal eventive)

[//]: # ()
[//]: # (7. **Aspectual classification**:)

[//]: # (   - "Por X tempo" compatible → Activity &#40;atelic&#41;)

[//]: # (   - "Em X tempo" compatible → Accomplishment &#40;telic&#41;)

[//]: # (   - Progressive incompatible → Check if actually Stative)

[//]: # ()
[//]: # (8. **Cross-namespace relations**:)

[//]: # (   - Link eventive to causative alternants: *A porta abriu* ↔ *João abriu a porta*)

[//]: # (   - Link eventive to inchoative: Same sentence, different focus)

[//]: # (   - Mark spontaneity scale to show relationship to causatives)

[//]: # ()
[//]: # (---)

## The Residual Nature of Eventive Namespace

The Eventive namespace serves as a **residual category** for dynamic occurrences that don't fit neatly elsewhere:

**What makes a frame Eventive?**

**Positive criteria** (what it IS):
1. Dynamic (something happens/occurs)
2. Event itself is primary semantic content
3. Participants are minimal, optional, or underspecified
4. Often spontaneous (no clear external causation)
5. Process-focused rather than participant-focused

**Negative criteria** (what it is NOT):
1. NOT Causative (no profiled Agent/Cause)
2. NOT Inchoative (not focused on result state achievement)
3. NOT Transition (no profiled path/goal structure)
4. NOT Experiential (no Experiencer undergoing mental/perceptual event)
5. NOT Stative (not static property or relation)

**Core examples that are clearly Eventive**:
- Weather: *chover, nevar* (rain, snow)
- Natural processes: *erosionar, evaporar* (erode, evaporate)
- Activities: *fluir, brilhar* (flow, shine)
- Spontaneous occurrences: *surgir, acontecer* (arise, happen)

**The Eventive namespace captures**: "Things that happen in the world, where the happening itself is what matters most."

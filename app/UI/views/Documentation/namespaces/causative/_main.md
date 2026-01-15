---
title: Causative
order: 1
description: Causative
---

# Causative Namespace

## Core Definition

Causative frames foreground the **AGENTIVE ROLE**, emphasizing the causal force that brings about events **with specific results or outcomes**.
These frames center on how entities or events initiate, trigger, or bring about **changes in the world**.
The causative namespace captures the semantic domain where **causation itself** is the primary conceptual content, where an Agent or Cause
brings about a state change or result in another entity.

**Theoretical foundation**: Causatives encode a basic cognitive schema where one entity or event is conceptualized as the **source of energy** or **impetus**
for another event or state change (Talmy's Force Dynamics, Langacker's Action Chain model).

**Key characteristic**: Causative frames are **result-oriented** and typically **telic** (have an inherent endpoint or goal state). The caused event or resultant state is semantically salient and often independently verifiable.

## Scope Clarification

**What Causative frames INCLUDE**:
- Agentive causation with results: *João quebrou o vaso* (João broke the vase → broken vase)
- Creation events: *Maria construiu uma casa* (Maria built a house → existing house)
- Change-of-state causation: *O calor derreteu o gelo* (Heat melted the ice → liquid water)
- Physical, social, and psychological causation with outcomes

**What Causative frames EXCLUDE** (see other namespaces):
- **Pure agentive activities without results** → See **Action namespace** (*João correu* - João ran)
- **Non-agentive natural phenomena** → See **Eventive namespace** (*O vento soprou* - The wind blew; *Choveu* - It rained)
- **Result-focused without agent** → See **Inchoative namespace** (*O vaso quebrou* - The vase broke)
- **Path-oriented motion** → See **Transition namespace** (*João foi para casa* - João went home)

## Subtypes based on Causation type

The causative namespace is not monolithic. We can identify several dimensions along which causative frames vary.

### Direct vs. Indirect Causation

#### Direct Causation

The causer is in immediate physical or perceptual contact with the caused event, with no intermediate steps.

**Semantic structure**: `CAUSE(Agent/Cause, Event)` - single causal link

**Examples**:
- *João quebrou o vaso* ("João broke the vase") - direct physical contact
- *O martelo quebrou o vidro* ("The hammer broke the glass") - direct instrumental causation
- *João empurrou Maria* ("João pushed Maria") - direct force application

**Syntactic properties**:
- Typically transitive with Agent as subject, Patient as direct object
- Causative and result encoded in single lexical verb
- No intermediate event overtly expressed

#### Indirect Causation

The causer triggers a chain of events, with intermediate steps or mechanisms between cause and effect.

**Semantic structure**: `CAUSE(Agent/Cause, Event₁) ∧ CAUSE(Event₁, Event₂)` - causal chain

**Examples**:
- *João fez Maria sair* ("João made Maria leave") - João's action causes Maria's departure (two events)
- *O terremoto causou o colapso do prédio* ("The earthquake caused the building's collapse") - natural force triggers structural failure
- *A política levou ao desemprego* ("The policy led to unemployment") - abstract causation with temporal/logical gap

**Syntactic properties**:
- Often requires causative periphrasis: *fazer com que*, *causar*, *provocar*, *levar a*
- Two-event structure: causing event and caused event
- Intermediate mechanisms may or may not be specified

#### Diagnostic test
```
DIRECT: Can be paraphrased with single-clause structure
✓ João quebrou o vaso
✗ ?João fez o vaso quebrar (marked, suggests indirect/accidental)

INDIRECT: Requires or strongly prefers two-clause structure  
✗ ?A política desempregou os trabalhadores (ungrammatical/not lexicalized)
✓ A política causou o desemprego dos trabalhadores
```

### Intentional vs. Accidental Causation

This dimension distinguishes **Agent** from **Cause** - the fundamental split in your event structure.

#### Intentional causation (Agent-driven)

**Semantic features**: `[+intentional, +volitional, +control, +sentient]`

**Examples**:
- *João matou Pedro* ("João killed Pedro") - deliberate action
- *Maria construiu uma casa* ("Maria built a house") - purposeful creation
- *O governo implementou a reforma* ("The government implemented the reform") - institutional agency

**Cognitive status**: The Agent is construed as having:
1. **Mental representation** of desired outcome (goal/intention)
2. **Volitional control** over their actions
3. **Responsibility/accountability** for the result

**Linguistic consequences**:
- Compatible with purpose clauses: *João quebrou o vaso para irritar Maria*
- Compatible with manner adverbs of intentionality: *deliberadamente, intencionalmente, de propósito*
- Can take imperatives: *Quebre o vaso!*
- Can be questioned with *por que* (why/reason): *Por que você quebrou o vaso?*

#### Accidental causation (still Agent)

An interesting intermediate case: sentient entity causes event but without intention.

**Semantic features**: `[+sentient, +volitional_action, -intended_result]`

**Examples**:
- *João quebrou o vaso sem querer* ("João broke the vase accidentally")
- *Maria matou Pedro acidentalmente* ("Maria killed Pedro accidentally")

**Key insight**: Portuguese uses the same verb forms but requires explicit markers (*sem querer, acidentalmente, por acidente*) to cancel the intentionality implicature.

#### Non-intentional causation (Cause-driven)

**Semantic features**: `[-intentional, -volitional, ±sentient]`

**Examples** (primarily abstract/non-physical causes):
- *O erro causou o acidente* ("The mistake caused the accident") - abstract cause
- *A doença matou milhares* ("The disease killed thousands") - biological process
- *O medo paralisou João* ("Fear paralyzed João") - psychological cause
- *A inflação aumentou os preços* ("Inflation increased prices") - economic cause

**Note on natural forces**: Non-agentive natural phenomena (wind, rain, earthquakes) are now classified in the **Eventive namespace** rather than Causative, even when they cause results. This maintains clearer boundaries:
- *O vento quebrou a janela* → **Eventive** (natural force)
- *O terremoto destruiu a cidade* → **Eventive** (natural event)
- Focus here is on **abstract or biological causes** that are not natural environmental phenomena.

**Cognitive status**: The Cause is construed as:
1. **Lacking mental states** (no intentions, desires, goals)
2. **Operating through physical/natural laws** or abstract relationships
3. **Not morally/legally responsible** (though can be involved in causal responsibility)

**Linguistic consequences**:
- Incompatible with purpose clauses: ✗ *O erro causou o acidente para irritar alguém*
- Incompatible with intentionality adverbs: ✗ *A doença matou deliberadamente*
- Cannot take imperatives: ✗ *Erro, cause o acidente!*
- Question with *como* (how/mechanism) not *por que* (why/reason): *Como o erro causou o acidente?*

#### The Agent-Cause gradient

Not all cases are clear-cut. Consider:

- **Institutional agents**: *O governo aumentou os impostos*
  - Collective intentionality but distributed agency
  
- **Animals**: *O cachorro quebrou o vaso*
  - Sentient but degree of intentionality unclear
  
- **Automated systems**: *O algoritmo aprovou o empréstimo*
  - Designed purpose but no subjective intentionality

### Physical vs. Social/Psychological Causation

#### Physical causation

Cause operates through physical mechanisms (force, energy, contact).

**Examples**:
- *O martelo achatou o metal* ("The hammer flattened the metal")
- *O calor derreteu o gelo* ("The heat melted the ice")
- *A explosão destruiu o prédio* ("The explosion destroyed the building")

**Domain**: Physical objects, forces, energies
**Mechanism**: Force dynamics, energy transfer
**Result**: Physical state changes

#### Social causation

Cause operates through social relationships, power structures, obligations.

**Examples**:
- *O juiz condenou o réu* ("The judge convicted the defendant")
- *O chefe demitiu o funcionário* ("The boss fired the employee")  
- *O parlamento aprovou a lei* ("Parliament approved the law")

**Domain**: Social institutions, legal/political systems
**Mechanism**: Social norms, institutional authority, legal force
**Result**: Changes in social/legal status

**Key feature**: Requires **social frameworks** - causation depends on conventional systems (law, organizations, norms) not just physical mechanisms.

#### Psychological causation

Cause operates through mental/emotional influence.

**Examples**:
- *Maria convenceu João a sair* ("Maria convinced João to leave")
- *O filme emocionou a plateia* ("The film moved the audience")
- *A notícia assustou as crianças* ("The news scared the children")

**Domain**: Mental states, emotions, beliefs
**Mechanism**: Persuasion, emotional influence, information transfer
**Result**: Changes in psychological states or belief-induced actions

**Special property**: Often involves **stimulus-experiencer** structure that overlaps with Experiential namespace (see your Experiencer discussion in event structure).

#### Diagnostic
- Physical: ✓ Operates without conscious participants
- Social: ✗ Requires social framework/institutions
- Psychological: ✗ Requires conscious experiencer

## Semantic Decomposition of Causative Frames

Following Pustejovsky's event structure and Dowty's decompositional semantics:

### Basic Causative Structure

**General template**:
```
CAUSE(x, BECOME(State(y)))
where:
  x = Agent/Cause (AGENTIVE quale)
  y = Patient (TELIC quale - endpoint/affected)
  State = resultant condition (FORMAL quale)
```

**Example**: *João quebrou o vaso*
```
CAUSE(João, BECOME(broken(vaso)))
  Agent: João [+intentional, +volitional]
  Event: BECOME(broken(vaso))
  Patient: vaso
  Result_state: broken
```

### Accomplishment Causatives (with process)

Some causatives include an explicit process component:

**Template**:
```
CAUSE(x, [PROCESS(y) & BECOME(State(y))])
```

**Example**: *João construiu uma casa*
```
CAUSE(João, [BUILD_PROCESS(casa) & BECOME(exists(casa))])
  Agent: João
  Process: incremental building activity
  Theme/Patient: casa (incremental theme - comes into existence gradually)
  Result: existence of complete house
```

**Key property**: **Incremental theme** - the Patient comes into being or undergoes change incrementally as the event progresses (Dowty, Krifka).

**Diagnostic**: Compatible with durative temporal expressions
- ✓ *João construiu a casa em três meses* (in three months - completed)
- ✓ *João construiu a casa por três meses* (for three months - process)

### Achievement Causatives (punctual)

Other causatives are conceptualized as instantaneous:

**Template**:
```
CAUSE(x, BECOME(State(y))) [punctual]
```

**Example**: *João quebrou o vaso*
```
CAUSE(João, BECOME(broken(vaso))) [at instant t]
  Agent: João  
  Patient: vaso
  Result: instantaneous transition to broken state
```

**Diagnostic**: Incompatible with progressive without coercion
- ✗ *João está quebrando o vaso* (requires iterative or slow-motion coercion)
- ✓ *João quebrou o vaso em um segundo* (at/in point of time)

### Complex Causative Chains

**Indirect causative template**:
```
CAUSE(x, Event₁) & CAUSE(Event₁, Event₂) & ... & RESULT(Eventₙ)
```

**Example**: *A chuva causou o deslizamento que destruiu as casas*
```
CAUSE(rain, landslide) & CAUSE(landslide, DESTROY(houses))
  Initial_cause: rain
  Intermediate_event: landslide  
  Final_result: destruction of houses
```

### Relation to Qualia Structure

Causative frames centrally involve **AGENTIVE quale** but recruit elements from all qualia:

| **Qualia** | **Role in Causative** | **Example element** |
|------------|----------------------|---------------------|
| AGENTIVE | **Core** - causer | Agent, Cause |
| TELIC | Result/affected entity | Patient, Goal |
| CONSTITUTIVE | Means/instruments | Instrument, Means |
| FORMAL | Resultant state | Result, End_state |

**Example**: *João cortou o pão com a faca*
- AGENTIVE: João (agent)
- TELIC: pão (patient/affected)
- CONSTITUTIVE: faca (instrument)
- FORMAL: pão cortado (result state)

## Diagnostic Tests for Causative Frames

### Periphrastic Causative Test

**Test**: Can the frame be paraphrased with *fazer com que* or *causar* + subordinate clause?

**Positive result** = Causative frame

```
✓ João quebrou o vaso → João fez com que o vaso quebrasse
✓ Maria matou Pedro → Maria fez com que Pedro morresse  
✓ O governo aprovou a lei → O governo fez com que a lei fosse aprovada
```

**False positives**: Some non-causative frames can be coerced:
```
? João viu Maria → ?João fez com que visse Maria (coercion to intentional perception)
```

**Strength**: Good for identifying core causative semantics

### Causative Alternation Test

**Test**: Does the frame participate in the causative-inchoative alternation?

**Transitive (causative)**: *X quebrou Y* (X caused Y to break)
**Intransitive (inchoative)**: *Y quebrou* (Y broke)

```
✓ João abriu a porta ↔ A porta abriu
✓ Maria derreteu o gelo ↔ O gelo derreteu
✓ O vento quebrou a janela ↔ A janela quebrou
```

**Non-alternating causatives** (no intransitive):
```
✓ João construiu a casa ↔ ✗ *A casa construiu
✓ Maria criou o projeto ↔ ✗ *O projeto criou
```

**Insight**: Creation verbs (*criar, construir, fabricar*) don't alternate because the Patient doesn't exist independently - it comes into being through the Agent's action.

**Strength**: Identifies change-of-state causatives specifically

### Instrument/Means Test

**Test**: Can the frame take instrumental phrases with *com* or *usando*?

```
✓ João quebrou o vaso com o martelo
✓ Maria cortou o pão com a faca
✓ O governo implementou a reforma usando novos métodos
```

**Positive result** = Likely causative with Agent (instruments require intentional users)

**Negative cases**:
```
✗ *O vento quebrou a janela com velocidade alta (velocidade is not instrument)
✓ O vento quebrou a janela por causa da velocidade alta (manner/reason, not instrument)
```

**Strength**: Distinguishes Agent-causatives from Cause-causatives

### Passivization Test

**Test**: Can the frame passivize naturally?

```
ACTIVE: João quebrou o vaso
PASSIVE: O vaso foi quebrado (por João)

ACTIVE: Maria construiu a casa  
PASSIVE: A casa foi construída (por Maria)
```

**Positive result** = Transitive causative structure

**Complication**: Not all causatives passivize equally well
```
✓ João matou Pedro → Pedro foi morto por João (good)
? João criou o problema → O problema foi criado por João (acceptable)
✗ *Isso custa dez reais → *Dez reais são custados por isso (ungrammatical)
```

**Strength**: Confirms Patient role and transitive structure

### Intentionality Tests

**Test A - Purpose clauses**: Can add *para + infinitive* (in order to)?

```
✓ João quebrou o vaso para irritar Maria (Agent - intentional)
✗ *O vento quebrou a janela para irritar Maria (Cause - non-intentional)
```

**Test B - Manner of intentionality**: Can add *deliberadamente, intencionalmente, de propósito*?

```
✓ João quebrou o vaso deliberadamente (Agent)
✗ *O vento quebrou a janela deliberadamente (Cause)
```

**Test C - Imperatives**: Can the verb take imperative form meaningfully?

```
✓ Quebre o vaso! (Agent possible)
✗ *Vento, quebre a janela! (Cause impossible)
```

**Strength**: Distinguishes Agent from Cause

### Result State Test

**Test**: Does the frame entail a specific result state that can be independently verified?

```
João quebrou o vaso → O vaso está quebrado
Maria abriu a porta → A porta está aberta  
O fogo derreteu o gelo → O gelo está derretido
```

**Positive result** = Causative with resultant state component

**Non-result causatives**:
```
João empurrou Maria → ? Maria está empurrada (no clear result state)
O vento balançou a árvore → ? A árvore está balançada (activity, not result)
```

**Strength**: Identifies accomplishment/achievement causatives vs. pure activity causatives

## Summary Table: Causative Subtypes

| **Dimension** | **Type** | **Features** | **Example** | **Test** |
|---------------|----------|--------------|-------------|----------|
| **Directness** | Direct | Single causal link | *quebrar, matar* | Single lexical verb |
| | Indirect | Causal chain | *causar, fazer com que* | Requires periphrasis |
| **Intentionality** | Agent | +intentional, +volitional | *construir, matar* | Purpose clauses ✓ |
| | Cause (abstract) | -intentional | *erro causar, doença matar* | Purpose clauses ✗ |
| **Domain** | Physical | Physical mechanism | *quebrar, derreter* | Physical objects |
| | Social | Social framework | *demitir, aprovar* | Institutional context |
| | Psychological | Mental influence | *convencer, assustar* | Mental state change |
| **Aspect** | Achievement | Punctual result | *quebrar, explodir* | Incompatible with progressive |
| | Accomplishment | Durative process | *construir, pintar* | Compatible with durative PP |

## Boundary Cases and Namespace Distinctions

The Causative namespace has important boundaries with other namespaces, particularly the new **Action namespace** and the **Eventive namespace**.

### Causative vs. Action

**Core distinction**: **Result-orientation** (telicity)

**Causative** (Result-oriented, telic):
```
João quebrou o vaso (João broke the vase)
- Agent: João
- Result: vaso quebrado (broken vase)
- Telic: has inherent endpoint (broken state)
- Semantic decomposition: CAUSE(João, BECOME(broken(vaso)))
```

**Action** (Process-oriented, atelic):
```
João correu (João ran)
- Agent: João
- Activity: running
- Atelic: no inherent endpoint
- Semantic decomposition: ACT(João, run)
```

**Diagnostic distinction**:

**Test 1: Result State**
- **Causative**: Can verify result independently
  - *João quebrou o vaso* → ✓ *O vaso está quebrado* (vase is broken)
- **Action**: No verifiable result state
  - *João correu* → ✗ *João está corrido* (nonsensical)

**Test 2: Telicity ("em X tempo" vs. "por X tempo")**
- **Causative**: Compatible with "em X tempo" (bounded)
  - ✓ *João quebrou o vaso em um segundo*
- **Action**: Compatible with "por X tempo" (unbounded)
  - ✓ *João correu por uma hora*

**Test 3: Periphrastic Causative**
- **Causative**: Can paraphrase with *fazer com que*
  - ✓ *João quebrou o vaso* → *João fez com que o vaso quebrasse*
- **Action**: Periphrastic causative is odd/changes meaning
  - ? *João correu* → *João fez com que corresse* (coercion needed)

**Ambiguous cases** (can be read both ways):

*João empurrou Maria* (João pushed Maria)
- **Causative reading**: Pushing caused Maria to move/fall (result-focus)
  - Result: Maria displaced or fell
- **Action reading**: João performed pushing activity (process-focus)
  - Focus: activity of pushing itself

**Classification guideline**:
- If a clear result state can be verified → **Causative**
- If focus is on activity without clear result → **Action**
- If both readings are equally accessible → tag as both or context-dependent

### Causative vs. Eventive

**Core distinction**: **Agency/Causation** vs. **Pure Occurrence**

**Causative** (Agentive causation):
```
João quebrou a janela (João broke the window)
- Intentional agent performs causative action
- Agent-Patient structure
```

**Eventive** (Natural phenomenon):
```
O vento soprou (The wind blew)
Choveu (It rained)
- No intentional agent
- Event-centric, minimal participant structure
- Natural forces and processes
```

**Note on non-agentive causes**:
Historically, non-agentive natural forces in causative structures (*O vento quebrou a janela* - The wind broke the window) were classified in Causative namespace. However, for greater theoretical clarity, **non-agentive natural phenomena** (wind, rain, earthquakes) are now classified in the **Eventive namespace**, even when they cause results. This separates:
- **Agentive causation** (sentient entities causing changes) → **Causative**
- **Natural processes** (non-agentive forces and phenomena) → **Eventive**

### Causative vs. Inchoative

**Core distinction**: **Profiled participant**

**Causative** (Agent/Cause profiled):
```
João quebrou o vaso (João broke the vase)
- Focus: João (causer)
- Agent in subject position
- CAUSE(João, BECOME(broken(vaso)))
```

**Inchoative** (Theme/Patient profiled):
```
O vaso quebrou (The vase broke)
- Focus: vaso (affected entity)
- Theme in subject position
- BECOME(broken(vaso))
```

**The Causative-Inchoative Alternation**:
Many verbs participate in this alternation:
```
Causative (transitive): João quebrou o vaso
Inchoative (intransitive): O vaso quebrou
```

This alternation is a fundamental property linking the two namespaces - they describe the same type of event from different perspectives (causer vs. affected entity).

[//]: # (### **6. Recommendations for FrameNet Brasil / DAISY**)

[//]: # ()
[//]: # (1. **Tag causative frames** with subtype features:)

[//]: # (   - `[±direct]`)

[//]: # (   - `[±intentional]` &#40;Agent vs Cause&#41;)

[//]: # (   - `[domain: physical|social|psychological]`)

[//]: # (   - `[aspect: achievement|accomplishment]`)

[//]: # ()
[//]: # (2. **Create frame-to-frame relations**:)

[//]: # (   - `Causative_of` relation linking causative frames to their inchoative counterparts)

[//]: # (   - Example: *Breaking* &#40;causative&#41; ↔ *Fragmentation* &#40;inchoative&#41;)

[//]: # ()
[//]: # (3. **For DAISY parsing**: Use diagnostic tests as **features for frame disambiguation**)

[//]: # (   - If purpose clause possible → Agent reading preferred)

[//]: # (   - If periphrastic causative required → Indirect_causation frame)

[//]: # (   - If alternates with intransitive → Change_of_state_causative)

[//]: # ()
[//]: # (4. **Annotation guideline**: When FE could be either Agent or Cause, use intentionality tests to decide)

[//]: # ()

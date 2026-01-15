---
title: Action
order: 2
description: Action
---

# Action Namespace

## Core Definition

Action frames foreground the **AGENTIVE ROLE** with emphasis on **activities and processes** performed by sentient agents, where the activity itself is the primary semantic content rather than any resultant state or change. These frames center on what agents **do** - the actions they perform - independent of whether those actions produce specific outcomes or results.

**Theoretical foundation**: Action frames encode the basic template:

```
ACT(Agent, Activity)
  [with optional: MANNER(how)]
  [with optional: LOCATION(where)]
  [with optional: TIME(when)]
```

Unlike causatives which encode `CAUSE(x, BECOME(State(y)))`, action frames encode **agentive activity** without requiring a resultant state. The agent is the primary participant, and the activity or process is profiled, not the outcome.

**Key characteristics**:
- **Agent-centric**: A sentient, volitional agent is required
- **Process/activity-oriented**: The doing itself is semantically salient
- **Atelic (typically)**: No inherent endpoint or required result state
- **Agentive**: Involves intentional, voluntary actions (though degree varies)
- **Dynamic**: Involves change/happening over time

**Vendler aspectual class**: Primarily **Activities** (atelic, durative processes) but can include some **Accomplishments** when focus is on process rather than result

**Philosophical grounding**: Action frames capture what is central to Action Theory in philosophy - intentional behavior by agents. They relate to **agency**, **volition**, and **intentional action** without necessarily requiring causation of external changes.

## Activity Types: Major Domains

Action frames divide into several major subtypes based on the **kind of activity** performed.

### Motion Activities

**Definition**: Activities involving physical displacement or movement of the agent's body through space, where the motion itself is the primary content (not the path or goal).

**Semantic template**: `ACT(Agent, Motion_Activity)`

**Examples**:

**a) Manner of motion (no specified path)**:
```
João correu (João ran)
- Agent: João
- Activity: running
- No specified path or goal
- Atelic: can continue indefinitely

Maria nadou (Maria swam)
- Agent: Maria
- Activity: swimming
- No endpoint specified

Pedro voou (Pedro flew)
- Agent: Pedro
- Activity: flying
```

**b) Self-propelled motion**:
```
João pulou (João jumped)
Maria saltou (Maria leaped)
Pedro dançou (Pedro danced)
```

**Properties**:
- Agent moves their own body
- No required Patient or Theme
- Activity can be iterated or prolonged
- Compatible with durative expressions: *João correu por duas horas*

**Contrast with Transition namespace**:
- **Action**: *João correu* (focus on activity of running)
- **Transition**: *João foi para casa* (focus on path from source to goal)

### Work and Labor Activities

**Definition**: Activities involving productive work, effort, or labor where the agent engages in goal-directed but not necessarily result-producing actions.

**Semantic template**: `ACT(Agent, Work_Activity)`

**Examples**:

**a) General work**:
```
João trabalhou (João worked)
- Agent: João
- Activity: working
- No specific product mentioned
- Atelic: *trabalhou por oito horas*

Maria estudou (Maria studied)
- Agent: Maria
- Activity: studying
- Process of learning (not result)

Pedro pesquisou (Pedro researched)
- Agent: Pedro
- Activity: researching
```

**b) Domain-specific labor**:
```
João ensinou (João taught)
Maria dirigiu (Maria drove)
Pedro escreveu (Pedro wrote)
```

**Note**: Some of these verbs can also be causative when they have objects with clear results:
- *Pedro escreveu* (Action - writing activity)
- *Pedro escreveu um livro* (Causative - created a book)

**Properties**:
- Agent engages in effortful activity
- May or may not produce tangible results
- Often professional or skilled activities
- Can be bounded by time: *trabalhou das 9h às 17h*

### Performance and Expression Activities

**Definition**: Activities involving expressive, performative, or communicative actions where the agent produces sounds, movements, or expressions.

**Semantic template**: `ACT(Agent, Performance_Activity)`

**Examples**:

**a) Vocal/sound production**:
```
João cantou (João sang)
Maria gritou (Maria shouted)
Pedro falou (Pedro spoke)
Ana murmurou (Ana murmured)
```

**b) Musical performance**:
```
João tocou (João played [an instrument])
Maria dançou (Maria danced)
```

**c) Physical expression**:
```
João gesticulou (João gestured)
Maria acenou (Maria waved)
Pedro sorriu (Pedro smiled)
```

**Properties**:
- Produces perceptible output (sound, movement)
- Often communicative intent
- Can be artistic or expressive
- May have audience or may be solo

### Interaction and Social Activities

**Definition**: Activities involving social interaction, communication, or coordinated action with others.

**Semantic template**: `ACT(Agent, Social_Activity, [with_Participant])`

**Examples**:

**a) Conversational**:
```
João conversou (com Maria) (João conversed with Maria)
Maria discutiu (com Pedro) (Maria discussed with Pedro)
Pedro debateu (Pedro debated)
```

**b) Collaborative**:
```
João colaborou (João collaborated)
Maria participou (Maria participated)
Pedro cooperou (Pedro cooperated)
```

**c) Competitive**:
```
João competiu (João competed)
Maria lutou (Maria fought)
Pedro disputou (Pedro contested)
```

**Properties**:
- Involves other participants (explicit or implicit)
- Often reciprocal or mutual actions
- Social or interpersonal context
- May be cooperative or adversarial

### Bodily and Physiological Activities

**Definition**: Activities related to basic bodily functions and physiological processes under volitional control.

**Semantic template**: `ACT(Agent, Bodily_Activity)`

**Examples**:

**a) Basic bodily functions**:
```
João respirou (João breathed)
Maria dormiu (Maria slept)
Pedro comeu (Pedro ate)
Ana bebeu (Ana drank)
```

**b) Postural activities**:
```
João sentou (João sat down)
Maria levantou (Maria stood up)
Pedro deitou (Pedro lay down)
```

**Properties**:
- Involve agent's body
- Some voluntary, some semi-automatic
- Basic survival or comfort functions
- Often intransitive

### Cognitive Activities

**Definition**: Mental activities where the agent engages in cognitive processes (thinking, planning, imagining) as activities in themselves.

**Semantic template**: `ACT(Agent, Cognitive_Activity, [about_Content])`

**Examples**:

**a) Thinking activities**:
```
João pensou (João thought)
Maria refletiu (Maria reflected)
Pedro imaginou (Pedro imagined)
Ana considerou (Ana considered)
```

**b) Planning activities**:
```
João planejou (João planned)
Maria organizou (Maria organized)
Pedro preparou (Pedro prepared)
```

**Properties**:
- Mental/cognitive domain
- Agent has control over process
- Can take propositional complements
- Often purposeful or goal-directed

## Agentivity and Control

A defining feature of Action frames is the presence of an **Agent** - a sentient entity with volitional control over the action.

### Properties of Agents in Action Frames

**1. Sentience**: The agent is a conscious, sentient being
```
✓ João correu (João ran - sentient agent)
✗ *A pedra correu (The stone ran - inanimate, cannot be agent of running)
```

**2. Volition**: The agent has volitional control
```
✓ João decidiu correr (João decided to run - volitional)
✓ João correu voluntariamente (João ran voluntarily)
```

**3. Intentionality**: The agent intends to perform the activity
```
✓ João correu para se exercitar (João ran to exercise - purposeful)
✓ João trabalhou deliberadamente (João worked deliberately)
```

**4. Responsibility**: The agent is responsible for the action
```
✓ João é responsável por ter corrido (João is responsible for having run)
```

### Diagnostic Tests for Agentivity

**Test 1: Imperative**
Agentive actions can take imperative form:
```
✓ Corre! (Run!)
✓ Trabalhe! (Work!)
✓ Cante! (Sing!)
```

**Test 2: Purpose clauses**
Compatible with *para* + infinitive (in order to):
```
✓ João correu para se exercitar (ran in order to exercise)
✓ Maria trabalhou para ganhar dinheiro (worked in order to earn money)
```

**Test 3: Manner adverbs of intentionality**
```
✓ João correu deliberadamente (ran deliberately)
✓ Maria trabalhou cuidadosamente (worked carefully)
```

**Test 4: "Decidir" (decide to)**
Can be embedded under decision verbs:
```
✓ João decidiu correr (decided to run)
✓ Maria decidiu trabalhar (decided to work)
```

## Telicity and Aspect

A crucial property distinguishing Action frames from Causative frames is **telicity** - whether the event has an inherent endpoint.

### Atelic Activities (Most Action Frames)

**Definition**: Activities with **no inherent endpoint** - they can continue indefinitely and don't culminate in a specific result state.

**Vendler class**: Activities

**Examples**:
```
João correu (João ran)
Maria trabalhou (Maria worked)
Pedro cantou (Pedro sang)
```

**Aspectual properties**:

**1. Compatible with durative "por X tempo" (for X time)**:
```
✓ João correu por duas horas (ran for two hours)
✓ Maria trabalhou por oito horas (worked for eight hours)
```

**2. Incompatible with "em X tempo" (in X time - completion)**:
```
✗ *João correu em duas horas (ran in two hours - odd without coercion)
✗ *Maria trabalhou em oito horas (worked in eight hours - odd)
```

**3. Compatible with progressive**:
```
✓ João está correndo (João is running)
✓ Maria está trabalhando (Maria is working)
```

**4. No specific result state**:
```
João correu → ✗ *João está corrido (no result state)
Maria trabalhou → ✗ *Maria está trabalhada (no result state)
```

**5. "Parar de" (stop) test**:
Atelic activities can be stopped at any point without canceling the activity:
```
✓ João parou de correr (João stopped running - still counts as having run)
✓ Maria parou de trabalhar (Maria stopped working - still counts as having worked)
```

### Telic Accomplishments (Some Action Frames)

Some action frames can be **telic** when they involve activities with inherent endpoints or when bounded by objects/complements.

**Examples**:
```
João escreveu uma carta (João wrote a letter)
- Activity: writing
- Endpoint: completed letter
- Can be Action (focus on writing activity) or Causative (focus on created letter)

Maria correu um quilômetro (Maria ran a kilometer)
- Activity: running
- Endpoint: one kilometer distance
- Bounded activity becomes telic
```

**Aspectual shift**: Adding measure phrases or objects can shift atelic to telic:

**Atelic** (unbounded):
```
João correu (por uma hora) - activity focus
```

**Telic** (bounded):
```
João correu um quilômetro (em 10 minutos) - accomplishment with endpoint
```

## Transitivity and Argument Structure

Action frames vary in their argument structure and transitivity.

### Intransitive Actions (Most Common)

**Structure**: Agent (subject) only, no object

**Examples**:
```
João correu (João ran)
Maria dormiu (Maria slept)
Pedro trabalhou (Pedro worked)
Ana dançou (Ana danced)
```

**Properties**:
- Single argument (Agent)
- No Patient or Theme affected
- Activity is self-contained
- Agent is both doer and only participant

### Transitive Actions with Non-affected Objects

Some actions can take objects, but the objects are not affected or changed (contrast with Causative):

**Examples**:
```
João tocou o violão (João played the guitar)
- Object: violão (guitar)
- Guitar is instrument, not affected entity
- No change to guitar's state

Maria dirigiu o carro (Maria drove the car)
- Object: carro (car)
- Car is instrument of driving
- No change to car's state (assuming normal operation)
```

**Diagnostic**: Object is not affected/changed
```
ACTION: João tocou o violão → Violão não mudou (Guitar didn't change)
CAUSATIVE: João quebrou o violão → Violão mudou (quebrado) (Guitar changed - broken)
```

### Actions with Oblique Complements

Many actions take oblique complements (PPs) rather than direct objects:

**Examples**:
```
João conversou com Maria (João conversed with Maria)
- Oblique: com Maria
- Reciprocal/social activity

Maria participou do evento (Maria participated in the event)
- Oblique: do evento
- Locative/involvement

Pedro falou sobre política (Pedro spoke about politics)
- Oblique: sobre política
- Topic/content
```

## Manner and Modification

Action frames are often specified or modified by manner expressions, indicating **how** the action is performed.

### Manner Adverbs

**Examples**:
```
João correu rapidamente (João ran quickly)
Maria trabalhou cuidadosamente (Maria worked carefully)
Pedro cantou alegremente (Pedro sang happily)
Ana dançou graciosamente (Ana danced gracefully)
```

**Types of manner**:
- **Speed**: *rapidamente, lentamente, depressa*
- **Care**: *cuidadosamente, descuidadamente*
- **Attitude**: *alegremente, tristemente, entusiasticamente*
- **Quality**: *bem, mal, perfeitamente*

### Manner-of-Action Verbs

Some verbs lexicalize specific manners of more basic actions:

**Motion**:
- Basic: *mover-se* (move)
- Manner-specific: *correr* (run), *andar* (walk), *rastejar* (crawl), *saltar* (jump)

**Speaking**:
- Basic: *falar* (speak)
- Manner-specific: *gritar* (shout), *sussurrar* (whisper), *murmurar* (murmur)

**Working**:
- Basic: *trabalhar* (work)
- Manner-specific: *esforçar-se* (exert oneself), *labutar* (toil), *dedicar-se* (dedicate oneself)

## Diagnostic Tests for Action Frames

### Test 1: Agentivity Tests

**Imperative**:
```
✓ Corra! (Run!) → ACTION
✗ *Quebre! (Break! - requires object) → CAUSATIVE
✗ *Ocorra! (Occur! - no agent) → EVENTIVE
```

### Test 2: Telicity Tests

**"Por X tempo" (for X time)**:
```
✓ João correu por duas horas → ACTION (atelic)
? João quebrou o vaso por duas horas → CAUSATIVE (odd - telic event)
```

**"Em X tempo" (in X time)**:
```
? João correu em duas horas (odd without endpoint) → ACTION
✓ João quebrou o vaso em um segundo → CAUSATIVE (telic)
```

### Test 3: Result State Test

**Does action entail verifiable result state?**
```
ACTION: João correu → ✗ *João está corrido (no result state)
CAUSATIVE: João quebrou o vaso → ✓ O vaso está quebrado (result state exists)
```

### Test 4: Transitivity Test

**Can occur intransitively with agent only?**
```
✓ João correu → ACTION (intransitive agent activity)
✗ *João quebrou → CAUSATIVE (requires object)
```

### Test 5: Purpose/Goal Modification

**Compatible with purpose clauses?**
```
✓ João correu para se exercitar (ran to exercise) → ACTION
✓ João quebrou o vaso para irritar Maria (broke to annoy) → CAUSATIVE
Both compatible, so not distinctive alone
```

### Test 6: Activity Continuation

**Can be stopped and restarted without negating the activity?**
```
✓ João parou de correr e depois continuou → Still counts as "ran"
✗ *João parou de quebrar o vaso e depois continuou → Odd (breaking is punctual)
```

## Boundary Cases and Namespace Overlaps

Action frames have important boundaries with several other namespaces.

### Action vs. Causative

**Core distinction**: **Result-orientation** and **telicity**

**Action** (Process-oriented, atelic):
```
João correu (João ran)
- Focus: activity of running
- No required result
- Atelic: *correu por uma hora*
- ACT(João, run)
```

**Causative** (Result-oriented, telic):
```
João quebrou o vaso (João broke the vase)
- Focus: result (broken vase)
- Clear result state
- Telic: *quebrou em um segundo*
- CAUSE(João, BECOME(broken(vaso)))
```

**Ambiguous cases**:

*João empurrou Maria* (João pushed Maria)
- **Action reading**: Activity of pushing (process focus)
- **Causative reading**: Caused Maria to move/fall (result focus)

*João escreveu* (João wrote)
- **Action reading**: Writing activity (*João escreveu por horas*)
- **Causative reading** (with object): Created text (*João escreveu um livro*)

**Classification guideline**:
- Has clear, independently verifiable result? → **Causative**
- No clear result, focus on activity? → **Action**
- Ambiguous? → Context-dependent or dual classification

### Action vs. Eventive

**Core distinction**: **Agency** - presence of volitional agent

**Action** (Agent required):
```
João correu (João ran)
- Sentient agent: João
- Volitional activity
- Can take imperatives: *Corra!*
```

**Eventive** (No agent required):
```
Choveu (It rained)
O vento soprou (The wind blew)
- No volitional agent
- Natural phenomena
- Cannot take imperatives: ✗ *Chova!*
```

**Clear boundary**: If sentient, volitional agent is required and profiled → **Action**; if agent is absent or not required → **Eventive**

### Action vs. Experiential

**Core distinction**: **Domain** - physical/observable vs. mental/perceptual

**Action** (Observable activity):
```
João correu (João ran)
- Physical activity
- Externally observable
- Bodily movement
```

**Experiential** (Mental/perceptual):
```
João pensou (João thought)
- Mental activity
- Not externally observable
- Cognitive process
```

**Overlap**: Cognitive actions that are volitional

*João pensou sobre o problema* (João thought about the problem)
- Can be **Action** (volitional cognitive activity)
- Can be **Experiential** (cognitive process)
- Classification depends on whether focus is on agency or mental domain

### Action vs. Inchoative

**Core distinction**: **Profiled participant**

**Action** (Agent profiled):
```
João correu (João ran)
- Agent is subject and focus
- Agentive activity
```

**Inchoative** (Theme profiled, no agent):
```
O vaso quebrou (The vase broke)
- Theme is subject
- No agent mentioned
- Focus on result/change
```

**Clear boundary**: Action requires agent; Inchoative focuses on affected entity without agent.

### Action vs. Transition

**Core distinction**: **Path/Goal** vs. **Activity**

**Action** (Activity focus):
```
João correu (João ran)
- Focus: manner of motion (running)
- No specified path or goal
- ACT(João, run)
```

**Transition** (Path/Goal focus):
```
João foi para casa (João went home)
- Focus: path from source to goal
- Directed motion
- MOVE(João, to_Goal(casa))
```

**Overlap**: Motion verbs can sometimes be both

*João correu para casa* (João ran home)
- **Action reading**: Running activity with destination (*correu* is main verb)
- **Transition reading**: Directed motion via running (*para casa* is profiled)

**Classification guideline**:
- Path/Goal is central and profiled? → **Transition**
- Manner of motion is central? → **Action**

## Summary Table: Action Frame Properties

| **Property** | **Value** | **Example** | **Test** |
|--------------|-----------|-------------|----------|
| **Agent** | Required | *João correu* | Imperative: ✓ *Corra!* |
| **Telicity** | Atelic (typical) | *correr, trabalhar* | ✓ *por X tempo* |
| **Result state** | No (typical) | *correu* → ✗ *corrido* | No result state adjective |
| **Transitivity** | Intransitive (typical) | *João correu* | No required object |
| **Vendler class** | Activity | *correr, trabalhar* | Durative, no endpoint |
| **Domain** | Physical/observable | *correr, cantar* | Externally perceivable |

## Summary Table: Action vs. Other Namespaces

| **Distinction** | **Action** | **Other Namespace** | **Key Diagnostic** |
|-----------------|------------|---------------------|-------------------|
| **vs. Causative** | No result focus | Has result state | Result state test |
| **vs. Eventive** | Agent required | No agent | Agentivity tests |
| **vs. Experiential** | Observable activity | Mental/perceptual | Domain test |
| **vs. Inchoative** | Agent profiled | Theme profiled | Subject role |
| **vs. Transition** | Activity focus | Path/Goal focus | Path specification |

## Comprehensive Diagnostic Test Battery

| **Test** | **Action Result** | **Non-Action Result** |
|----------|-------------------|-----------------------|
| **Imperative** | ✓ Natural | ✗ Odd or impossible |
| **Agentivity** | Requires agent | Agent optional/absent |
| **Por X tempo** | ✓ Compatible | ? Marginal (if telic) |
| **Result state** | ✗ No result | ✓ Has result (Causative) |
| **Intransitive** | ✓ Often | Varies by namespace |
| **Purpose clause** | ✓ Compatible | Varies |
| **Manner adverbs** | ✓ Natural | Varies |

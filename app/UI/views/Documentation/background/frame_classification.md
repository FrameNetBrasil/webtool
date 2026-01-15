---
title: Frame Classification
order: 15
description: Frame classification
---

# Frame Classification

Frames are classified into 3 dimensions: Domain, Type, and Namespace

### Dimension: Domain

- The frames in the current network were developed over many years, in several different projects. Many frames are for general use, but many others are related to specific domains.

- Recording the domain to which a frame belongs can help in the disambiguation process, if it is possible to determine the domain from the context of the sentence.

- The list of domains is open and can/should be extended as new frames are created.

- The initial list includes: `@Agriculture, @Biology, @Body, @Business, @Cloth, @Communication, @Emotion, @Employment, @Finance, @Fire,
@Food, @Generic, @Health, @Legal, @Linguistics, @Math, @Military, @Music, @Physics, @Psychology, @Social, @Sports, @Time, @Tourism,
@Transport, @Visit, @Weapon`

### Dimension: type

- The frames in the network are of different types. "Type" here refers to some characteristics and functions that the frame possesses within the network.
Explicitly characterizing the frame type makes the network structure more understandable and can facilitate the disambiguation process.

- The types implemented in the current network are:

- `@Non-lexical`: frames that do not have LUs and are used to connect other frames semantically in the network. They are used to maintain the network structure.

- `@Lexical`: frames that are evoked by LUs.

- `@ImageSchemas`: frames that were created in the network to represent very basic/primitive *image schemas*. Generally, these frames are very generic and establish a structure (via Frame Elements) that can be inherited by lexical or non-lexical frames.

- `@Scenario`: frames that have the function of structuring a given scenario, bringing together other frames through various different relationships.
Scenario frames are important because, if activated, they can allow inferences about other parts of the scene/sentence that are not explicitly stated, enriching the NLU process.

- `@Non-perspectivalized`: According to the Book, these frames have a great diversity of LUs, each with a background scene. These frames do not have:

  - A consistent set of FEs for the targets

  - A consistent time for the events or participants

  - A consistent point of view among the targets

    These frames could be broken down into more consistent frames, but these would have few LUs. Today there are 41 frames marked as *non-perspectivalized frames*.

### Dimension: namespace

- An important idea that had been worked on was the possibility of establishing a *frame lattice* that would allow the definition of *top frames*. 
The network would be structured around these top frames. In the Lutma implementation, a lattice was also defined with the aim of facilitating the creation of new frames.

- The problem with defining top frames is the difficulty of establishing inheritance or perspective relationships in many generic frames. 
Thus, the idea implemented was the definition of *namespaces* for frames. These namespaces function as a set of frames that share some basic semantics.

- The frames sharing the same namespace do not need to be related to each other. 

- In the current version, the following namespaces are implemented:

| Namespace                                           | Description                                                                                                                                                                                                                                                             | Typical structure                                                              |
|-----------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------|
| <span class="color_situation">@situation</span>     | This is a "catch-all" namespace for frames that do not fit into the other namespaces. Used for scenario frames and image-schema frames.                                                                                                                                 | Variable structure                        |
| <span class="color_eventive">@eventive</span>       | Eventive frames that do not have an explicitly defined agent, cause, or experiencer. This includes, for example, events without agentive focus, events with non-human agents, natural phenomena (rain, erosion, combustion, growth), processes (physical. social, etc). | FE core ("event") incorporated by LU.                                          |
| <span class="color_causative">@causative</span>     | Eventive frames that have a cause (non intentional) or an agent (intentional, volitional, goal-directed actions).                                                                                                                                                       | FE core "agent" or "cause" (in excludes relation)                              |
| <span class="color_inchoative">@inchoative</span>   | Eventive frames that exhibit inchoative alternation.                                                                                                                                                                                                                    | FE core indicating the affected element.                                       |
| <span class="color_action">@action</span>  | Eventive frames that profiles the activity performed by agents.                                                                                                             | FE core "agent"                               |
| <span class="color_stative">@stative</span>         | Frames that represent states (no change implied) about entities.                                                                                                                                                                                                        | FE core for entity, FE core-unexpressed for state.                             |
| <span class="color_experiential">@experience</span> | Eventive frames that profile the participant as an experiencer in an event or being affected by a cause or an agent.                                                                                                                                                    | FE core for entity experimenting the event ("experiencer", "perceiver", etc)   |
| <span class="color_transition">@transition</span>   | Eventive frames that represent changes in a situation (state, attribute, category, etc.) of an entity.                                                                                                                                                                  | FE core for entity under transition; FEs for initial/final state or condition. |
| <span class="color_attribute">@attribute</span>     | Frames that represent attributes or attribute values.                                                                                                                                                                                                                   | FE core for attribute, FE core-unexpressed for attribute.                      |
| <span class="color_entity">@entity</span>           | Frames that represent entities.                                                                                                                                                                                                                                         | FE core for entity, incorporated by LU.                                        |
| <span class="color_relational">@relation </span>    | Frames that represent relations between entities or events.                                                                                                                                                                                                             | FEs core for related concepts and FE core joining the concepts.                |
| <span class="color_pragmatic">@pragmatic</span>     | Pragmatic frames.                                                                                                                                                                                                                                                       | Variable structure.                                                            |


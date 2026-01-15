---
title: Getting Started
order: 1
description: Quick start guide for Webtool 4.2
summary: Introduction to the documentation style and core writing principles. Explains how to structure notes in a clear, modular, interlinked way inspired by Zettelkasten, so contributors can create small, focused pages that are easy to navigate, maintain, and expand.
aliases:
tags:
---
UID:202511180943
backlink:
related:
# Getting Started

Welcome to the collaborative documentation for this project.

Before writing or editing anything, it helps to understand a few core principles so everyone can contribute in a consistent and productive way.

The goal is to structure information in a clear, modular, and easy-to-browse format — much like note-taking in Obsidian or Notion and inspired by the [Zettelkasten](https://en.wikipedia.org/wiki/Zettelkasten) method, which builds knowledge through small, connected notes. In this approach, each note focuses on a single topic and links to related topics, creating a network of small, connected pages instead of long, heavy documents. This makes the content easier to navigate and easier to expand. Long pages are harder to browse because readers have to scroll, guess where things are, and hope the right section exists. Smaller, focused notes make it simpler to find information, search for concepts, and keep the documentation organized.

## What it means to write in an “interlinked notes” style

Instead of long, dense pages, we prefer:

- **Short notes** (small, focused chunks of information)
- **One topic per file**, when it makes sense
- **Plenty of links between pages**
- **Clear, direct titles**
- **Minimal context**: explain just enough; if readers want more, they click

This keeps the documentation readable, searchable, and easier to maintain.

## Core Principles

### 1. Split content into smaller notes

If a file is getting too long, it probably contains more than one concept.

Example:

The section “Namespace Classification Using Qualia” inside [Events](https://webtool.frame.net.br/docs/background/events) could be broken down like this:

```
namespace-classification-using-qualia.md
│
├── namespace-causative.md
├── namespace-inchoative.md
├── namespace-experience.md
├── namespace-stative.md
├── namespace-transition.md
├── namespace-eventive.md
└── qualia-roles-and-namespace-mapping.md
```

This makes the content far easier to find.

### 2. Use links generously

Zettelkasten works because notes point to each other.  
Every new file should link to:

- related concepts
- definitions
- procedures
- examples

Example:

>In the section on <u>Namespace Classification Using Qualia</u>, we explain how frames are grouped by their core qualia signatures. For cases where the <u>Agentive Role</u> is foregrounded, check out <u>Causative Namespace</u>. When the focus is on <u>entities</u> undergoing a change of state, check <u>Inchoative Namespace</u>, and for explicit before/after state changes, go to <u>Transition Namespace</u>. Psychological and perceptual events are described in <u>Experience Namespace</u>, while static situations and classifications are covered in <u>Stative Namespace</u>. For processes where the event itself is central, without a highlighted <u>agent</u> or <u>experiencer</u>, refer to <u>Eventive Namespace</u>. For a compact overview of how each <u>qualia role</u> maps onto these namespaces, you can also consult <u>Qualia Roles and Namespace Mapping</u>.

In this example, each underlined segment should link to its own dedicated page. This network of links lets a reader jump directly between related ideas — instead of scrolling through one long document — and builds a map of connected concepts you can expand over time.

### 3. Start every note with “what it’s for” using YAML frontmatter

Each Markdown file should begin with a short YAML block that explains the purpose of the note. This replaces long introductions and gives readers an immediate sense of what the page is about. At minimum, include a summary.

Example:

> title: Your Note Title
> summary: A short explanation of what this note is for — what it describes, why it matters, or what problem it helps solve.
>
> title: Namespace Classification Using Qualia
> summary: Explains how frames are grouped into namespaces based on their core qualia signatures (Formal, Constitutive, Telic, Agentive).

---
### TL;DR

1. Keep it short and clear.
2. Use brief paragraphs, simple sentences, and examples.
3. If a section gets dense, split it into a separate note and link to it.

---
title: Annotation Guide
order: 1
description: How to annotate text with frame semantic information
---

# Annotation Guide

Learn how to annotate text with frame semantic information.

## Annotation Types

Webtool 4.2 supports multiple annotation modes:

### Full-Text Annotation

Annotate complete sentences with frame and frame element information.

1. Select a sentence from your corpus
2. Identify the target word (lexical unit)
3. Mark frame elements in the sentence
4. Assign appropriate FE labels

### Multimodal Annotation

Annotate images and videos with semantic information:

- **Static Objects**: Bounding boxes on images
- **Dynamic Objects**: Time-based regions in videos
- **Deixis Annotation**: Spatial and temporal references

## Annotation Workflow

```
1. Select Corpus → 2. Choose Document → 3. Pick Sentence
                     ↓
4. Identify Target LU → 5. Mark Frame Elements → 6. Save Annotation
```

## Quality Guidelines

- Ensure frame elements don't overlap
- Use the most specific FE labels available
- Annotate all core frame elements when present
- Mark null instantiations explicitly
- Review annotations for consistency

## Keyboard Shortcuts

- `Ctrl+S`: Save annotation
- `Ctrl+Z`: Undo last change
- `Ctrl+Y`: Redo
- `Esc`: Cancel current annotation

## Annotation Reports

Generate reports to track annotation progress:

- Annotations by frame
- Coverage statistics
- Inter-annotator agreement
- Quality metrics

## See Also

- [Frame Elements](frame-elements.md)
- [Corpus Management](corpus-management.md)

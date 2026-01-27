<?php

namespace App\Services\SeqGraph;

use App\Models\SeqGraph\ParseEvent;
use App\Models\SeqGraph\ParseTreeNode;

/**
 * Builds a parse result tree from parse events.
 *
 * Reconstructs the hierarchical structure of how patterns combined
 * to parse the input, based on the events recorded during activation.
 */
class ResultGraphBuilder
{
    /**
     * Build a parse tree from events.
     *
     * @param  array<ParseEvent>  $events  Parse events from the activation engine
     * @return array<ParseTreeNode> Root nodes of the parse tree (may be multiple for ambiguous parses)
     */
    public function build(array $events): array
    {
        ParseTreeNode::resetIdCounter();

        // Group events by time
        $eventsByTime = $this->groupEventsByTime($events);

        // Build pattern completion records
        $completions = $this->extractCompletions($events);

        // Build element firing records
        $elementFirings = $this->extractElementFirings($events);

        // Build construction ref linkages
        $constructionRefs = $this->extractConstructionRefs($events);

        // Build the tree bottom-up
        $trees = $this->buildTrees($completions, $elementFirings, $constructionRefs);

        return $trees;
    }

    /**
     * Group events by time.
     *
     * @param  array<ParseEvent>  $events  Events
     * @return array<int, array<ParseEvent>> Events grouped by time
     */
    private function groupEventsByTime(array $events): array
    {
        $grouped = [];
        foreach ($events as $event) {
            if (! isset($grouped[$event->time])) {
                $grouped[$event->time] = [];
            }
            $grouped[$event->time][] = $event;
        }
        ksort($grouped);

        return $grouped;
    }

    /**
     * Extract pattern completion records.
     *
     * @param  array<ParseEvent>  $events  Events
     * @return array<array{pattern: string, time: int, completedBy: string|null}> Completions
     */
    private function extractCompletions(array $events): array
    {
        $completions = [];
        foreach ($events as $event) {
            if ($event->type === ParseEvent::TYPE_PATTERN_COMPLETED) {
                $completions[] = [
                    'pattern' => $event->patternName,
                    'time' => $event->time,
                    'completedBy' => $event->completedByNodeId,
                ];
            }
        }

        return $completions;
    }

    /**
     * Extract element firing records.
     *
     * @param  array<ParseEvent>  $events  Events
     * @return array<array{pattern: string, nodeId: string, time: int, elementType: string, inputValue: string}> Firings
     */
    private function extractElementFirings(array $events): array
    {
        $firings = [];
        foreach ($events as $event) {
            if ($event->type === ParseEvent::TYPE_ELEMENT_FIRED) {
                $firings[] = [
                    'pattern' => $event->patternName,
                    'nodeId' => $event->nodeId,
                    'time' => $event->time,
                    'elementType' => $event->elementType,
                    'inputValue' => $event->inputValue,
                ];
            }
        }

        return $firings;
    }

    /**
     * Extract construction ref linkages.
     *
     * @param  array<ParseEvent>  $events  Events
     * @return array<array{parentPattern: string, childPattern: string, nodeId: string, time: int}> Linkages
     */
    private function extractConstructionRefs(array $events): array
    {
        $refs = [];
        foreach ($events as $event) {
            if ($event->type === ParseEvent::TYPE_CONSTRUCTION_REF_FIRED) {
                $refs[] = [
                    'parentPattern' => $event->patternName,
                    'childPattern' => $event->triggeredByPattern,
                    'nodeId' => $event->nodeId,
                    'time' => $event->time,
                    'role' => $this->extractRole($event->nodeId),
                ];
            }
        }

        return $refs;
    }

    /**
     * Extract role from node ID (e.g., "CLAUSE:subj" -> "subj").
     *
     * @param  string|null  $nodeId  Node ID
     * @return string|null Role or null
     */
    private function extractRole(?string $nodeId): ?string
    {
        if ($nodeId === null) {
            return null;
        }

        if (str_contains($nodeId, ':')) {
            $parts = explode(':', $nodeId);

            return $parts[1] ?? null;
        }

        return null;
    }

    /**
     * Build parse trees from extracted data.
     *
     * @param  array  $completions  Pattern completions
     * @param  array  $elementFirings  Element firings
     * @param  array  $constructionRefs  Construction ref linkages
     * @return array<ParseTreeNode> Root nodes
     */
    private function buildTrees(array $completions, array $elementFirings, array $constructionRefs): array
    {
        // Deduplicate completions - only keep unique pattern:time combinations
        $uniqueCompletions = [];
        foreach ($completions as $completion) {
            $key = "{$completion['pattern']}:{$completion['time']}";
            if (! isset($uniqueCompletions[$key])) {
                $uniqueCompletions[$key] = $completion;
            }
        }
        $completions = array_values($uniqueCompletions);

        // Create nodes for each pattern completion
        $patternNodes = [];  // key: "pattern:time" => ParseTreeNode

        foreach ($completions as $completion) {
            $key = "{$completion['pattern']}:{$completion['time']}";

            // Find the start time for this pattern (earliest element or construction ref at this completion)
            $startTime = $this->findPatternStartTime(
                $completion['pattern'],
                $completion['time'],
                $elementFirings,
                $constructionRefs
            );

            $node = ParseTreeNode::pattern(
                $completion['pattern'],
                $startTime,
                $completion['time']
            );

            $patternNodes[$key] = $node;
        }

        // Track which children have been added to each parent to avoid duplicates
        $parentChildren = [];  // "parentKey" => ["childKey1" => true, ...]

        // Create terminal nodes for element firings and link to their patterns
        // Use a unique key for each terminal to avoid duplicates
        $terminalNodes = [];  // "pattern:nodeId:time" => terminal node
        foreach ($elementFirings as $firing) {
            $terminalKey = "{$firing['pattern']}:{$firing['nodeId']}:{$firing['time']}";

            // Skip if we already created this terminal
            if (isset($terminalNodes[$terminalKey])) {
                continue;
            }

            $terminalNode = ParseTreeNode::terminal(
                $firing['elementType'],
                $firing['time'],
                $firing['elementType'],
                $firing['inputValue'],
                $firing['nodeId']
            );
            $terminalNodes[$terminalKey] = $terminalNode;

            // Find the pattern completion this element contributed to
            $parentKey = $this->findParentCompletion(
                $firing['pattern'],
                $firing['time'],
                $completions
            );

            if ($parentKey !== null && isset($patternNodes[$parentKey])) {
                // Check if this terminal is already a child
                if (! isset($parentChildren[$parentKey][$terminalKey])) {
                    $patternNodes[$parentKey]->addChild($terminalNode);
                    $parentChildren[$parentKey][$terminalKey] = true;
                }
            }
        }

        // Deduplicate construction refs - only keep unique parent+child+time combinations
        $uniqueRefs = [];
        foreach ($constructionRefs as $ref) {
            $refKey = "{$ref['parentPattern']}:{$ref['childPattern']}:{$ref['time']}";
            if (! isset($uniqueRefs[$refKey])) {
                $uniqueRefs[$refKey] = $ref;
            }
        }
        $constructionRefs = array_values($uniqueRefs);

        // Link patterns via construction refs
        foreach ($constructionRefs as $ref) {
            // Find the child pattern completion at this time
            $childKey = "{$ref['childPattern']}:{$ref['time']}";

            // Find the parent pattern completion at or after this time
            $parentKey = $this->findParentCompletion(
                $ref['parentPattern'],
                $ref['time'],
                $completions
            );

            if (isset($patternNodes[$childKey]) && $parentKey !== null && isset($patternNodes[$parentKey])) {
                // Check if this child is already linked to this parent
                if (! isset($parentChildren[$parentKey][$childKey])) {
                    $childNode = $patternNodes[$childKey];
                    $childNode->role = $ref['role'];
                    $patternNodes[$parentKey]->addChild($childNode);
                    $parentChildren[$parentKey][$childKey] = true;
                }
            }
        }

        // Find root nodes (nodes without parents)
        $roots = [];
        foreach ($patternNodes as $node) {
            if ($node->parent === null) {
                $roots[] = $node;
            }
        }

        // Sort roots by end time (latest first, as they are the most complete)
        usort($roots, fn ($a, $b) => $b->endTime <=> $a->endTime);

        // Sort children by start time
        foreach ($patternNodes as $node) {
            usort($node->children, fn ($a, $b) => $a->startTime <=> $b->startTime);
        }

        return $roots;
    }

    /**
     * Find the start time for a pattern completion.
     *
     * @param  string  $pattern  Pattern name
     * @param  int  $endTime  Completion time
     * @param  array  $elementFirings  Element firings
     * @param  array  $constructionRefs  Construction ref linkages
     * @return int Start time
     */
    private function findPatternStartTime(string $pattern, int $endTime, array $elementFirings, array $constructionRefs): int
    {
        $startTime = $endTime;

        // Check element firings for this pattern up to this time
        foreach ($elementFirings as $firing) {
            if ($firing['pattern'] === $pattern && $firing['time'] <= $endTime) {
                $startTime = min($startTime, $firing['time']);
            }
        }

        // Check construction refs for this pattern up to this time
        foreach ($constructionRefs as $ref) {
            if ($ref['parentPattern'] === $pattern && $ref['time'] <= $endTime) {
                $startTime = min($startTime, $ref['time']);
            }
        }

        return $startTime;
    }

    /**
     * Find the parent pattern completion for an event at a given time.
     *
     * @param  string  $pattern  Pattern name
     * @param  int  $time  Event time
     * @param  array  $completions  All completions
     * @return string|null Key "pattern:time" or null
     */
    private function findParentCompletion(string $pattern, int $time, array $completions): ?string
    {
        // Find the earliest completion of this pattern at or after this time
        $best = null;
        $bestTime = PHP_INT_MAX;

        foreach ($completions as $completion) {
            if ($completion['pattern'] === $pattern && $completion['time'] >= $time && $completion['time'] < $bestTime) {
                $best = "{$completion['pattern']}:{$completion['time']}";
                $bestTime = $completion['time'];
            }
        }

        return $best;
    }
}

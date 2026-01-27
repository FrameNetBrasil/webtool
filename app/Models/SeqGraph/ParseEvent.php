<?php

namespace App\Models\SeqGraph;

/**
 * Represents an event during parsing.
 *
 * Events track what happened during activation processing,
 * allowing reconstruction of the parse tree afterwards.
 */
class ParseEvent
{
    public const TYPE_ELEMENT_FIRED = 'element_fired';

    public const TYPE_PATTERN_COMPLETED = 'pattern_completed';

    public const TYPE_CONSTRUCTION_REF_FIRED = 'construction_ref_fired';

    /**
     * Event type.
     */
    public string $type;

    /**
     * Timestamp when event occurred.
     */
    public int $time;

    /**
     * Pattern name this event belongs to.
     */
    public string $patternName;

    /**
     * Node ID that fired (for element/construction_ref events).
     */
    public ?string $nodeId;

    /**
     * Element type that was matched.
     */
    public ?string $elementType;

    /**
     * Input value that was matched (for terminal elements).
     */
    public ?string $inputValue;

    /**
     * For CONSTRUCTION_REF_FIRED: which pattern triggered this.
     */
    public ?string $triggeredByPattern;

    /**
     * For PATTERN_COMPLETED: which element node firing caused completion.
     */
    public ?string $completedByNodeId;

    /**
     * Create a new parse event.
     */
    public function __construct(
        string $type,
        int $time,
        string $patternName,
        ?string $nodeId = null,
        ?string $elementType = null,
        ?string $inputValue = null,
        ?string $triggeredByPattern = null,
        ?string $completedByNodeId = null
    ) {
        $this->type = $type;
        $this->time = $time;
        $this->patternName = $patternName;
        $this->nodeId = $nodeId;
        $this->elementType = $elementType;
        $this->inputValue = $inputValue;
        $this->triggeredByPattern = $triggeredByPattern;
        $this->completedByNodeId = $completedByNodeId;
    }

    /**
     * Create an element fired event.
     */
    public static function elementFired(
        int $time,
        string $patternName,
        string $nodeId,
        string $elementType,
        string $inputValue
    ): self {
        return new self(
            self::TYPE_ELEMENT_FIRED,
            $time,
            $patternName,
            $nodeId,
            $elementType,
            $inputValue
        );
    }

    /**
     * Create a pattern completed event.
     */
    public static function patternCompleted(
        int $time,
        string $patternName,
        ?string $completedByNodeId = null
    ): self {
        return new self(
            self::TYPE_PATTERN_COMPLETED,
            $time,
            $patternName,
            null,
            null,
            null,
            null,
            $completedByNodeId
        );
    }

    /**
     * Create a construction ref fired event.
     *
     * This happens when a pattern completes and triggers a CONSTRUCTION_REF
     * node in another pattern.
     */
    public static function constructionRefFired(
        int $time,
        string $patternName,
        string $nodeId,
        string $elementType,
        string $triggeredByPattern
    ): self {
        return new self(
            self::TYPE_CONSTRUCTION_REF_FIRED,
            $time,
            $patternName,
            $nodeId,
            $elementType,
            null,
            $triggeredByPattern
        );
    }
}
